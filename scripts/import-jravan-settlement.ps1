param(
    [Parameter(Mandatory = $true)]
    [int] $AppRaceId,

    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d{8}$')]
    [string] $RaceDate,

    [Parameter(Mandatory = $true)]
    [ValidatePattern('^\d{2}$')]
    [string] $CourseCode,

    [Parameter(Mandatory = $true)]
    [ValidateRange(1, 12)]
    [int] $RaceNo,

    [string] $ApiBaseUrl = 'http://localhost',
    [string] $ApiToken = $env:JRA_VAN_IMPORT_TOKEN,
    [string] $FromTime = '',
    [ValidateSet(1, 2, 3, 4)]
    [int] $JvOption = 1,
    [switch] $DryRun
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ([Environment]::Is64BitProcess) {
    $scriptPath = $PSCommandPath
    $pwsh32 = Join-Path $env:WINDIR 'SysWOW64\WindowsPowerShell\v1.0\powershell.exe'
    $args = @(
        '-NoProfile',
        '-ExecutionPolicy', 'Bypass',
        '-File', $scriptPath,
        '-AppRaceId', $AppRaceId,
        '-RaceDate', $RaceDate,
        '-CourseCode', $CourseCode,
        '-RaceNo', $RaceNo,
        '-ApiBaseUrl', $ApiBaseUrl,
        '-FromTime', $FromTime,
        '-JvOption', $JvOption
    )
    if ($ApiToken) {
        $args += @('-ApiToken', $ApiToken)
    }
    if ($DryRun) {
        $args += '-DryRun'
    }

    & $pwsh32 @args
    exit $LASTEXITCODE
}

if (-not $DryRun -and [string]::IsNullOrWhiteSpace($ApiToken)) {
    throw 'ApiToken is required. Set JRA_VAN_IMPORT_TOKEN or pass -ApiToken.'
}

$encoding = [Text.Encoding]::GetEncoding(932)
$targetMonthDay = $RaceDate.Substring(4, 4)
$targetRaceNo = '{0:D2}' -f $RaceNo
if ([string]::IsNullOrWhiteSpace($FromTime)) {
    $fromDate = [datetime]::ParseExact($RaceDate, 'yyyyMMdd', $null).AddDays(-14)
    $FromTime = $fromDate.ToString('yyyyMMdd') + '000000'
}

function Get-JvBytes([string] $record) {
    return $encoding.GetBytes($record)
}

function Get-JvField([byte[]] $bytes, [int] $position, [int] $length) {
    if ($bytes.Length -lt ($position - 1 + $length)) {
        return ''
    }

    return $encoding.GetString($bytes, $position - 1, $length).Trim()
}

function Convert-JvInt([string] $value) {
    $digits = ($value -replace '[^0-9]', '')
    if ($digits -eq '') {
        return $null
    }

    return [int] $digits
}

function Convert-JvSelection([string] $raw, [string] $separator, [int] $unitLength = 2) {
    $digits = ($raw -replace '[^0-9]', '')
    if ($digits.Length -lt $unitLength -or ($digits -replace '0', '') -eq '') {
        return $null
    }

    $parts = @()
    for ($i = 0; $i -lt $digits.Length; $i += $unitLength) {
        if ($i + $unitLength -gt $digits.Length) {
            return $null
        }

        $part = [int] $digits.Substring($i, $unitLength)
        if ($part -le 0) {
            return $null
        }
        $parts += [string] $part
    }

    return ($parts -join $separator)
}

function New-PayoutRow([string] $selectionKey, [string] $payout, [string] $popularity) {
    $payoutValue = Convert-JvInt $payout
    if ($null -eq $selectionKey -or $null -eq $payoutValue -or $payoutValue -le 0) {
        return $null
    }

    $row = [ordered]@{
        selection_key = $selectionKey
        payout_per_100 = $payoutValue
    }
    $popularityValue = Convert-JvInt $popularity
    if ($null -ne $popularityValue -and $popularityValue -gt 0) {
        $row.popularity = $popularityValue
    }

    return $row
}

function Add-HrPayoutRows([hashtable] $payouts, [byte[]] $bytes) {
    $definitions = @(
        @{ type = 'tansho'; start = 103; repeat = 3; width = 13; keyLength = 2; separator = '-' },
        @{ type = 'fukusho'; start = 142; repeat = 5; width = 13; keyLength = 2; separator = '-' },
        @{ type = 'wakuren'; start = 207; repeat = 3; width = 13; keyLength = 2; separator = '-'; unitLength = 1 },
        @{ type = 'umaren'; start = 246; repeat = 3; width = 16; keyLength = 4; separator = '-' },
        @{ type = 'wide'; start = 294; repeat = 7; width = 16; keyLength = 4; separator = '-' },
        @{ type = 'umatan'; start = 454; repeat = 6; width = 16; keyLength = 4; separator = '>' },
        @{ type = 'sanrenpuku'; start = 550; repeat = 3; width = 18; keyLength = 6; separator = '-' },
        @{ type = 'sanrentan'; start = 604; repeat = 6; width = 19; keyLength = 6; separator = '>' }
    )

    foreach ($definition in $definitions) {
        for ($i = 0; $i -lt $definition.repeat; $i++) {
            $base = $definition.start + ($i * $definition.width)
            $rawKey = Get-JvField $bytes $base $definition.keyLength
            $unitLength = if ($definition.ContainsKey('unitLength')) { $definition.unitLength } else { 2 }
            $selectionKey = Convert-JvSelection $rawKey $definition.separator $unitLength
            $payout = Get-JvField $bytes ($base + $definition.keyLength) 9
            $popularityOffset = if ($definition.keyLength -eq 2) { 11 } elseif ($definition.keyLength -eq 6) { 15 } else { 13 }
            $popularityLength = if ($definition.keyLength -eq 2) { 2 } elseif ($definition.type -eq 'sanrentan') { 4 } else { 3 }
            $popularity = Get-JvField $bytes ($base + $popularityOffset) $popularityLength
            $row = New-PayoutRow $selectionKey $payout $popularity
            if ($null -ne $row) {
                $payouts[$definition.type] += @($row)
            }
        }
    }
}

$records = @()
$jv = New-Object -ComObject JVDTLab.JVLink
try {
    $initCode = $jv.JVInit('UNKNOWN')
    if ($initCode -ne 0) {
        throw "JVInit failed: $initCode"
    }

    $readCount = 0
    $downloadCount = 0
    $lastFileTimestamp = ''
    $openCode = $jv.JVOpen('RACE', $FromTime, $JvOption, [ref] $readCount, [ref] $downloadCount, [ref] $lastFileTimestamp)
    if ($openCode -ne 0) {
        throw "JVOpen failed: $openCode"
    }

    while ($true) {
        $buffer = ' ' * 110000
        $size = 110000
        $fileName = ' ' * 256
        $readCode = $jv.JVRead([ref] $buffer, [ref] $size, [ref] $fileName)
        if ($readCode -eq 0) {
            break
        }
        if ($readCode -eq -1) {
            continue
        }
        if ($readCode -lt 0) {
            throw "JVRead failed: $readCode"
        }

        $records += $buffer
    }
}
finally {
    if ($null -ne $jv) {
        $jv.JVClose() | Out-Null
    }
}

$ranks = @{
    '1' = @()
    '2' = @()
    '3' = @()
}
$withdrawals = @()
$payouts = @{
    tansho = @()
    fukusho = @()
    wakuren = @()
    umaren = @()
    wide = @()
    umatan = @()
    sanrenpuku = @()
    sanrentan = @()
}

$matchedSeRecords = @()
$matchedHrRecords = @()
foreach ($record in $records) {
    $bytes = Get-JvBytes $record
    $recordType = Get-JvField $bytes 1 2
    if ($recordType -notin @('SE', 'HR')) {
        continue
    }

    $year = Get-JvField $bytes 12 4
    $monthDay = Get-JvField $bytes 16 4
    $courseCode = Get-JvField $bytes 20 2
    $raceNo = Get-JvField $bytes 26 2
    if ($year + $monthDay -ne $RaceDate -or $courseCode -ne $CourseCode -or $raceNo -ne $targetRaceNo) {
        continue
    }

    if ($recordType -eq 'SE') {
        $matchedSeRecords += $record
    }
    elseif ($recordType -eq 'HR') {
        $matchedHrRecords += $record
    }
}

$latestSeGroup = @($matchedSeRecords `
    | Group-Object {
        $bytes = Get-JvBytes $_
        (Get-JvField $bytes 3 1) + '|' + (Get-JvField $bytes 4 8)
    } `
    | Sort-Object `
        @{ Expression = { [int] ($_.Name.Split('|')[0]) }; Descending = $true }, `
        @{ Expression = { $_.Name.Split('|')[1] }; Descending = $true } `
    | Select-Object -First 1)
$latestSeRecords = if ($latestSeGroup.Count -gt 0) { @($latestSeGroup[0].Group) } else { @() }

$latestHrRecord = @($matchedHrRecords `
    | Sort-Object `
        @{ Expression = { $bytes = Get-JvBytes $_; [int] (Get-JvField $bytes 3 1) }; Descending = $true }, `
        @{ Expression = { $bytes = Get-JvBytes $_; Get-JvField $bytes 4 8 }; Descending = $true } `
    | Select-Object -First 1)

foreach ($record in $latestSeRecords) {
        $bytes = Get-JvBytes $record
        $horseNo = Convert-JvInt (Get-JvField $bytes 29 2)
        $abnormalCode = Get-JvField $bytes 332 1
        $confirmedRank = Convert-JvInt (Get-JvField $bytes 335 2)

        if ($null -ne $horseNo -and $horseNo -gt 0) {
            if ($confirmedRank -in @(1, 2, 3)) {
                $ranks[[string] $confirmedRank] += @($horseNo)
            }
            elseif ($abnormalCode -in @('1', '2', '3')) {
                $withdrawals += $horseNo
            }
        }
}

if ($null -ne $latestHrRecord) {
    $bytes = Get-JvBytes $latestHrRecord
    Add-HrPayoutRows $payouts $bytes

    if ($payouts['sanrentan'].Count -gt 0) {
        $orderedTop3 = ([string] $payouts['sanrentan'][0]['selection_key']).Split('>')
        if ($orderedTop3.Count -eq 3) {
            $ranks['1'] = @([int] $orderedTop3[0])
            $ranks['2'] = @([int] $orderedTop3[1])
            $ranks['3'] = @([int] $orderedTop3[2])
        }
    }
    elseif ($payouts['umatan'].Count -gt 0 -and $payouts['sanrenpuku'].Count -gt 0) {
        $orderedTop2 = ([string] $payouts['umatan'][0]['selection_key']).Split('>')
        $top3Set = ([string] $payouts['sanrenpuku'][0]['selection_key']).Split('-')
        if ($orderedTop2.Count -eq 2 -and $top3Set.Count -eq 3) {
            $third = @($top3Set | Where-Object { $_ -notin $orderedTop2 })[0]
            $ranks['1'] = @([int] $orderedTop2[0])
            $ranks['2'] = @([int] $orderedTop2[1])
            $ranks['3'] = @([int] $third)
        }
    }
}

$payload = [ordered]@{
    ranks = [ordered]@{
        '1' = @($ranks['1'] | Sort-Object -Unique)
        '2' = @($ranks['2'] | Sort-Object -Unique)
        '3' = @($ranks['3'] | Sort-Object -Unique)
    }
    withdrawals = @($withdrawals | Sort-Object -Unique)
    payouts = $payouts
}

$json = $payload | ConvertTo-Json -Depth 8
Write-Output "Matched SE records: $($matchedSeRecords.Count)"
Write-Output "Used SE records: $($latestSeRecords.Count)"
Write-Output "Matched HR records: $($matchedHrRecords.Count)"
Write-Output "Used HR records: $(if ($null -ne $latestHrRecord) { 1 } else { 0 })"
Write-Output $json

if ($latestSeRecords.Count -eq 0 -or $null -eq $latestHrRecord) {
    throw 'Target SE/HR records were not found. Check RaceDate, CourseCode, RaceNo, or wait until JRA-VAN data is available.'
}

if ($DryRun) {
    Write-Output 'Dry run only. No API request was sent.'
    exit 0
}

$uri = ($ApiBaseUrl.TrimEnd('/') + "/api/jra-van/races/$AppRaceId/settlement")
$headers = @{
    Authorization = "Bearer $ApiToken"
}

Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -ContentType 'application/json; charset=utf-8' -Body $json | ConvertTo-Json -Depth 8
