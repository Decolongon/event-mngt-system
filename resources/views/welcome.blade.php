<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="color-scheme" content="light dark" />
    <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico?v=2" sizes="any" />
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <script>
        (() => {
            // Sync legacy 'theme' key to flux.appearance for seamless migration
            const legacy = localStorage.getItem('theme');
            if (legacy && !localStorage.getItem('flux.appearance')) {
                window.Flux.applyAppearance(legacy);
            }
            const syncLegacy = (appearance) => {
                // Keep legacy key in sync so old code still works
                if (appearance === 'system') localStorage.removeItem('theme');
                else localStorage.setItem('theme', appearance);
            };
            const updateToggle = () => {
                const btn = document.getElementById('theme-toggle');
                if (!btn) return;
                const isDark = document.documentElement.classList.contains('dark');
                btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
                const sun = btn.querySelector('[data-icon="sun"]');
                const moon = btn.querySelector('[data-icon="moon"]');
                if (sun) sun.classList.toggle('hidden', !isDark);
                if (moon) moon.classList.toggle('hidden', isDark);
            };
            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('theme-toggle');
                if (!btn) return;
                updateToggle();
                // Observe class changes (flux applies via Alpine effect)
                const obs = new MutationObserver(updateToggle);
                obs.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
                btn.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.contains('dark');
                    const next = isDark ? 'light' : 'dark';
                    window.Flux.applyAppearance(next);
                    syncLegacy(next);
                    // Also keep Flux reactive state in sync if available
                    if (window.Flux && typeof window.Flux.appearance !== 'undefined') {
                        try { window.Flux.appearance = next; } catch {}
                    }
                    updateToggle();
                });
            });
            // Update on system preference change when in system mode
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if ((localStorage.getItem('flux.appearance') || 'system') === 'system') {
                    setTimeout(updateToggle, 50);
                }
            });
        })();
    </script>
</head>
<body class="min-h-screen bg-[#fdfaf6] font-sans text-stone-800 antialiased cater-pattern dark:bg-[#1c1917] dark:text-stone-100">
    {{-- Top accent bar --}}
    <div class="h-1 w-full bg-gradient-to-r from-amber-600 via-orange-600 to-amber-500"></div>

    {{-- Header --}}
    <header class="sticky top-0 z-30 w-full border-b border-amber-100/70 bg-[#fdfaf6]/85 backdrop-blur-md dark:border-stone-800 dark:bg-[#1c1917]/85">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 sm:py-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 sm:gap-3 text-sm font-semibold tracking-tight">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white shadow-sm shadow-amber-600/20 dark:bg-amber-500">
                    <x-app-logo-icon class="size-5" />
                </span>
                <span class="font-display truncate text-[15px] font-semibold tracking-tight sm:text-[17px]">{{ config('app.name', 'Laravel') }}</span>
                <span class="hidden rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-widest text-amber-700 sm:inline-flex dark:bg-amber-500/15 dark:text-amber-400">Events</span>
            </a>

            <div class="flex items-center gap-1.5 sm:gap-2">
                {{-- Theme toggle --}}
                <button id="theme-toggle" type="button" aria-label="Toggle theme" class="inline-flex size-9 items-center justify-center rounded-full border border-amber-200 bg-white text-stone-600 shadow-sm hover:bg-amber-50 hover:text-stone-900 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                    <svg data-icon="moon" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
                    <svg data-icon="sun" class="hidden size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                </button>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-1.5 sm:gap-2 text-sm">
                        @auth
                            <a href="{{ auth()->user()->hasRole('attendee') ? route('attendee.dashboard') : route('dashboard') }}" class="rounded-full bg-amber-600 px-4 py-2 text-xs font-medium text-white shadow-sm shadow-amber-600/20 hover:bg-amber-700 sm:px-5 sm:text-sm dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400">
                                {{ __('Dashboard') }}
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden rounded-full px-3 py-2 text-xs font-medium text-stone-600 hover:text-stone-900 sm:inline-flex sm:px-4 sm:text-sm dark:text-stone-400 dark:hover:text-stone-100">
                                {{ __('Log in') }}
                            </a>
                            <a href="{{ route('login') }}" class="inline-flex rounded-full border border-stone-300 bg-white px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50 sm:hidden dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200">
                                {{ __('Log in') }}
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-full bg-amber-600 px-4 py-2 text-xs font-medium text-white shadow-sm shadow-amber-600/20 hover:bg-amber-700 sm:px-5 sm:text-sm dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400">
                                    {{ __('Register') }}
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </div>
    </header>

    {{-- Main --}}
    <main class="mx-auto max-w-6xl px-4 pb-16 pt-6 sm:px-6 sm:pt-8 lg:pt-10">
        {{-- Hero - two column generalized event layout --}}
        <div class="grid items-center gap-8 sm:gap-10 lg:grid-cols-2 lg:gap-12">
            {{-- Left copy --}}
            <div class="text-center lg:text-left">
                {{-- Eyebrow --}}
                <div class="inline-flex max-w-full flex-wrap items-center justify-center gap-1.5 rounded-full border border-amber-200 bg-white px-3 py-1.5 text-[11px] font-medium leading-none text-amber-700 shadow-sm sm:gap-2 sm:text-xs dark:border-amber-900/30 dark:bg-stone-800 dark:text-amber-300">
                    <span class="flex size-2 shrink-0 rounded-full bg-amber-600 dark:bg-amber-500"></span>
                    <span class="truncate">{{ __('Conferences • Concerts • Workshops • Meetups • Festivals') }}</span>
                </div>

                <h1 class="font-display mt-4 text-[28px] font-semibold leading-[0.95] tracking-tight text-stone-900 sm:mt-5 sm:text-4xl lg:text-[44px] dark:text-stone-50">
                    <span class="block">{{ __('Discover') }} <span class="font-display italic font-normal text-amber-600 dark:text-amber-500">{{ __('amazing') }}</span></span>
                    <span class="block">{{ __('events near you.') }}</span>
                </h1>

                <p class="mx-auto mt-3 max-w-md text-[14px] leading-6 text-stone-600 sm:mt-4 sm:text-[15px] lg:mx-0 dark:text-stone-400">
                    {{ __('From music festivals and tech conferences to workshops, community meetups and private gatherings — find published events, choose your tickets, and book instantly. Secure payments, instant confirmation.') }}
                </p>

                <div class="mt-6 flex flex-col items-stretch justify-center gap-2.5 sm:mt-8 sm:flex-row sm:items-center lg:justify-start">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-white shadow-sm shadow-amber-600/20 hover:bg-amber-700 sm:w-auto sm:px-7 dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400">
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>{{ __('Go to Dashboard') }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-amber-600 px-6 py-3 text-sm font-medium text-white shadow-sm shadow-amber-600/20 hover:bg-amber-700 sm:w-auto sm:px-7 dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400">
                            <span>{{ __('Log in to book tickets') }}</span>
                            <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-full border border-stone-300 bg-white px-6 py-3 text-sm font-medium text-stone-800 hover:bg-stone-50 sm:w-auto sm:px-7 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:hover:bg-stone-700">
                                {{ __('Create account') }}
                            </a>
                        @endif
                    @endauth
                </div>

                <p class="mt-3 flex flex-wrap items-center justify-center gap-1.5 text-[11px] text-stone-500 sm:mt-4 sm:gap-2 sm:text-xs lg:justify-start dark:text-stone-500">
                    <span class="inline-flex items-center gap-1"><span class="size-1.5 rounded-full bg-emerald-500"></span> {{ __('Trusted by 2,500+ organizers') }}</span>
                    <span class="hidden text-stone-300 sm:inline dark:text-stone-700">•</span>
                    <span class="text-center sm:text-left">{{ __('Already have an account? Log in — or create a new one in seconds.') }}</span>
                </p>

                {{-- Stats row --}}
                <div class="mt-6 flex flex-col items-center justify-center gap-4 border-t border-amber-100 pt-5 sm:flex-row sm:gap-6 lg:justify-start dark:border-stone-800">
                    <div class="flex -space-x-2">
                        <img src="https://i.pravatar.cc/100?img=11" alt="" class="size-7 rounded-full border-2 border-white object-cover sm:size-8 dark:border-stone-900" />
                        <img src="https://i.pravatar.cc/100?img=22" alt="" class="size-7 rounded-full border-2 border-white object-cover sm:size-8 dark:border-stone-900" />
                        <img src="https://i.pravatar.cc/100?img=33" alt="" class="size-7 rounded-full border-2 border-white object-cover sm:size-8 dark:border-stone-900" />
                        <span class="flex size-7 items-center justify-center rounded-full border-2 border-white bg-stone-900 text-[10px] font-semibold text-white sm:size-8 dark:border-stone-900">+2k</span>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="flex items-center justify-center gap-0.5 text-amber-500 sm:justify-start">
                            <svg class="size-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <svg class="size-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <svg class="size-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <svg class="size-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <svg class="size-3.5 fill-amber-500" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span class="ml-1 text-xs font-semibold text-stone-700 dark:text-stone-300">4.9/5</span>
                        </div>
                        <p class="text-[11px] text-stone-500 sm:text-xs dark:text-stone-500">{{ __('from 1,200+ happy attendees') }}</p>
                    </div>
                    <div class="hidden h-8 w-px bg-amber-100 sm:block dark:bg-stone-800"></div>
                    <div class="text-center sm:text-left">
                        <p class="text-sm font-semibold leading-none text-stone-900 dark:text-stone-100">500+</p>
                        <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-500">{{ __('events published') }}</p>
                    </div>
                </div>
            </div>

            {{-- Right visual - generalized events --}}
            <div class="relative mx-auto w-full max-w-lg lg:mx-0 lg:max-w-none">
                <div class="relative overflow-hidden rounded-[20px] border border-amber-200/60 bg-white shadow-xl shadow-amber-900/10 sm:rounded-[28px] dark:border-stone-800 dark:bg-stone-900">
                    <img src="https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?q=80&w=1200&auto=format&fit=crop" alt="Crowd at vibrant live event" class="h-[280px] w-full object-cover sm:h-[340px] lg:h-[440px]" />
                    {{-- Gradient overlay for text readability --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-stone-900/60 via-transparent to-transparent"></div>
                    {{-- Top badge --}}
                    <div class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold text-stone-800 shadow-sm backdrop-blur-sm dark:bg-stone-800/95 dark:text-stone-100">
                        <span class="size-2 rounded-full bg-emerald-500"></span> {{ __('Live now • 3 events today') }}
                    </div>
                    {{-- Floating card --}}
                    <div class="absolute inset-x-3 bottom-3 rounded-2xl border border-white/60 bg-white/95 p-3 shadow-lg backdrop-blur-sm sm:inset-x-4 sm:bottom-4 sm:p-4 dark:border-stone-700 dark:bg-stone-800/95">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white dark:bg-amber-500">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 10h18M5 10v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9"/></svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold leading-none text-stone-900 dark:text-stone-100">{{ __('Summer Festival 2026') }}</p>
                                    <p class="mt-1 truncate text-xs text-stone-500 dark:text-stone-400">{{ __('Manila • Music • Conference • Workshop') }}</p>
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">{{ __('Open') }}</span>
                        </div>
                        <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-stone-500 sm:mt-3 dark:text-stone-400">
                            <span class="inline-flex items-center gap-1"><svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> {{ __('Instant booking') }}</span>
                            <span class="hidden text-stone-300 sm:inline dark:text-stone-600">•</span>
                            <span class="inline-flex items-center gap-1"><svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> {{ __('Secure payment') }}</span>
                            <span class="hidden text-stone-300 sm:inline dark:text-stone-600">•</span>
                            <span class="inline-flex items-center gap-1"><svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> {{ __('Any event type') }}</span>
                        </div>
                    </div>
                </div>
                {{-- Decorative blurs - hidden on mobile for performance --}}
                <div class="pointer-events-none absolute -right-3 -top-3 hidden size-24 rounded-full bg-amber-100 blur-2xl lg:block dark:bg-amber-900/20"></div>
                <div class="pointer-events-none absolute -left-4 bottom-10 hidden size-20 rounded-full bg-orange-100 blur-2xl lg:block dark:bg-orange-900/20"></div>
            </div>
        </div>

        {{-- Feature row - generalized for any events --}}
        <div class="mt-8 grid grid-cols-1 gap-3 sm:mt-12 sm:gap-4 sm:grid-cols-3">
            <div class="group rounded-2xl border border-amber-100 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-900/30">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 110-16 8 8 0 010 16z"/></svg>
                </div>
                <p class="font-display mt-3 text-sm font-semibold text-stone-900 dark:text-stone-100">{{ __('Discover Any Event') }}</p>
                <p class="mt-1 text-xs leading-5 text-stone-600 dark:text-stone-400">{{ __('Conferences, concerts, workshops, festivals, meetups — all published events in one place.') }}</p>
            </div>
            <div class="group rounded-2xl border border-amber-100 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-900/30">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m3-6a3 3 0 11-6 0 3 3 0 016 0zM4 16a3 3 0 016 0 3 3 0 01-6 0zm9 4a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="font-display mt-3 text-sm font-semibold text-stone-900 dark:text-stone-100">{{ __('Book Instantly') }}</p>
                <p class="mt-1 text-xs leading-5 text-stone-600 dark:text-stone-400">{{ __('Pick ticket type, choose quantity and pay securely — card, GCash, Maya or GrabPay.') }}</p>
            </div>
            <div class="group rounded-2xl border border-amber-100 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5 dark:border-stone-800 dark:bg-stone-900">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-900/30">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="font-display mt-3 text-sm font-semibold text-stone-900 dark:text-stone-100">{{ __('Get Confirmed') }}</p>
                <p class="mt-1 text-xs leading-5 text-stone-600 dark:text-stone-400">{{ __('Receive your booking reference instantly via email. Just show up and enjoy.') }}</p>
            </div>
        </div>

        {{-- Event categories teaser --}}
        <div class="mt-6 grid grid-cols-2 gap-2 sm:mt-8 sm:grid-cols-4 sm:gap-3">
            <div class="rounded-xl border border-amber-100 bg-white px-3 py-3 text-center dark:border-stone-800 dark:bg-stone-900">
                <p class="text-lg">🎵</p><p class="mt-1 text-xs font-semibold text-stone-700 dark:text-stone-300">{{ __('Music & Concerts') }}</p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-white px-3 py-3 text-center dark:border-stone-800 dark:bg-stone-900">
                <p class="text-lg">💼</p><p class="mt-1 text-xs font-semibold text-stone-700 dark:text-stone-300">{{ __('Business & Tech') }}</p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-white px-3 py-3 text-center dark:border-stone-800 dark:bg-stone-900">
                <p class="text-lg">🎓</p><p class="mt-1 text-xs font-semibold text-stone-700 dark:text-stone-300">{{ __('Workshops & Learning') }}</p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-white px-3 py-3 text-center dark:border-stone-800 dark:bg-stone-900">
                <p class="text-lg">🎉</p><p class="mt-1 text-xs font-semibold text-stone-700 dark:text-stone-300">{{ __('Community & Social') }}</p>
            </div>
        </div>

        {{-- Hosting CTA bar --}}
        <div class="mt-6 rounded-2xl border border-amber-200/70 bg-gradient-to-r from-amber-600 to-orange-600 px-4 py-4 text-white shadow-sm sm:px-6 dark:border-amber-900/30">
            <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-center text-sm font-medium leading-snug sm:text-left">✦ {{ __('Hosting anything? From a small workshop to a large festival — create your event and let guests book in seconds.') }}</p>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-white px-5 py-2.5 text-xs font-semibold text-amber-700 hover:bg-amber-50 sm:px-5 sm:py-2 dark:bg-stone-900 dark:text-amber-300 dark:hover:bg-stone-800">{{ __('Create an event →') }}</a>
                @endif
            </div>
        </div>
    </main>

    <footer class="border-t border-amber-100 bg-white/60 py-6 text-center text-xs text-stone-500 backdrop-blur-sm sm:py-8 dark:border-stone-800 dark:bg-stone-900/60 dark:text-stone-500">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <p class="font-display text-sm font-medium text-stone-700 dark:text-stone-300">© {{ date('Y') }} {{ config('app.name', 'Laravel') }} — {{ __('Events for everyone') }}</p>
            <p class="mt-1 leading-relaxed">{{ __('All rights reserved.') }} · {{ __('Made with') }} <span class="text-amber-600">♥</span> {{ __('for organizers and attendees everywhere') }}</p>
        </div>
    </footer>
</body>
</html>
