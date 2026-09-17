@php
    $isAttendee = auth()->check() && auth()->user()->hasRole('attendee');
    $dashboardHref = $isAttendee ? route('attendee.dashboard') : route('dashboard');
    $dashboardCurrent = $isAttendee ? request()->routeIs('attendee.dashboard') : request()->routeIs('dashboard');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-[#fdf8f0] dark:bg-stone-900">
    <flux:header container class="border-b border-amber-100 bg-white dark:border-stone-800 dark:bg-stone-900">
        <flux:sidebar.toggle class="mr-2 lg:hidden" icon="bars-2" inset="left" />

        <x-app-logo :href="$dashboardHref" wire:navigate />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item icon="layout-grid" :href="$dashboardHref" :current="$dashboardCurrent" wire:navigate>
                {{ __('Dashboard') }}
            </flux:navbar.item>
            @if (! $isAttendee)
                <flux:navbar.item
                    icon="calendar"
                    :href="route('organizer.event')"
                    :current="request()->routeIs('organizer.event')"
                    wire:navigate
                >
                    {{ __('Events') }}
                </flux:navbar.item>
            @endif
        </flux:navbar>

        <flux:spacer />

        <button type="button" onclick="(() => { const isDark = document.documentElement.classList.contains('dark'); const next = isDark ? 'light' : 'dark'; window.Flux.applyAppearance(next); try { window.Flux.appearance = next; } catch {} })()" class="inline-flex size-9 items-center justify-center rounded-full border border-amber-200 bg-white text-stone-600 shadow-sm hover:bg-amber-50 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300" aria-label="Toggle theme">
            <svg class="size-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
            <svg class="hidden size-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>

        <flux:navbar class="me-1.5 space-x-0.5 py-0! rtl:space-x-reverse">
            <flux:tooltip :content="__('Search')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 !h-10"
                    icon="magnifying-glass"
                    href="#"
                    :label="__('Search')"
                />
            </flux:tooltip>
            <flux:tooltip :content="__('Repository')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 h-10 max-lg:hidden"
                    icon="folder-git-2"
                    href="https://github.com/laravel/livewire-starter-kit"
                    target="_blank"
                    :label="__('Repository')"
                />
            </flux:tooltip>
            <flux:tooltip :content="__('Documentation')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 h-10 max-lg:hidden"
                    icon="book-open-text"
                    href="https://laravel.com/docs/starter-kits#livewire"
                    target="_blank"
                    :label="__('Documentation')"
                />
            </flux:tooltip>
        </flux:navbar>

        <x-desktop-user-menu />
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar
        collapsible="mobile"
        sticky
        class="border-e border-amber-100 bg-white lg:hidden dark:border-stone-800 dark:bg-stone-900"
    >
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" :href="$dashboardHref" wire:navigate />
            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')">
                <flux:sidebar.item icon="layout-grid" :href="$dashboardHref" :current="$dashboardCurrent" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                @if (! $isAttendee)
                    <flux:sidebar.item
                        icon="calendar"
                        :href="route('organizer.event')"
                        :current="request()->routeIs('organizer.event')"
                        wire:navigate
                    >
                        {{ __('Events') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item
                icon="folder-git-2"
                href="https://github.com/laravel/livewire-starter-kit"
                target="_blank"
            >
                {{ __('Repository') }}
            </flux:sidebar.item>
            <flux:sidebar.item
                icon="book-open-text"
                href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank"
            >
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>
</html>
