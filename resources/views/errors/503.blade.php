<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>メンテナンス中 | {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg1: #f7fafc;
            --bg2: #eef2ff;
            --card: #ffffff;
            --text: #1f2937;
            --sub: #4b5563;
            --line: #e5e7eb;
            --accent: #2563eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Figtree", "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif;
            color: var(--text);
            background: radial-gradient(1200px 600px at 0% 0%, var(--bg2), var(--bg1));
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .card {
            width: min(680px, 100%);
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }
        .badge {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .04em;
            padding: 6px 10px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: clamp(24px, 4vw, 32px);
            line-height: 1.25;
        }
        p {
            margin: 0;
            line-height: 1.8;
            color: var(--sub);
        }
        .msg {
            margin-top: 12px;
            padding: 10px 12px;
            border-left: 3px solid var(--accent);
            background: #eff6ff;
            border-radius: 8px;
            color: #1e3a8a;
        }
        .meta {
            margin-top: 18px;
            font-size: 14px;
            color: #6b7280;
        }
        .actions {
            margin-top: 18px;
        }
        .admin-link {
            display: inline-block;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            padding: 8px 12px;
            border-radius: 10px;
        }
        .admin-link:hover {
            background: #dbeafe;
        }
    </style>
</head>
<body>
    @php
        $maintenance = [];
        $retry = null;

        if (app()->isDownForMaintenance()) {
            $maintenance = app()->maintenanceMode()->data() ?? [];
            $retry = $maintenance['retry'] ?? null;
        }
    @endphp

    <section class="card" role="status" aria-live="polite">
        <span class="badge">503 SERVICE UNAVAILABLE</span>
        <h1>ただいまメンテナンス中です</h1>
        <p>
            {{ config('app.name') }} は現在、機能改善のため一時的に利用できません。<br>
            しばらくしてから再度アクセスしてください。
        </p>

        @if (! empty($exception?->getMessage()))
            <p class="msg">{{ $exception->getMessage() }}</p>
        @endif

        @if (! empty($retry))
            <p class="meta">目安: {{ $retry }} 秒後に再試行してください。</p>
        @endif

        <div class="actions">
            <a class="admin-link" href="{{ route('admin.login', [], false) }}">管理者ログイン</a>
        </div>
    </section>
</body>
</html>
