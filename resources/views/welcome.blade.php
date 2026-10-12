<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ExTrack') }}</title>

        @fonts

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        <style>
            :root {
                --brand: #1F6D45;
                --brand-dark: #175636;
                --brand-light: #24875A;
                --brand-tint: #EAF6EF;
                --text: #EDF3EE;
                --text-muted: #9FB2A5;
            }
            body { font-family: 'Inter', sans-serif; color: var(--text); }
            .font-display { font-family: 'Space Grotesk', sans-serif; }

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
                .logo-ring { animation: pulse-ring 2.8s ease-out infinite; }
                .sprig { animation: sway 3.4s ease-in-out infinite; transform-origin: 50% 100%; }
            }
            @keyframes spin { from { transform: translate(-50%, -50%) rotate(0deg); } to { transform: translate(-50%, -50%) rotate(360deg); } }
            @keyframes drift1 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(-24px, 28px) scale(1.06); } }
            @keyframes drift2 { 0%,100% { transform: translate(0,0) scale(1); } 50% { transform: translate(22px, -22px) scale(1.05); } }
            @keyframes drift3 { 0%,100% { transform: translate(0,0); } 50% { transform: translate(16px, 20px); } }
            @keyframes bob { 0%,100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-14px) rotate(6deg); } }
            @keyframes twinkle { 0%,100% { opacity: 0.15; transform: scale(1); } 50% { opacity: 0.9; transform: scale(1.4); } }
            @keyframes rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
            @keyframes pulse-ring { 0% { transform: scale(1); opacity: 0.5; } 100% { transform: scale(1.7); opacity: 0; } }
            @keyframes sway { 0%,100% { transform: rotate(-4deg); } 50% { transform: rotate(4deg); } }

            .auth-card {
                position: relative; z-index: 1;
                box-shadow: 0 1px 2px rgba(0,0,0,0.2), 0 30px 60px -12px rgba(0,0,0,0.55);
            }

            .logo-wrap { position: relative; display: inline-flex; align-items: center; justify-content: center; }
            .logo-ring { position: absolute; inset: 0; border-radius: 1.25rem; border: 2px solid #3ED598; pointer-events: none; }

            .btn-primary {
                background: linear-gradient(155deg, var(--brand-light) 0%, var(--brand) 100%);
                color: #FFFFFF;
                box-shadow: 0 10px 22px -8px rgba(31,109,69,0.55);
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 28px -8px rgba(31,109,69,0.6); }
            .btn-primary:active { transform: translateY(0); }
            .btn-primary .arrow { transition: transform 0.2s ease; display: inline-block; }
            .btn-primary:hover .arrow { transform: translateX(3px); }

            .btn-ghost { background: transparent; color: var(--text); border: 1px solid rgba(237,243,238,0.2); }
            .btn-ghost:hover { background: rgba(237,243,238,0.06); border-color: rgba(237,243,238,0.35); }

            a:focus-visible, button:focus-visible { outline: 2px solid #3ED598; outline-offset: 2px; }

            .register-link { color: #3ED598; position: relative; }
            .register-link::after {
                content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 1px;
                background: #3ED598; transform: scaleX(0); transform-origin: right;
                transition: transform 0.2s ease;
            }
            .register-link:hover::after { transform: scaleX(1); transform-origin: left; }

            .card-surface { background: #10211A; border: 1px solid rgba(237,243,238,0.08); }
        </style>
    </head>
    <body class="antialiased">
        <div class="page-bg flex flex-col">
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

            <!-- Top nav -->
            <header class="relative z-10 w-full px-6 py-5 border-b" style="border-color: rgba(237,243,238,0.08);">
                <div class="max-w-6xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-md flex items-center justify-center" style="background: var(--brand);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 8L12 3 3 8l9 5 9-5Z"/>
                                <path d="M3 8v8l9 5 9-5V8"/>
                                <path d="M12 13v8"/>
                            </svg>
                        </span>
                        <span class="font-display text-lg font-semibold tracking-tight">ExTrack</span>
                    </div>

                    @if (Route::has('login'))
                        <nav class="flex items-center gap-3">
                            @auth
                                <a href="{{ url('/dashboard') }}"
                                   class="btn-primary px-5 py-2 rounded-md text-sm font-medium transition">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="px-4 py-2 text-sm font-medium transition" style="color: var(--text-muted);">
                                    Log in
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}"
                                       class="btn-primary px-5 py-2 rounded-md text-sm font-medium transition">
                                        Register
                                    </a>
                                @endif
                            @endauth
                        </nav>
                    @endif
                </div>
            </header>

            <!-- Hero -->
            <main class="relative z-10 flex-1 flex items-center px-6 py-16">
                <div class="w-full sm:max-w-md mx-auto auth-card">
                    <div class="card-surface rounded-3xl px-10 py-14 text-center">

                        <p class="text-xs tracking-widest uppercase font-semibold mb-6" style="color: #3ED598; opacity: 0.85;">
                            Grocery Inventory
                        </p>

                        <div class="logo-wrap mb-6">
                            <span class="logo-ring"></span>
                            <span class="w-20 h-20 rounded-2xl inline-flex items-center justify-center relative" style="background: linear-gradient(155deg, var(--brand-light) 0%, var(--brand) 100%); box-shadow: 0 8px 20px -4px rgba(31,109,69,0.5);">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 8L12 3 3 8l9 5 9-5Z"/>
                                    <path d="M3 8v8l9 5 9-5V8"/>
                                    <path d="M12 13v8"/>
                                </svg>
                            </span>
                        </div>

                        <h1 class="font-display text-3xl sm:text-4xl font-bold mb-2.5 tracking-tight">ExTrack</h1>
                        <p class="text-base mb-11" style="color: var(--text-muted);">
                            Never lose track of your stock.
                        </p>

                        @guest
                            <a href="{{ route('login') }}"
                               class="btn-primary flex items-center justify-center gap-2 w-full py-4 rounded-full text-base font-semibold mb-4">
                                Log in
                                <span class="arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </span>
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="btn-ghost flex items-center justify-center w-full py-3.5 rounded-full text-sm font-medium transition">
                                    Register
                                </a>
                            @endif
                        @else
                            <a href="{{ url('/dashboard') }}"
                               class="btn-primary flex items-center justify-center gap-2 w-full py-4 rounded-full text-base font-semibold">
                                Go to Dashboard
                                <span class="arrow">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                                </span>
                            </a>
                        @endguest
                    </div>
                </div>
            </main>

            <footer class="relative z-10 text-center py-6 text-xs" style="color: var(--text-muted);">
                &copy; {{ date('Y') }} ExTrack. All rights reserved.
            </footer>
        </div>
    </body>
</html>
