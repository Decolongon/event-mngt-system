<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    {{-- Simple header --}}
    <header class="w-full">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-sm font-semibold tracking-tight">
                <span class="flex size-7 items-center justify-center rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-zinc-900">
                    <svg viewBox="0 0 24 24" fill="none" class="size-4" stroke="currentColor" stroke-width="2"><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 10h18M5 10v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                {{ config('app.name', 'Laravel') }}
            </a>

            @if (Route::has('login'))
                <nav class="flex items-center gap-2 text-sm">
                    @auth
                        <a href="{{ auth()->user()->hasRole('attendee') ? route('attendee.dashboard') : route('dashboard') }}" class="rounded-full bg-zinc-900 px-5 py-2 font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                            {{ __('Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full px-4 py-2 font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                            {{ __('Log in') }}
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-full bg-zinc-900 px-5 py-2 font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                                {{ __('Register') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    {{-- Main --}}
    <main class="mx-auto flex max-w-6xl flex-col items-center px-6 pb-16 pt-10 lg:pt-20">
        <div class="w-full max-w-xl text-center">
            <div class="mx-auto mb-6 flex size-12 items-center justify-center rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <svg viewBox="0 0 24 24" fill="none" class="size-6 text-zinc-900 dark:text-white" stroke="currentColor" stroke-width="1.8"><path d="M16 8l-4-4-4 4M12 4v12M20 16v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>

            <h1 class="text-3xl font-semibold tracking-tight lg:text-4xl">
                {{ config('app.name', 'Event Manager') }}
            </h1>
            <p class="mx-auto mt-3 max-w-md text-[15px] leading-6 text-zinc-500 dark:text-zinc-400">
                {{ __('Discover published events and secure your tickets instantly. Simple booking, secure payments, and instant confirmation.') }}
            </p>

            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-7 py-3 text-sm font-medium text-white hover:bg-zinc-800 sm:w-auto dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        {{ __('Go to Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-full bg-zinc-900 px-7 py-3 text-sm font-medium text-white hover:bg-zinc-800 sm:w-auto dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100">
                        {{ __('Log in to book tickets') }}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-full border border-zinc-200 bg-white px-7 py-3 text-sm font-medium text-zinc-900 hover:bg-zinc-50 sm:w-auto dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">
                            {{ __('Create account') }}
                        </a>
                    @endif
                @endauth
            </div>

            <p class="mt-6 text-xs text-zinc-400 dark:text-zinc-500">
                {{ __('Already have an account? Log in — or create a new one in seconds.') }}
            </p>

            {{-- Simple feature row --}}
            <div class="mt-10 grid grid-cols-1 gap-3 text-left sm:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm font-medium">{{ __('Browse Events') }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Find published events in one place.') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm font-medium">{{ __('Book Instantly') }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Choose tickets and pay securely.') }}</p>
                </div>
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-sm font-medium">{{ __('Get Confirmed') }}</p>
                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ __('Receive your booking reference.') }}</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-auto py-8 text-center text-xs text-zinc-400 dark:text-zinc-600">
        © {{ date('Y') }} {{ config('app.name', 'Laravel') }}. {{ __('All rights reserved.') }}
    </footer>
</body>
</html>
