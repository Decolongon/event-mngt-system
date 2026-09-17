{{-- Generic event icon: calendar with star/ticket --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" {{ $attributes }}>
    {{-- Calendar body --}}
    <rect x="4" y="6" width="16" height="13.5" rx="2" fill="currentColor" opacity="0.95"/>
    <rect x="4" y="6" width="16" height="5" rx="2" fill="white" opacity="0.92"/>
    <rect x="4" y="10" width="16" height="1.2" fill="white" opacity="0.92"/>
    {{-- Rings --}}
    <rect x="7.5" y="3.5" width="2" height="4" rx="1" fill="currentColor"/>
    <rect x="14.5" y="3.5" width="2" height="4" rx="1" fill="currentColor"/>
    {{-- Star center --}}
    <path d="M12 11.8l1 2 2 .3-1.5 1.45.35 2.05L12 16.6l-1.85 1 .35-2.05L9 14.1l2-.3 1-2z" fill="white"/>
    {{-- Dots --}}
    <circle cx="7.5" cy="14.5" r="0.9" fill="white" opacity="0.6"/>
    <circle cx="16.5" cy="14.5" r="0.9" fill="white" opacity="0.6"/>
    <circle cx="7.5" cy="17.2" r="0.9" fill="white" opacity="0.6"/>
    <circle cx="16.5" cy="17.2" r="0.9" fill="white" opacity="0.6"/>
</svg>
