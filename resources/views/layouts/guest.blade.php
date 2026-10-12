<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'ExTrack') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --brand: #1F6D45;
                --brand-dark: #175636;
                --brand-tint: #EAF6EF;
            }

            .page-bg {
                min-height: 100vh;
                position: relative;
                overflow: hidden;
                background: radial-gradient(ellipse 120% 80% at 50% -10%, #14382A 0%, #0B211A 55%, #071712 100%);
            }

            .rotor {
                position: absolute; top: 50%; left: 50%; width: 1100px; height: 1100px;
                transform: translate(-50%, -50%);
                background: conic-gradient(from 0deg, rgba(62,213,152,0.09), rgba(242,178,62,0.05) 30%, transparent 55%, rgba(62,213,152,0.07) 80%, rgba(62,213,152,0.09));
                filter: blur(40px);
            }

            .dot-pattern {
                position: absolute; inset: 0;
                background-image: radial-gradient(circle, rgba(62,213,152,0.14) 1px, transparent 1px);
                background-size: 26px 26px;
            }
            .twinkle { position: absolute; border-radius: 50%; background: #3ED598; }

            .blob { position: absolute; border-radius: 50%; filter: blur(2px); }
            .blob-1 { top: -12%; right: -8%; width: 480px; height: 480px;
                background: radial-gradient(circle, rgba(62,213,152,0.20) 0%, rgba(62,213,152,0) 70%); }
            .blob-2 { bottom: -14%; left: -10%; width: 520px; height: 520px;
                background: radial-gradient(circle, rgba(242,178,62,0.14) 0%, rgba(242,178,62,0) 70%); }
            .blob-3 { top: 40%; left: 6%; width: 260px; height: 260px;
                background: radial-gradient(circle, rgba(62,213,152,0.14) 0%, rgba(62,213,152,0) 70%); }
            .blob-4 { bottom: 8%; right: 8%; width: 220px; height: 220px;
                background: radial-gradient(circle, rgba(242,178,62,0.10) 0%, rgba(242,178,62,0) 70%); }

            .float-icon { position: absolute; opacity: 0.35; }

            @media (prefers-reduced-motion: no-preference) {
                .rotor { animation: spin 60s linear infinite; }
                .blob-1 { animation: drift1 17s ease-in-out infinite; }
                .blob-2 { animation: drift2 21s ease-in-out infinite; }
                .blob-3 { animation: drift3 14s ease-in-out infinite; }
                .blob-4 { animation: drift1 19s ease-in-out infinite reverse; }
                .float-icon { animation: bob 6s ease-in-out infinite; }
                .twinkle { animation: twinkle 3.2s ease-in-out infinite; }
                .auth-card { animation: rise 0.6s cubic-bezier(0.2, 0.7, 0.3, 1) both; }
            }
            @keyframes spin { from { transform: translate(-50%, -50%) rotate(0deg); } to { transform: translate(-50%, -50%) rotate(360deg); } }
            @keyframes drift1 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(-24px, 28px) scale(1.06); } }
            @keyframes drift2 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(22px, -22px) scale(1.05); } }
            @keyframes drift3 { 0%,100% { transform: translate(0,0); } 50% { transform: translate(16px, 20px); } }
            @keyframes bob { 0%,100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-14px) rotate(6deg); } }
            @keyframes twinkle { 0%,100% { opacity: 0.15; transform: scale(1); } 50% { opacity: 0.9; transform: scale(1.4); } }
            @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

            .auth-card {
                position: relative; z-index: 1;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2), 0 30px 60px -12px rgba(0,0,0,0.55);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="page-bg flex items-center justify-center px-4 py-12">
            <div class="rotor"></div>
            <div class="dot-pattern"></div>
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
            <div class="blob blob-4"></div>

            <!-- twinkling dots -->
            <span class="twinkle" style="top:18%; left:22%; width:5px; height:5px; animation-delay:0.2s;"></span>
            <span class="twinkle" style="top:30%; right:20%; width:4px; height:4px; animation-delay:1.1s;"></span>
            <span class="twinkle" style="bottom:26%; left:28%; width:6px; height:6px; animation-delay:1.9s;"></span>
            <span class="twinkle" style="bottom:34%; right:24%; width:4px; height:4px; animation-delay:0.7s;"></span>
            <span class="twinkle" style="top:50%; left:8%; width:5px; height:5px; animation-delay:2.4s;"></span>
            <span class="twinkle" style="top:12%; right:34%; width:4px; height:4px; animation-delay:1.6s;"></span>

            <!-- floating produce/box icons -->
            <svg class="float-icon hidden sm:block" style="top:14%; left:11%; animation-delay:0.2s;" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#3ED598" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="13" r="7"/><path d="M12 6q2-3 4-1"/>
            </svg>
            <svg class="float-icon hidden sm:block" style="top:70%; left:15%; animation-delay:1.6s;" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#F2B23E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="7"/>
            </svg>
            <svg class="float-icon hidden sm:block" style="top:20%; right:12%; animation-delay:1s;" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#3ED598" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 8L12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/>
            </svg>
            <svg class="float-icon hidden sm:block" style="bottom:16%; right:13%; animation-delay:2.2s;" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#F2B23E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>
            </svg>
            <svg class="float-icon hidden sm:block" style="top:44%; right:6%; animation-delay:2.8s;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3ED598" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="7" width="18" height="14" rx="1.5"/><path d="M8 7V5a4 4 0 0 1 8 0v2"/>
            </svg>
            <svg class="float-icon hidden sm:block" style="bottom:36%; left:6%; animation-delay:0.9s;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F2B23E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="7"/>
            </svg>

            <div class="w-full sm:max-w-md auth-card">
                <div class="bg-white shadow-xl rounded-2xl px-8 py-8 border border-gray-100">

                    <div class="flex flex-col items-center text-center mb-6">
                        <span class="w-14 h-14 rounded-xl flex items-center justify-center mb-3" style="background: var(--brand);">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="20" r="1"/>
                                <circle cx="18" cy="20" r="1"/>
                                <path d="M2.5 3h2l2.4 12.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6"/>
                            </svg>
                        </span>
                        <h1 class="text-xl font-bold text-gray-900">ExTrack</h1>
                        <p class="text-sm font-medium mt-0.5" style="color: var(--brand);">Vivienas Grocery Store</p>
                        <p class="text-xs text-gray-400">Dagohoy, Carmen, Bohol</p>
                    </div>

                    {{ $slot }}

                </div>

                <p class="text-center text-xs mt-5" style="color: rgba(237,243,238,0.5);">
                    &copy; {{ date('Y') }} Vivienas Grocery Store &middot; ExTrack v1.2
                </p>
            </div>
        </div>
    </body>
</html>
