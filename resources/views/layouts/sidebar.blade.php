@php
    $navItems = function () {
        echo '';
    };
@endphp

{{-- Shared nav links (reused sa desktop ug mobile) --}}
@php
    $linkClass = fn($active) => 'flex items-center gap-3 px-5 py-2.5 text-sm ' .
        ($active ? 'bg-green-700 border-l-4 border-white font-semibold text-white' : 'text-green-100 hover:bg-green-800');
@endphp

{{-- ===== DESKTOP SIDEBAR (always visible, no JS) ===== --}}
<aside class="hidden md:flex w-64 min-h-screen bg-green-900 text-white flex-col fixed left-0 top-0 z-30">
    <div class="p-5 border-b border-green-800 flex items-center gap-2">
        <span class="text-2xl">🛒</span>
        <div>
            <div class="font-bold text-lg leading-tight">ExTrack</div>
            <div class="text-xs text-green-300 leading-tight">Vivienas Grocery</div>
        </div>
    </div>

    <nav class="flex-1 py-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="{{ $linkClass(request()->routeIs('dashboard')) }}">📊 Dashboard</a>
        <a href="{{ route('sales.index') }}" class="{{ $linkClass(request()->routeIs('sales.*')) }}">🧾 POS / Sales</a>
        <a href="{{ route('products.index') }}" class="{{ $linkClass(request()->routeIs('products.*')) }}">📦 Products</a>
                @if(auth()->user()->role === 'owner')
            <a href="{{ route('categories.index') }}" class="{{ $linkClass(request()->routeIs('categories.*')) }}">🏷 Categories</a>
            <a href="{{ route('suppliers.index') }}" class="{{ $linkClass(request()->routeIs('suppliers.*')) }}">🚚 Suppliers</a>
        @endif
        <a href="{{ route('inventories.index') }}" class="{{ $linkClass(request()->routeIs('inventories.*')) }}">📋 Inventory</a>
        <a href="{{ route('expiry.index') }}" class="{{ $linkClass(request()->routeIs('expiry.*')) }} justify-between">
            <span>⏰ Expiry Tracking</span>
            @if($notifCount > 0)
                <span class="bg-red-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>
            @endif
        </a>
        @if(auth()->user()->role === 'owner')
            <a href="{{ route('reports.sales') }}" class="{{ $linkClass(request()->routeIs('reports.*')) }}">📈 Reports</a>
            <a href="{{ route('reports.daily-close') }}" class="{{ $linkClass(request()->routeIs('reports.daily-close*')) }}">💵 Daily Closing</a>
            <a href="{{ route('users.index') }}" class="{{ $linkClass(request()->routeIs('users.*')) }}">👥 Users</a>
        @endif
    </nav>

    <div class="p-4 border-t border-green-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); this.closest('form').submit();"
               class="flex items-center gap-3 px-2 py-2 text-sm text-green-100 hover:bg-green-800 rounded">
                🚪 Logout
            </a>
        </form>
    </div>
</aside>

{{-- ===== MOBILE DRAWER (separate element, JS-controlled) ===== --}}
<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
     class="md:hidden fixed inset-0 bg-black/50 z-40" style="display: none;"></div>

<aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
       class="md:hidden w-64 min-h-screen bg-green-900 text-white flex flex-col fixed left-0 top-0 z-50" style="display: none;">
    <div class="p-5 border-b border-green-800 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="text-2xl">🛒</span>
            <div>
                <div class="font-bold text-lg leading-tight">ExTrack</div>
                <div class="text-xs text-green-300 leading-tight">Vivienas Grocery</div>
            </div>
        </div>
        <button @click="sidebarOpen = false" class="text-green-200 p-1">✕</button>
    </div>

    <nav class="flex-1 py-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="{{ $linkClass(request()->routeIs('dashboard')) }}">📊 Dashboard</a>
        <a href="{{ route('sales.index') }}" class="{{ $linkClass(request()->routeIs('sales.*')) }}">🧾 POS / Sales</a>
        <a href="{{ route('products.index') }}" class="{{ $linkClass(request()->routeIs('products.*')) }}">📦 Products</a>
        @if(auth()->user()->role === 'owner')
            <a href="{{ route('categories.index') }}" class="{{ $linkClass(request()->routeIs('categories.*')) }}">🏷 Categories</a>
            <a href="{{ route('suppliers.index') }}" class="{{ $linkClass(request()->routeIs('suppliers.*')) }}">🚚 Suppliers</a>
        @endif
        <a href="{{ route('inventories.index') }}" class="{{ $linkClass(request()->routeIs('inventories.*')) }}">📋 Inventory</a>
        <a href="{{ route('expiry.index') }}" class="{{ $linkClass(request()->routeIs('expiry.*')) }} justify-between">
            <span>⏰ Expiry Tracking</span>
            @if($notifCount > 0)
                <span class="bg-red-600 text-white text-xs px-2 py-0.5 rounded-full">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>
            @endif
        </a>
        @if(auth()->user()->role === 'owner')
            <a href="{{ route('reports.sales') }}" class="{{ $linkClass(request()->routeIs('reports.*')) }}">📈 Reports</a>
            <a href="{{ route('reports.daily-close') }}" class="{{ $linkClass(request()->routeIs('reports.daily-close*')) }}">💵 Daily Closing</a>
            <a href="{{ route('users.index') }}" class="{{ $linkClass(request()->routeIs('users.*')) }}">👥 Users</a>
        @endif
    </nav>

    <div class="p-4 border-t border-green-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault(); this.closest('form').submit();"
               class="flex items-center gap-3 px-2 py-2 text-sm text-green-100 hover:bg-green-800 rounded">
                🚪 Logout
            </a>
        </form>
    </div>
</aside>
