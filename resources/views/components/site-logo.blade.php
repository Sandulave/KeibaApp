@props(['variant' => 'guest'])

@php
    $isSummerSite = config('domain.site.type') === 'summer';
@endphp

@if ($isSummerSite)
    @if ($variant === 'nav')
        <div class="shrink-0 inline-flex items-center gap-2 rounded-md border border-sky-200 bg-white px-3 py-2 text-lg sm:text-xl font-bold text-sky-800 shadow-sm">
            <img src="{{ asset('summer_icon.png') }}" alt="" class="h-8 w-8 rounded" aria-hidden="true" />
            <span>夏競馬</span>
        </div>
    @else
        <div class="w-full max-w-xs rounded-lg border border-sky-200 bg-sky-50 px-6 py-7 text-center shadow-sm">
            <img src="{{ asset('summer_icon.png') }}" alt="" class="mx-auto h-20 w-20 rounded-2xl" aria-hidden="true" />
            <div class="mt-3 text-4xl font-bold tracking-normal text-sky-900">夏競馬</div>
        </div>
    @endif
@else
    @if ($variant === 'nav')
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-14 sm:h-20 lg:h-24 w-auto shrink-0" />
    @else
        <img src="{{ asset('login_header.png') }}" alt="競馬アプリ ロゴ" class="h-auto w-full max-w-xs" />
    @endif
@endif
