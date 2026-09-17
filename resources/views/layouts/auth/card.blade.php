<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-[#fdf8f0] antialiased cater-pattern dark:bg-stone-950">
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
        <div class="flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-600 text-white shadow-sm shadow-amber-600/20 dark:bg-amber-500">
                    <x-app-logo-icon class="size-6 fill-current text-white dark:text-stone-900" />
                </span>
                <span class="font-display text-sm font-semibold tracking-tight text-stone-700 dark:text-stone-300">{{ config('app.name', 'Laravel') }} <span class="font-normal text-amber-700 dark:text-amber-400">— Catering</span></span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="flex flex-col gap-6">
                <div class="rounded-xl border border-amber-100 bg-white text-stone-800 shadow-sm shadow-amber-900/5 dark:border-stone-800 dark:bg-stone-900">
                    <div class="px-10 py-8">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
