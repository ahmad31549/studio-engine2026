<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'THOR REBRAND TOOL')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css'])
    @endunless
    
    @stack('styles')
</head>
<body>
    <div class="app-shell">
        <nav class="main-nav">
                <a href="/" class="logo-wrapper">
                <div class="logo-icon" aria-hidden="true">
                    <svg viewBox="0 0 36 36" role="presentation" focusable="false">
                        <path d="M9 12.5h13.5a2 2 0 0 1 2 2V18H12a3 3 0 0 1-3-3v-2.5Z" fill="rgba(255,255,255,0.96)" />
                        <path d="M24.5 13 28.5 9l2.5 2.5-4 4Z" fill="#FFD27A" />
                        <path d="M16.8 18.5 19.6 21l-6.8 6.8a2 2 0 1 1-2.8-2.8Z" fill="rgba(255,255,255,0.96)" />
                        <path d="M22.5 19h5.5l-3.2 4h2.6l-5.8 6.5 1.6-5H19.5Z" fill="#FFB347" />
                    </svg>
                </div>
                <div class="logo-text">THOR <span style="color: var(--primary)">REBRAND TOOL</span></div>
            </a>
            
            <div class="nav-links">
                <a href="/" class="nav-link {{ request()->is('/') ? 'active' : '' }}">Studio</a>
                @auth
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.index') }}" class="nav-link {{ request()->is('admin') ? 'active' : '' }}">Members</a>
                    @endif
                    <a href="/setting" class="nav-link {{ request()->is('setting', 'profile*') ? 'active' : '' }}">Settings</a>
                @else
                    <a href="/login" class="nav-link">Sign In</a>
                    <a href="/register" class="btn btn-primary" style="height: 40px; padding: 0 16px; font-size: 0.875rem;">Get Started</a>
                @endauth
            </div>
        </nav>

        <main>
            @yield('content')
        </main>
        
        <footer style="text-align: center; padding: 60px 0; color: var(--text-dim); font-size: 0.8125rem;">
            <p>&copy; {{ date('Y') }} THOR Rebrand Tool. All processing remains on-node for maximum security.</p>
        </footer>
    </div>
    
    @stack('scripts')
</body>
</html>
