<x-guest-layout>
    <div class="space-y-6 text-sm text-gray-700">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">プライバシーポリシー</h1>
            <p class="mt-2 text-xs text-gray-500">最終更新日: 2026年3月3日</p>
        </div>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">事前案内</h2>
            <p>配信者・視聴者の個人情報保護の観点から、ログインは Discord OAuth のみとしています。</p>
            <p>（Discord OAuth：Discordアカウントでログインする仕組み）</p>
            <p>当アプリではメールアドレス／ID・パスワードは使用・保存しません。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">1. Discord OAuth で取得する情報</h2>
            <p>Discord OAuth により、当アプリは Discord からユーザー識別のための情報（例：ユーザーID、表示名等）を受け取ります。</p>
            <p>受け取った情報はログイン・アカウント管理の目的で利用し、目的外の利用はしません。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">2. アクセスログについて</h2>
            <p>一方で、サーバ運用上の仕組みとして、アクセスログに以下が記録される場合があります。</p>
            <ul class="list-disc space-y-1 pl-5">
                <li>IPアドレス</li>
                <li>User-Agent（ユーザーエージェント／ブラウザ・OSの種類が分かる文字列）</li>
                <li>アクセス日時</li>
            </ul>
            <p>これらは障害対応・不正アクセス対策などの運用目的で利用し、目的外の利用はしません。</p>
            <p>※アクセスログはサーバ側で日ごとに分割して管理しており、現状は約14日分を目安に、古いものから順次圧縮・削除されます。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">3. 端末内データ・位置情報</h2>
            <p>また、当アプリは端末内データ（写真・連絡先・ファイル等）に触れる必要がある機能を提供していません。</p>
            <p>位置情報（GPS）についても、許可を求める機能はありません。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">4. 代理入力のご案内</h2>
            <p>当アプリのご利用に抵抗がある場合は、主催者（いぬまみや）または管理者（ねこ）が代理で情報を入力することも可能です。</p>
            <p>Discord にて気軽にご連絡ください。</p>
            <p>よろしくお願いいたします。</p>
        </section>

        <a href="{{ route('login', [], false) }}" class="inline-block text-sm text-gray-600 underline hover:text-gray-900">
            ログイン画面に戻る
        </a>
    </div>
</x-guest-layout>
