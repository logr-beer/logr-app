@php $clipId = 'glass-filled-' . uniqid(); @endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    <defs>
        <clipPath id="{{ $clipId }}">
            <path d="M9 21h6a1 1 0 0 0 1 -1v-3.625c0 -1.397 .29 -2.775 .845 -4.025l.31 -.7c.556 -1.25 .845 -2.253 .845 -3.65v-4a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1v4c0 1.397 .29 2.4 .845 3.65l.31 .7a9.931 9.931 0 0 1 .845 4.025v3.625a1 1 0 0 0 1 1" />
        </clipPath>
    </defs>
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <g clip-path="url(#{{ $clipId }})">
        <rect x="4" y="3" width="16" height="4" fill="white" stroke="none" />
        <rect x="4" y="7" width="16" height="16" fill="#f59e0b" stroke="none" />
        <circle cx="10" cy="13" r="0.7" fill="rgba(255,255,255,0.3)" stroke="none" />
        <circle cx="13" cy="11" r="0.5" fill="rgba(255,255,255,0.3)" stroke="none" />
        <circle cx="11.5" cy="15" r="0.4" fill="rgba(255,255,255,0.3)" stroke="none" />
    </g>
    <path d="M9 21h6a1 1 0 0 0 1 -1v-3.625c0 -1.397 .29 -2.775 .845 -4.025l.31 -.7c.556 -1.25 .845 -2.253 .845 -3.65v-4a1 1 0 0 0 -1 -1h-10a1 1 0 0 0 -1 1v4c0 1.397 .29 2.4 .845 3.65l.31 .7a9.931 9.931 0 0 1 .845 4.025v3.625a1 1 0 0 0 1 1" />
    <path d="M6 8h12" />
</svg>
