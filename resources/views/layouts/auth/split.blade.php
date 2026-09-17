<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-[#fdf8f0] antialiased dark:bg-stone-950">
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-stone-800">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-700 via-orange-600 to-amber-800"></div>
            <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 22px 22px;"></div>
            <img src="https://images.unsplash.com/photo-1555244162-803834f70033?q=80&w=800&auto=format&fit=crop" alt="Catering spread" class="absolute inset-0 h-full w-full object-cover mix-blend-overlay opacity-30" />
            <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/15 backdrop-blur-sm ring-1 ring-white/20">
                    <x-app-logo-icon class="me-0 h-7 fill-current text-white" />
                </span>
                <span class="ml-2 font-display font-semibold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
                <span class="ml-2 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-widest backdrop-blur-sm">Catering</span>
            </a>

            <div class="relative z-20 mt-auto">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-sm ring-1 ring-white/20">
                    <span class="size-1.5 rounded-full bg-white"></span> Crafted for memorable gatherings
                </div>
                <blockquote class="space-y-2">
                    <p class="font-display text-xl leading-relaxed text-white/95">&ldquo;Great catering is not just food — it is the warmth that brings people together around one table.&rdquo;</p>
                    <footer><p class="text-sm font-medium text-white/70">— The Catered Table</p></footer>
                </blockquote>
            </div>
        </div>
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <a
                    href="{{ route('home') }}"
                    class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden"
                    wire:navigate
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>

                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                {{ $slot }}
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
