@props(['variant' => 'guest'])

@php
    $isSummerSite = config('domain.site.type') === 'summer';
@endphp

@if ($isSummerSite)
    @if ($variant === 'nav')
        <div class="shrink-0 rounded-md border border-sky-200 bg-white px-4 py-2 text-xl sm:text-2xl font-bold text-sky-800 shadow-sm">
            夏競馬
        </div>
    @else
        <div class="w-full max-w-xs rounded-lg border border-sky-200 bg-sky-50 px-6 py-8 text-center shadow-sm">
            <div class="text-4xl font-bold tracking-normal text-sky-900">夏競馬</div>
        </div>
    @endif
@else
    @if ($variant === 'nav')
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-14 sm:h-20 lg:h-24 w-auto shrink-0" />
    @else
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    @endif
@endif
