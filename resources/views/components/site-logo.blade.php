@props(['variant' => 'guest'])

@php
    $isSummerSite = config('domain.site.type') === 'summer';
@endphp

@if ($isSummerSite)
    @if ($variant === 'nav')
        <div class="shrink-0 rounded-md border border-sky-200 bg-white p-1 shadow-sm">
            <img src="{{ asset('summer_icon.png') }}" alt="夏競馬バトル" class="h-12 w-12 rounded" />
        </div>
    @else
        <img src="{{ asset('summer_icon.png') }}" alt="夏競馬バトル" class="h-40 w-40 rounded-3xl sm:h-44 sm:w-44" />
    @endif
@else
    @if ($variant === 'nav')
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-14 sm:h-20 lg:h-24 w-auto shrink-0" />
    @else
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    @endif
@endif
