<?php
/**
 * FlowJM - Clean Modern Dashboard
 */

// Define application root
define('FLOWJM_ROOT', __DIR__);

// Load configuration
require_once FLOWJM_ROOT . '/includes/config.php';
require_once INCLUDES_PATH . 'database.php';
require_once INCLUDES_PATH . 'helpers.php';
require_once INCLUDES_PATH . 'auth.php';

// Load models
require_once INCLUDES_PATH . 'models/User.php';
require_once INCLUDES_PATH . 'models/Journey.php';
require_once INCLUDES_PATH . 'models/Moment.php';
require_once INCLUDES_PATH . 'models/Fieldnote.php';

// Load component system
require_once COMPONENTS_PATH . 'index.php';

// Initialize authentication
Auth::init();
Auth::require();

// Get current user
$currentUser = Auth::user();
if (!$currentUser) {
    Auth::logout();
    redirect('/login.php');
}

// Get dashboard data
$journey = new Journey();
$moment = new Moment();

$journeyStats = $journey->getStats($_SESSION['user_id']);
$momentStats = $moment->getStats($_SESSION['user_id']);
$circleJourneys = $journey->getCircleJourneys($_SESSION['user_id']);
$activeJourneys = $journey->getByUserId($_SESSION['user_id'], 'active', 1, 20);
$recentMoments = $moment->getRecentByUserId($_SESSION['user_id'], 1, 10);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">
    <title>FlowJM - Journey Management</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        /* Clean Modern Design System */
        :root {
            --primary: #0A0A0A;
            --secondary: #4A4A4A; 
            --accent: #FF6B35;
            --success: #00C896;
            --background: #FFFFFF;
            --surface: #FAFAFA;
            --border: #E5E5E5;
        }
        
        * {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
        }
        
        /* Clean animations */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .hover-lift {
            transition: all 0.2s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        }
        
        /* Minimal scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #E5E5E5;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #D0D0D0;
        }
    </style>
</head>
<body class="bg-white">
    <!-- Clean Header -->
    <header class="border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-8">
                    <a href="/" class="flex items-center space-x-2">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M13 10l5 8H6l5-8m2-7l7 18H4L11 3z"/>
                        </svg>
                        <span class="text-xl font-medium">FlowJM</span>
                    </a>
                    
                    <!-- Clean Navigation -->
                    <nav class="hidden md:flex items-center space-x-6">
                        <a href="/" class="text-sm text-gray-900 font-medium">Dashboard</a>
                        <a href="/journeys.php" class="text-sm text-gray-500 hover:text-gray-900">Projects</a>
                        <a href="/moments.php" class="text-sm text-gray-500 hover:text-gray-900">Timeline</a>
                        <button onclick="openDrawer()" class="text-sm text-gray-500 hover:text-gray-900">Archive</button>
                    </nav>
                </div>
                
                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <button class="p-2 hover:bg-gray-50 rounded-lg">
                        <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    
                    <!-- Notifications -->
                    <button class="p-2 hover:bg-gray-50 rounded-lg relative">
                        <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <?php if ($journeyStats['critical_journeys'] > 0): ?>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Profile -->
                    <button class="flex items-center space-x-2 p-1.5 hover:bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium"><?php echo get_initials($currentUser['name']); ?></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <!-- Stats Row -->
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-xl border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-light"><?php echo $journeyStats['active_journeys']; ?></p>
                        <p class="text-sm text-gray-500 mt-1">Active Projects</p>
                    </div>
                    <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-light"><?php echo $momentStats['total_moments']; ?></p>
                        <p class="text-sm text-gray-500 mt-1">Updates</p>
                    </div>
                    <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-light"><?php echo $journeyStats['circle_journeys']; ?></p>
                        <p class="text-sm text-gray-500 mt-1">Priority</p>
                    </div>
                    <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                        <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-3xl font-light"><?php echo format_currency($journeyStats['total_balance'] ?? 0); ?></p>
                        <p class="text-sm text-gray-500 mt-1">Outstanding</p>
                    </div>
                    <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                        <span class="text-xs">$</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Priority Section -->
        <?php if (!empty($circleJourneys)): ?>
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium">Priority Projects</h2>
                <a href="/journeys.php" class="text-sm text-gray-500 hover:text-gray-900">View all →</a>
            </div>
            
            <div class="grid grid-cols-3 gap-4">
                <?php foreach (array_slice($circleJourneys, 0, 3) as $item): ?>
                <div class="bg-white p-6 rounded-xl border border-gray-100 hover-lift cursor-pointer" onclick="window.location.href='/journey.php?id=<?php echo $item['id']; ?>'">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900"><?php echo escapeContent($item['title']); ?></h3>
                            <p class="text-sm text-gray-500 mt-1"><?php echo escapeContent($item['client_name'] ?? 'Personal'); ?></p>
                        </div>
                        <span class="w-2 h-2 bg-orange-500 rounded-full"></span>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400"><?php echo time_ago($item['updated_at']); ?></span>
                        <span class="font-medium"><?php echo format_currency($item['balance_due'] ?? 0); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-3 gap-6">
            <!-- Projects List -->
            <div class="col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium">All Projects</h2>
                    <button class="text-sm text-gray-500 hover:text-gray-900">Filter</button>
                </div>
                
                <div class="space-y-3">
                    <?php foreach ($activeJourneys as $item): ?>
                    <div class="bg-white p-4 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors cursor-pointer" onclick="window.location.href='/journey.php?id=<?php echo $item['id']; ?>'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-gray-50 rounded-lg flex items-center justify-center">
                                    <span class="text-sm"><?php echo substr($item['title'], 0, 1); ?></span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-sm"><?php echo escapeContent($item['title']); ?></h4>
                                    <p class="text-xs text-gray-500 mt-0.5"><?php echo escapeContent($item['client_name'] ?? 'No client'); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-400"><?php echo $item['moment_count'] ?? 0; ?> updates</span>
                                <span class="text-sm font-medium"><?php echo format_currency($item['balance_due'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Activity Feed -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium">Recent Activity</h2>
                </div>
                
                <div class="space-y-3">
                    <?php foreach (array_slice($recentMoments, 0, 5) as $item): ?>
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 bg-gray-50 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <div class="w-1.5 h-1.5 bg-gray-400 rounded-full"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-600"><?php echo escapeContent($item['content']); ?></p>
                            <p class="text-xs text-gray-400 mt-1">
                                <?php echo escapeContent($item['journey_title']); ?> · <?php echo time_ago($item['created_at']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Floating Action Button -->
    <div class="fixed bottom-8 right-8 z-50">
        <!-- Main FAB -->
        <button 
            id="fab-main"
            class="w-14 h-14 bg-black text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center group"
            onclick="toggleQuickActions()"
        >
            <svg id="fab-icon" class="w-6 h-6 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </button>
        
        <!-- Quick Action Menu -->
        <div id="fab-menu" class="absolute bottom-16 right-0 space-y-2 opacity-0 scale-0 transform origin-bottom-right transition-all duration-200">
            <!-- Add Journey -->
            <button 
                class="w-12 h-12 bg-gray-800 text-white rounded-full shadow-md hover:shadow-lg transition-all duration-150 flex items-center justify-center"
                onclick="window.location.href='/journey/create.php'"
                title="New Project"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 .553-.894L9 2l6 3 5.447-2.724A1 1 0 0 1 21 3.618v10.764a1 1 0 0 1-.553.894L15 18l-6-3z"/>
                    <path d="M9 2v18M15 5v18"/>
                </svg>
            </button>
            
            <!-- Add Moment -->
            <button 
                class="w-12 h-12 bg-gray-600 text-white rounded-full shadow-md hover:shadow-lg transition-all duration-150 flex items-center justify-center"
                onclick="alert('Add moment coming soon')"
                title="Quick Update"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="m18.5 2.5-8 8V14h3.5l8-8-3.5-3.5Z"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Archive Drawer -->
    <div id="drawer-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 opacity-0 pointer-events-none transition-opacity duration-300" onclick="closeDrawer()"></div>
    <div id="drawer" class="fixed top-0 right-0 h-full w-96 bg-white transform translate-x-full transition-transform duration-300 z-50 shadow-2xl">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Archive</h2>
                <button onclick="closeDrawer()" class="p-2 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="p-6">
            <p class="text-gray-500">Archived projects will appear here</p>
        </div>
    </div>
    
    <script>
    // Quick Actions Toggle
    function toggleQuickActions() {
        const menu = document.getElementById('fab-menu');
        const icon = document.getElementById('fab-icon');
        const isOpen = !menu.classList.contains('scale-0');
        
        if (isOpen) {
            menu.classList.add('scale-0', 'opacity-0');
            menu.classList.remove('scale-100', 'opacity-100');
            icon.style.transform = 'rotate(0deg)';
        } else {
            menu.classList.remove('scale-0', 'opacity-0');
            menu.classList.add('scale-100', 'opacity-100');
            icon.style.transform = 'rotate(45deg)';
        }
    }
    
    // Drawer functionality  
    function openDrawer() {
        const drawer = document.getElementById('drawer');
        const overlay = document.getElementById('drawer-overlay');
        
        overlay.classList.remove('pointer-events-none', 'opacity-0');
        overlay.classList.add('opacity-100');
        drawer.classList.remove('translate-x-full');
        drawer.classList.add('translate-x-0');
    }
    
    function closeDrawer() {
        const drawer = document.getElementById('drawer');
        const overlay = document.getElementById('drawer-overlay');
        
        overlay.classList.add('pointer-events-none', 'opacity-0');
        overlay.classList.remove('opacity-100');
        drawer.classList.add('translate-x-full');
        drawer.classList.remove('translate-x-0');
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Add fade-in animation to cards
        document.querySelectorAll('.hover-lift').forEach((el, index) => {
            el.style.animationDelay = `${index * 0.05}s`;
            el.classList.add('fade-in');
        });
        
        // Close quick actions on click outside
        document.addEventListener('click', function(e) {
            const fab = document.getElementById('fab-main');
            const menu = document.getElementById('fab-menu');
            if (!fab.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('scale-0', 'opacity-0');
                menu.classList.remove('scale-100', 'opacity-100');
                document.getElementById('fab-icon').style.transform = 'rotate(0deg)';
            }
        });
    });
    </script>
</body>
</html>