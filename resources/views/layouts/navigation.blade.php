@php
    $currentRaceId = session('bet.current_race_id');
    $cartCount = $currentRaceId ? count(session("bet_cart.$currentRaceId.items", [])) : 0;
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $raceSelectRoute = $isAdmin ? route('races.index') : route('bet.races');
    $isStatsPage = request()->routeIs('stats.*');
    $isRaceSelectPage = request()->routeIs('races.*')
        || request()->routeIs('bet.races')
        || request()->routeIs('bet.types')
        || request()->routeIs('bet.modes')
        || request()->routeIs('bet.build.mode');
    $isCartPage = request()->routeIs('bet.cart');
@endphp

<nav class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-end h-16 items-center gap-6">
            @auth
                <div class="text-sm text-gray-600">
                    ログイン中: {{ auth()->user()->display_name ?: auth()->user()->name }}
                </div>
            @endauth

            <a href="{{ route('stats.index') }}"
                class="text-sm font-medium text-gray-700 hover:text-gray-900 transition {{ $isStatsPage ? 'underline underline-offset-4' : '' }}">
                成績
            </a>

            <a href="{{ $raceSelectRoute }}"
                class="text-sm font-medium text-gray-700 hover:text-gray-900 transition {{ $isRaceSelectPage ? 'underline underline-offset-4' : '' }}">
                馬券購入
            </a>

            @if ($currentRaceId)
                <a href="{{ route('bet.cart', $currentRaceId) }}"
                    class="text-sm font-medium text-blue-600 hover:text-blue-800 transition {{ $isCartPage ? 'underline underline-offset-4' : '' }}">
                    カートを見る
                    @if ($cartCount > 0)
                        <span class="ml-1 text-xs font-semibold text-blue-700">
                            ({{ $cartCount }})
                        </span>
                    @endif
                </a>
            @endif



            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="px-4 py-2 bg-gray-800 text-white font-bold rounded-md
               hover:bg-black transition">
                    ログアウト
                </button>
            </form>

        </div>
    </div>

    <div class="border-t border-amber-200 bg-amber-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 text-xs text-amber-800">
            初回アクセス時はサーバー起動のため、5〜20秒ほどお待ちいただく場合があります。
        </div>
    </div>
</nav>
