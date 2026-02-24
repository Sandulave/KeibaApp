@php
    $currentRaceId = session('bet.current_race_id');
    $cartCount = 0;
    if ($currentRaceId) {
        $currentCart = session("bet_cart_{$currentRaceId}", []);
        $cartCount = is_array($currentCart) ? count($currentCart['items'] ?? []) : 0;
    }
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $raceSelectRoute = $isAdmin ? route('races.index') : route('bet.races');
    $isStatsPage = request()->routeIs('stats.index');
    $isPersonalStatsPage = request()->is('stats/users/2');
    $isRaceSelectPage = request()->routeIs('races.*')
        || request()->routeIs('bet.races')
        || request()->routeIs('bet.types')
        || request()->routeIs('bet.modes')
        || request()->routeIs('bet.build.mode');
    $isCartPage = request()->routeIs('bet.cart');
@endphp

<nav class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-3 sm:h-16 sm:py-0 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            @auth
                <div class="text-xs sm:text-sm text-gray-600 break-all sm:mr-auto">
                    ログイン中: {{ auth()->user()->display_name ?: auth()->user()->name }}
                </div>
            @endauth

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 sm:gap-6">
                <a href="{{ route('stats.index') }}"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900 transition {{ $isStatsPage ? 'underline underline-offset-4' : '' }}">
                    成績ランキング
                </a>

                <a href="{{ route('stats.users.show', 2) }}"
                    class="text-sm font-medium text-gray-700 hover:text-gray-900 transition {{ $isPersonalStatsPage ? 'underline underline-offset-4' : '' }}">
                    個人成績
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
                        class="px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-800 text-white font-bold rounded-md hover:bg-black transition text-sm">
                        ログアウト
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
