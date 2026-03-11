<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" translate="no" class="notranslate">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="Content-Language" content="ja">
        <meta name="google" content="notranslate">

        <title>初心者G1馬券バトル</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <style>
            @media (max-width: 639px) {
                .mobile-scroll-fade-host {
                    position: relative;
                }

                .mobile-scroll-fade {
                    position: absolute;
                    inset-block: 0;
                    z-index: 10;
                    width: 3.5rem;
                    pointer-events: none;
                }

                .mobile-scroll-fade-left {
                    left: 0;
                    background: linear-gradient(to right, rgba(148, 163, 184, 0.85), rgba(203, 213, 225, 0.55), rgba(255, 255, 255, 0));
                }

                .mobile-scroll-fade-right {
                    right: 0;
                    background: linear-gradient(to left, rgba(148, 163, 184, 0.85), rgba(203, 213, 225, 0.55), rgba(255, 255, 255, 0));
                }

                .mobile-scroll-fade-arrow {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    border-radius: 9999px;
                    padding: 0.125rem 0.375rem;
                    font-size: 10px;
                    font-weight: 700;
                    line-height: 1;
                    color: #fff;
                    background: rgba(51, 65, 85, 0.8);
                }

                .mobile-scroll-fade-left .mobile-scroll-fade-arrow {
                    left: 0.25rem;
                }

                .mobile-scroll-fade-right .mobile-scroll-fade-arrow {
                    right: 0.25rem;
                }
            }
        </style>

    </head>
    @php
        $bodyClasses = 'font-sans antialiased';
        if (request()->routeIs('bet.build.mode')) {
            $bodyClasses .= ' bet-build-page';
        }
    @endphp

    <body class="{{ $bodyClasses }}">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <script>
            (() => {
                const setupMobileTableFades = () => {
                    const wrappers = Array.from(document.querySelectorAll('.overflow-x-auto'))
                        .filter((el) => el.querySelector('table') !== null)
                        .filter((el) => el.dataset.mobileFadeReady !== '1');

                    wrappers.forEach((scrollEl) => {
                        scrollEl.dataset.mobileFadeReady = '1';

                        const host = document.createElement('div');
                        host.className = 'mobile-scroll-fade-host';
                        scrollEl.parentNode?.insertBefore(host, scrollEl);
                        host.appendChild(scrollEl);

                        const left = document.createElement('div');
                        left.className = 'mobile-scroll-fade mobile-scroll-fade-left hidden sm:hidden';
                        left.innerHTML = '<span class="mobile-scroll-fade-arrow">←</span>';

                        const right = document.createElement('div');
                        right.className = 'mobile-scroll-fade mobile-scroll-fade-right hidden sm:hidden';
                        right.innerHTML = '<span class="mobile-scroll-fade-arrow">→</span>';

                        host.appendChild(left);
                        host.appendChild(right);

                        const update = () => {
                            const max = scrollEl.scrollWidth - scrollEl.clientWidth;
                            if (max <= 1) {
                                left.classList.add('hidden');
                                right.classList.add('hidden');
                                return;
                            }
                            left.classList.toggle('hidden', scrollEl.scrollLeft <= 1);
                            right.classList.toggle('hidden', scrollEl.scrollLeft >= max - 1);
                        };

                        scrollEl.addEventListener('scroll', update, {
                            passive: true
                        });
                        window.addEventListener('resize', update);
                        requestAnimationFrame(update);
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', setupMobileTableFades);
                } else {
                    setupMobileTableFades();
                }
            })();
        </script>
    </body>
</html>
