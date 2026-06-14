@props(['variant' => 'guest'])

@php
    $isSummerSite = config('domain.site.type') === 'summer';
@endphp

@if ($isSummerSite)
    @if ($variant === 'nav')
        <img src="{{ asset('summer_icon.png') }}" alt="夏競馬バトル" class="h-14 sm:h-20 lg:h-24 w-auto shrink-0" />
    @else
        <img src="{{ asset('summer_icon.png') }}" alt="夏競馬バトル" class="h-auto w-full max-w-xs" />
    @endif
@else
    @if ($variant === 'nav')
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-14 sm:h-20 lg:h-24 w-auto shrink-0" />
    @else
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    @endif
@endif
