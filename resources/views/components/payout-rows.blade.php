@php
	// 既存データが空なら、空の1行分を初期表示
	$rows = $existingRows;
	if (empty($rows) || (is_array($rows) && count($rows) === 0)) {
		$rows = [
			[
				'selection_key' => '',
				'payout_per_100' => '',
				'popularity' => '',
			]
		];
	}
@endphp

@foreach($rows as $i => $row)
	<div class="flex gap-2 mb-2">
		<input type="text" name="payouts[{{ $betType }}][{{ $i }}][selection_key]" value="{{ old('payouts.' . $betType . '.' . $i . '.selection_key', $row['selection_key'] ?? '') }}" placeholder="組番" class="border rounded px-2 py-1 w-24">
		<input type="text" name="payouts[{{ $betType }}][{{ $i }}][payout_per_100]" value="{{ old('payouts.' . $betType . '.' . $i . '.payout_per_100', $row['payout_per_100'] ?? '') }}" placeholder="払戻金" class="border rounded px-2 py-1 w-24">
		<input type="text" name="payouts[{{ $betType }}][{{ $i }}][popularity]" value="{{ old('payouts.' . $betType . '.' . $i . '.popularity', $row['popularity'] ?? '') }}" placeholder="人気" class="border rounded px-2 py-1 w-16">
	</div>
@endforeach
