<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="flex items-center justify-between px-6 py-3">
        <div class="md:hidden flex items-center">
            <button type="button" @click="sidebarOpen = true" class="p-3 -ml-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>        @isset($header)
            <div class="flex-1">
                {{ $header }}
            </div>
        @else
            <div class="flex-1"></div>
        @endisset

        <div class="flex items-center gap-4">
            {{-- Notification Bell --}}
            <x-dropdown align="right" width="80">
                <x-slot name="trigger">
                    <button class="relative inline-flex items-center p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition ease-in-out duration-150">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        @if($notifCount > 0)
                            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full">
                                {{ $notifCount > 99 ? '99+' : $notifCount }}
                            </span>
                        @endif
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="max-h-96 overflow-y-auto">
                        <div class="px-4 py-2 font-semibold text-gray-700 border-b bg-gray-50 sticky top-0">
                            Expiry Alerts
                        </div>

                        @if($notifCount === 0)
                            <div class="px-4 py-6 text-sm text-gray-400 text-center">
                                No expiry alerts. All good! ✅
                            </div>
                        @else
                            @foreach($notifExpired as $product)
                                <a href="{{ route('expiry.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 border-b">
                                    <span class="inline-block w-2 h-2 rounded-full bg-red-600 mr-2"></span>
                                    <span class="font-medium">{{ $product->display_name }}</span>
                                    <span class="block text-xs text-red-600 ml-4">Expired on {{ \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') }}</span>
                                </a>
                            @endforeach
                            @foreach($notifWithin7 as $product)
                                <a href="{{ route('expiry.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 border-b">
                                    <span class="inline-block w-2 h-2 rounded-full bg-orange-500 mr-2"></span>
                                    <span class="font-medium">{{ $product->display_name }}</span>
                                    <span class="block text-xs text-orange-600 ml-4">Expires in {{ \Carbon\Carbon::today()->diffInDays($product->expiry_date) }} day(s)</span>
                                </a>
                            @endforeach
                            @foreach($notifWithin15 as $product)
                                <a href="{{ route('expiry.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 border-b">
                                    <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-2"></span>
                                    <span class="font-medium">{{ $product->display_name }}</span>
                                    <span class="block text-xs text-yellow-600 ml-4">Expires in {{ \Carbon\Carbon::today()->diffInDays($product->expiry_date) }} day(s)</span>
                                </a>
                            @endforeach
                            @foreach($notifWithin30 as $product)
                                <a href="{{ route('expiry.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-50 border-b">
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-400 mr-2"></span>
                                    <span class="font-medium">{{ $product->display_name }}</span>
                                    <span class="block text-xs text-blue-600 ml-4">Expires in {{ \Carbon\Carbon::today()->diffInDays($product->expiry_date) }} day(s)</span>
                                </a>
                            @endforeach
                        @endif

                        <a href="{{ route('expiry.index') }}" class="block px-4 py-2 text-sm text-center text-blue-600 hover:bg-gray-50 font-medium">
                            View Full Expiry Tracking →
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            {{-- User Profile Pill --}}
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 pl-1 pr-3 py-1 rounded-full border border-gray-200 hover:bg-gray-50 transition">
                        <span class="w-8 h-8 rounded-full bg-green-700 text-white flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(strrchr(Auth::user()->name, ' '), 1, 1)) }}
                        </span>
                        <span class="text-sm text-left leading-tight hidden sm:block">
                            <span class="block font-medium text-gray-800">{{ Auth::user()->name }}</span>
                            <span class="block text-xs text-gray-400 capitalize">{{ Auth::user()->role }}</span>
                        </span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">
                        {{ __('Profile') }}
                    </x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
