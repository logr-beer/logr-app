<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{!! isset($title) ? strip_tags($title) . ' | ' . config('app.name', 'Logr') : config('app.name', 'Logr') !!}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            (function(){var t=localStorage.getItem('theme');if(t==='dark'||(t!=='light'&&matchMedia('(prefers-color-scheme:dark)').matches))document.documentElement.classList.add('dark');else document.documentElement.classList.remove('dark')})();
        </script>

        @if(config('app.google_analytics_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('app.google_analytics_id') }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ config('app.google_analytics_id') }}');
            </script>
        @endif
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/" wire:navigate class="flex items-center gap-3">
                    <x-application-logo class="w-12 h-12 stroke-white text-white" />
                    <span class="text-3xl font-bold text-white">Logr</span>
                </a>
            </div>

            @if(config('app.demo_mode'))
                <div class="w-full sm:max-w-md mt-4 px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-center text-sm text-amber-700 dark:text-amber-400">
                    <strong>Demo Site.</strong> Credentials are <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded text-xs font-mono">demo</code> / <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-900/40 rounded text-xs font-mono">password</code>
                </div>
            @endif

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <div class="mt-6 mb-4 flex flex-col items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                    <x-application-logo-filled class="w-4 h-4 stroke-current" />
                    <span class="font-medium">{{ config('app.name', 'Logr') }}</span>
                    <span>v{{ config('logr.version') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ config('logr.links.github') }}" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                        GitHub
                    </a>
                    <a href="{{ config('logr.links.docker_hub') }}" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.186.186 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.186.186 0 00-.185.185v1.888c0 .102.082.185.185.186m0 2.716h2.118a.187.187 0 00.186-.186V6.29a.186.186 0 00-.186-.185h-2.118a.186.186 0 00-.185.185v1.887c0 .102.082.186.185.186m-2.93 0h2.12a.186.186 0 00.184-.186V6.29a.185.185 0 00-.185-.185H8.1a.186.186 0 00-.185.185v1.887c0 .102.083.186.185.186m-2.964 0h2.119a.186.186 0 00.185-.186V6.29a.186.186 0 00-.185-.185H5.136a.186.186 0 00-.186.185v1.887c0 .102.084.186.186.186m5.893 2.715h2.118a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.118a.186.186 0 00-.185.185v1.888c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.083.185.185.185m-2.964 0h2.119a.186.186 0 00.185-.185V9.006a.186.186 0 00-.185-.186H5.136a.186.186 0 00-.186.185v1.888c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.186.186 0 00-.186.186v1.887c0 .102.084.185.186.185m10.893 2.715h2.118a.186.186 0 00.186-.185v-1.888a.186.186 0 00-.186-.185h-2.118a.186.186 0 00-.185.185v1.888c0 .102.082.185.185.185M.001 11.962c0 .535.434.97.97.97h3.207a4.107 4.107 0 01-.048-.633 4.147 4.147 0 014.147-4.148c.996 0 1.903.36 2.606.953A5.688 5.688 0 0118.16 5.08a5.685 5.685 0 015.677 5.912h.163a.97.97 0 00.97-.97v-.06a4.91 4.91 0 00-4.91-4.91h-.09a5.68 5.68 0 00-8.803-2.752 4.144 4.144 0 00-6.329 2.584H.97a.97.97 0 00-.97.97v6.108z"/></svg>
                        Docker
                    </a>
                    <a href="https://logr.beer" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors inline-flex items-center gap-1">
                        <x-application-logo-filled class="w-3.5 h-3.5 stroke-current" />
                        logr.beer
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>
