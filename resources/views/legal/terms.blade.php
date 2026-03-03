<x-guest-layout>
    <div class="space-y-6 text-sm text-gray-700">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">利用規約</h1>
            <p class="mt-2 text-xs text-gray-500">最終更新日: 2026年3月3日</p>
        </div>

        <section class="space-y-2">
            <p>本サービス（以下「当アプリ」）は、競馬成績の記録・管理を目的として提供します。</p>
            <p>当アプリを利用した時点で、本規約に同意したものとみなします。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">1. 利用条件</h2>
            <p>当アプリは URL を知っている方であれば利用できます。</p>
            <p>ただし、運営は運用上の都合により、予告なく内容変更・停止・終了することがあります。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">2. 禁止事項</h2>
            <p>以下の行為を禁止します。</p>
            <ul class="list-disc space-y-1 pl-5">
                <li>不正アクセス、脆弱性の探索、当アプリの動作を妨げる行為</li>
                <li>過度な負荷を与える行為（大量アクセス、連続リクエスト等）</li>
                <li>なりすまし、第三者への権限の不正共有</li>
                <li>当アプリや他の利用者に損害・不利益を与える行為</li>
                <li>法令または公序良俗に反する行為</li>
                <li>その他、運営が不適切と判断する行為</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">3. 免責</h2>
            <ul class="list-disc space-y-1 pl-5">
                <li>当アプリは、障害・通信環境・メンテナンス等により利用できない場合があります。</li>
                <li>入力データの正確性・完全性・保存の保証は行いません。必要に応じて各自で控えを取ってください。</li>
                <li>当アプリの利用により生じた損害について、運営は責任を負いません（故意または重大な過失がある場合を除く）。</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">4. 利用制限・停止</h2>
            <p>禁止事項に該当する場合、または運営が必要と判断した場合、運営は事前の通知なく利用制限・停止等の対応を行うことがあります。</p>
        </section>

        <section class="space-y-2">
            <h2 class="font-semibold text-gray-900">5. お問い合わせ</h2>
            <p>問い合わせは Discord にて受け付けます。</p>
        </section>

        <a href="{{ route('login', [], false) }}" class="inline-block text-sm text-gray-600 underline hover:text-gray-900">
            ログイン画面に戻る
        </a>
    </div>
</x-guest-layout>
