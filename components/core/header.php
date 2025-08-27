<?php
/**
 * FlowJM Header Component
 * Fixed top bar like a trail sign - logo and profile with camp button
 */

function renderHeader($user) {
    $avatar = renderAvatar($user);
    $userName = escapeContent($user['name']);
    
    return <<<HTML
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-sunrise-orange text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>
    
    <header class="bg-white shadow-sm border-b border-stone-gray/20 sticky top-0 z-50" role="banner">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo/Brand -->
                <div class="flex items-center space-x-4">
                    <a href="/" class="flex items-center space-x-3 text-forest-floor hover:text-sunrise-orange transition-colors" aria-label="FlowJM Home">
                        <svg class="icon-mountain w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="m8 3 4 8 5-5 5 15H2L8 3z"></path>
                        </svg>
                        <span class="font-bold text-xl">FlowJM</span>
                    </a>
                </div>
                
                <!-- Navigation - Desktop -->
                <nav class="hidden md:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
                    <a href="/" class="text-small font-medium text-stone-gray hover:text-forest-floor transition-colors">Lookout</a>
                    <a href="/journeys.php" class="text-small font-medium text-stone-gray hover:text-forest-floor transition-colors">Trails</a>
                    <a href="/moments.php" class="text-small font-medium text-stone-gray hover:text-forest-floor transition-colors">Moments</a>
                    <a href="/fieldnotes.php" class="text-small font-medium text-stone-gray hover:text-forest-floor transition-colors">Notes</a>
                </nav>
                
                <!-- User Profile & Camp Button -->
                <div class="flex items-center space-x-4">
                    <!-- Camp Button (Tent Icon) -->
                    <button 
                        onclick="toggleCampDrawer()" 
                        class="p-2 text-stone-gray hover:text-sunrise-orange hover:bg-surface-secondary rounded-lg transition-colors"
                        title="Return to basecamp"
                        aria-label="Open camp drawer"
                        aria-expanded="false"
                        aria-controls="camp-drawer"
                    >
                        <svg class="icon-tent" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M3.5 21 12 2l8.5 19"></path>
                            <path d="M12 2v19"></path>
                            <path d="M7 21h10"></path>
                            <path d="M9.5 9.5 12 2l2.5 7.5"></path>
                        </svg>
                    </button>
                    
                    <!-- User Menu Dropdown -->
                    <div class="relative">
                        <button 
                            class="flex items-center space-x-2 text-stone-gray hover:text-night-sky transition-colors"
                            onclick="toggleUserMenu()"
                            id="user-menu-button"
                            aria-label="User menu"
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="user-menu"
                        >
                            $avatar
                            <span class="hidden sm:block text-sm font-medium">$userName</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div 
                            id="user-menu" 
                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-stone-gray/20 py-2 z-50"
                        >
                            <a href="/profile.php" class="block px-4 py-2 text-sm text-stone-gray hover:bg-canvas hover:text-night-sky transition-colors">
                                Profile Settings
                            </a>
                            <a href="/preferences.php" class="block px-4 py-2 text-sm text-stone-gray hover:bg-canvas hover:text-night-sky transition-colors">
                                Preferences
                            </a>
                            <hr class="my-2 border-stone-gray/20">
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile Menu Button -->
                <button 
                    class="md:hidden p-2 text-stone-gray hover:text-night-sky"
                    onclick="toggleMobileMenu()"
                    aria-label="Toggle mobile menu"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden py-4 border-t border-border-light" role="navigation" aria-label="Mobile navigation">
                <nav class="flex flex-col space-y-1">
                    <a href="/" class="text-body text-stone-gray hover:text-forest-floor hover:bg-surface-secondary transition-colors px-2 py-3 rounded-lg">The Lookout</a>
                    <a href="/journeys.php" class="text-body text-stone-gray hover:text-forest-floor hover:bg-surface-secondary transition-colors px-2 py-3 rounded-lg">Trail Maps</a>
                    <a href="/moments.php" class="text-body text-stone-gray hover:text-forest-floor hover:bg-surface-secondary transition-colors px-2 py-3 rounded-lg">Trail Log</a>
                    <a href="/fieldnotes.php" class="text-body text-stone-gray hover:text-forest-floor hover:bg-surface-secondary transition-colors px-2 py-3 rounded-lg">Field Notes</a>
                </nav>
            </div>
        </div>
    </header>
    
    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }
        
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
        
        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            
            if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
    HTML;
}