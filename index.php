<?php
/**
 * FlowJM - The Lookout
 * A narrative-driven journey management system for creative freelancers
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

// Get today's focus (journeys with due dates in next 3 days or critical status)
$todaysFocus = array_filter($circleJourneys, function($j) {
    $dueIn = $j['due_date'] ? (strtotime($j['due_date']) - time()) / 86400 : 999;
    return $dueIn <= 3 || $j['pulse_status'] == 'critical';
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">
    <title>The Lookout - FlowJM</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* Creative Journey Design System */
        :root {
            /* Journey Colors - Warm and Natural */
            --trail-dust: #F5F2ED;      /* Main background */
            --morning-light: #FFFFFF;    /* Cards */
            --campfire: #FF6B35;         /* Primary action */
            --forest-green: #00AA88;     /* Success */
            --sunset-gold: #FFB800;      /* Warning */
            --mountain-shadow: #2D3748;  /* Dark text */
            --trail-marker: #718096;     /* Secondary text */
            --mist: #E2E8F0;            /* Borders */
            
            /* Creative Shadows */
            --shadow-soft: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-card: 0 4px 12px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.08);
            --shadow-fab: 0 12px 32px rgba(255, 107, 53, 0.25);
        }
        
        * {
            font-family: 'Inter', -apple-system, sans-serif;
        }
        
        h1, h2, h3 {
            font-family: 'Space Grotesk', sans-serif;
        }
        
        /* Background Pattern */
        body {
            background-color: var(--trail-dust);
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 107, 53, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 170, 136, 0.02) 0%, transparent 50%);
        }
        
        /* Journey Card Styles */
        .journey-card {
            background: linear-gradient(135deg, var(--morning-light) 0%, rgba(255, 255, 255, 0.95) 100%);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .journey-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }
        
        /* Moment Feed Style - Like a Creative Timeline */
        .moment-item {
            position: relative;
            padding-left: 2rem;
        }
        
        .moment-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.75rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--campfire);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .moment-item::after {
            content: '';
            position: absolute;
            left: 3px;
            top: 1.75rem;
            width: 2px;
            height: calc(100% - 1rem);
            background: linear-gradient(to bottom, var(--mist), transparent);
        }
        
        .moment-item:last-child::after {
            display: none;
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.2);
                opacity: 0.6;
            }
        }
        
        .pulse-dot {
            animation: pulse 2s ease-in-out infinite;
        }
        
        /* Floating Action Button - Campfire Style */
        .fab-main {
            background: linear-gradient(135deg, var(--campfire) 0%, #FF8055 100%);
            box-shadow: var(--shadow-fab);
        }
        
        .fab-main:hover {
            transform: scale(1.1) rotate(90deg);
        }
        
        /* Quick Add Form */
        .quick-add {
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .quick-add.active {
            transform: translateY(0);
        }
        
        /* Status Badges */
        .status-healthy { 
            background: linear-gradient(135deg, #00AA88 0%, #00CC99 100%);
            color: white;
        }
        
        .status-warning { 
            background: linear-gradient(135deg, #FFB800 0%, #FFC933 100%);
            color: white;
        }
        
        .status-critical { 
            background: linear-gradient(135deg, #FF4757 0%, #FF6B7A 100%);
            color: white;
        }
        
        /* Desktop Navigation Tabs */
        .nav-tab-header {
            position: relative;
            padding: 1rem 1.5rem;
            color: var(--trail-marker);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 0.75rem;
            font-weight: 500;
        }
        
        .nav-tab-header:hover {
            color: var(--mountain-shadow);
            background: rgba(255, 107, 53, 0.05);
        }
        
        .nav-tab-header.active {
            color: var(--campfire);
            background: rgba(255, 107, 53, 0.1);
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.2);
        }
        
        /* Content Sections */
        .section-content {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .section-content.hidden {
            display: none;
        }
        
        /* Enhanced Journey Cards */
        .journey-card {
            background: linear-gradient(135deg, var(--morning-light) 0%, rgba(255, 255, 255, 0.98) 100%);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .journey-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: rgba(255, 107, 53, 0.2);
        }
        
        /* Multi-column Moment Layout */
        .columns-1 {
            column-count: 1;
        }
        
        @media (min-width: 1024px) {
            .columns-2 {
                column-count: 2;
            }
        }
    </style>
</head>
<body>
    <!-- Professional Desktop Header -->
    <header class="bg-white/95 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between px-6 py-4">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <svg class="w-10 h-10 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M13 10l5 8H6l5-8m2-7l7 18H4L11 3z"/>
                        </svg>
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full pulse-dot"></div>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">FlowJM</h1>
                        <p class="text-sm text-gray-500 font-medium">The Lookout • <?php echo date('l, M j, Y'); ?></p>
                    </div>
                </div>
                
                <!-- Desktop Navigation Tabs -->
                <nav class="flex items-center space-x-8">
                    <button class="nav-tab-header active" data-section="circle">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">Circle</span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"><?php echo $journeyStats['circle_journeys']; ?></span>
                        </div>
                    </button>
                    <button class="nav-tab-header" data-section="stacks">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                            </svg>
                            <span class="font-medium">Stacks</span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"><?php echo count($recentMoments); ?></span>
                        </div>
                    </button>
                    <button class="nav-tab-header" data-section="camp">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="font-medium">Camp</span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"><?php echo $journeyStats['active_journeys']; ?></span>
                        </div>
                    </button>
                </nav>
                
                <!-- User Menu and Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Journey Health Indicator -->
                    <div class="flex items-center space-x-2 px-4 py-2 bg-gradient-to-r from-green-50 to-green-100 rounded-xl border border-green-200">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm font-semibold text-green-700"><?php echo $journeyStats['active_journeys']; ?> Active</span>
                    </div>
                    
                    <!-- Quick Add Button -->
                    <button onclick="toggleQuickAdd()" class="flex items-center space-x-2 px-4 py-2 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-all duration-200 shadow-md hover:shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="font-medium">Add Moment</span>
                    </button>
                    
                    <!-- User Avatar -->
                    <div class="flex items-center space-x-3">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900"><?php echo escapeContent($currentUser['name']); ?></p>
                            <p class="text-xs text-gray-500">Creative Professional</p>
                        </div>
                        <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center shadow-md">
                            <span class="text-sm font-bold text-white"><?php echo get_initials($currentUser['name']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Desktop Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        
        <!-- Circle Section - Rich Journey Cards -->
        <section id="circle-section" class="section-content">
            <?php if (!empty($todaysFocus)): ?>
            <div class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-orange-100 rounded-xl">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Priority Focus</h2>
                            <p class="text-gray-600">Journeys requiring immediate attention</p>
                        </div>
                    </div>
                    <span class="px-4 py-2 text-sm font-semibold bg-red-100 text-red-700 rounded-xl">
                        <?php echo count($todaysFocus); ?> Critical Items
                    </span>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <?php foreach (array_slice($todaysFocus, 0, 3) as $journey): ?>
                    <div class="journey-card bg-white rounded-2xl p-8 border border-gray-100 shadow-md hover:shadow-xl transition-all duration-300 cursor-pointer" 
                         onclick="window.location.href='/journey.php?id=<?php echo $journey['id']; ?>'">
                        
                        <!-- Journey Header -->
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo escapeContent($journey['title']); ?></h3>
                                <p class="text-sm font-medium text-gray-500"><?php echo escapeContent($journey['client_name'] ?? 'Personal Project'); ?></p>
                            </div>
                            <?php
                            $statusClass = 'status-' . $journey['pulse_status'];
                            $statusColor = $journey['pulse_status'] == 'critical' ? 'bg-red-500' : ($journey['pulse_status'] == 'warning' ? 'bg-yellow-500' : 'bg-green-500');
                            ?>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 <?php echo $statusColor; ?> rounded-full"></div>
                                <span class="px-3 py-1.5 text-xs font-bold rounded-xl <?php echo $statusClass; ?>">
                                    <?php echo strtoupper($journey['pulse_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Journey Details -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-2 text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="font-medium"><?php echo $journey['due_date'] ? date('M j, Y', strtotime($journey['due_date'])) : 'Ongoing'; ?></span>
                                </div>
                                <?php if ($journey['balance_due'] > 0): ?>
                                <div class="text-right">
                                    <p class="text-xl font-bold text-gray-900"><?php echo format_currency($journey['balance_due']); ?></p>
                                    <p class="text-xs text-gray-500">Outstanding</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Progress Indicator -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700">Progress</span>
                                    <span class="text-gray-500">75%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-orange-500 h-2 rounded-full" style="width: 75%"></div>
                                </div>
                            </div>
                            
                            <!-- Last Activity -->
                            <?php if ($journey['last_moment_at']): ?>
                            <div class="pt-4 border-t border-gray-100">
                                <p class="text-sm text-gray-600">
                                    <span class="font-medium">Last update:</span> <?php echo time_ago($journey['last_moment_at']); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- All Active Journeys Grid -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Active Journeys</h3>
                    <button class="flex items-center space-x-2 px-4 py-2 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>New Journey</span>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <?php foreach (array_slice($circleJourneys, 0, 8) as $journey): ?>
                    <div class="journey-card bg-white rounded-xl p-6 border border-gray-100 hover:shadow-md transition-all duration-200 cursor-pointer" 
                         onclick="window.location.href='/journey.php?id=<?php echo $journey['id']; ?>'">
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-semibold text-gray-900 text-sm leading-tight"><?php echo escapeContent($journey['title']); ?></h4>
                            <?php
                            $statusColor = $journey['pulse_status'] == 'critical' ? 'bg-red-500' : ($journey['pulse_status'] == 'warning' ? 'bg-yellow-500' : 'bg-green-500');
                            ?>
                            <div class="w-2 h-2 <?php echo $statusColor; ?> rounded-full"></div>
                        </div>
                        <p class="text-xs text-gray-500 mb-3"><?php echo escapeContent($journey['client_name'] ?? 'Personal'); ?></p>
                        <?php if ($journey['balance_due'] > 0): ?>
                        <p class="text-sm font-bold text-gray-900"><?php echo format_currency($journey['balance_due']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Stacks Section - Multi-Column Moment Feed -->
        <section id="stacks-section" class="section-content hidden">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 rounded-xl">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">The Stacks</h2>
                        <p class="text-gray-600">Your creative journey timeline</p>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Moment Feed - Multi-Column -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="font-bold text-gray-900">Recent Activity</h3>
                        </div>
                        
                        <div class="p-6">
                            <?php if (!empty($recentMoments)): ?>
                            <div class="columns-1 lg:columns-2 gap-6 space-y-4">
                                <?php foreach ($recentMoments as $moment): ?>
                                <div class="moment-item break-inside-avoid mb-6">
                                    <div class="bg-gray-50 rounded-xl p-5">
                                        <!-- Moment Content -->
                                        <p class="text-gray-800 leading-relaxed mb-4">
                                            <?php echo escapeContent($moment['content']); ?>
                                        </p>
                                        
                                        <!-- Moment Meta -->
                                        <div class="flex items-center justify-between text-sm">
                                            <div class="flex items-center space-x-3">
                                                <span class="font-semibold text-orange-600">
                                                    <?php echo escapeContent($moment['journey_title']); ?>
                                                </span>
                                                <?php if (!empty($moment['type']) && $moment['type'] == 'milestone'): ?>
                                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full">
                                                    Milestone
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-gray-400">
                                                <?php echo time_ago($moment['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-16">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2">No moments yet</h3>
                                <p class="text-gray-500 mb-6">Start logging moments to build your journey story</p>
                                <button onclick="toggleQuickAdd()" class="px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-colors font-semibold">
                                    Log Your First Moment
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Sidebar -->
                <div class="space-y-6">
                    <!-- Journey Pulse -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-6">Journey Pulse</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                                    <span class="text-sm font-medium text-gray-700">Healthy</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <?php 
                                        $healthyCount = isset($journeyStats['healthy_journeys']) ? $journeyStats['healthy_journeys'] : 0;
                                        $activeCount = isset($journeyStats['active_journeys']) && $journeyStats['active_journeys'] > 0 ? $journeyStats['active_journeys'] : 1;
                                        $healthyPercent = ($healthyCount / $activeCount) * 100;
                                        ?>
                                        <div class="h-full bg-green-500" style="width: <?php echo $healthyPercent; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900"><?php echo $healthyCount; ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 bg-yellow-500 rounded-full"></div>
                                    <span class="text-sm font-medium text-gray-700">Warning</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <?php 
                                        $warningCount = isset($journeyStats['warning_journeys']) ? $journeyStats['warning_journeys'] : 0;
                                        $warningPercent = ($warningCount / $activeCount) * 100;
                                        ?>
                                        <div class="h-full bg-yellow-500" style="width: <?php echo $warningPercent; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900"><?php echo $warningCount; ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-4 h-4 bg-red-500 rounded-full"></div>
                                    <span class="text-sm font-medium text-gray-700">Critical</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-20 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <?php 
                                        $criticalCount = isset($journeyStats['critical_journeys']) ? $journeyStats['critical_journeys'] : 0;
                                        $criticalPercent = ($criticalCount / $activeCount) * 100;
                                        ?>
                                        <div class="h-full bg-red-500" style="width: <?php echo $criticalPercent; ?>%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900"><?php echo $criticalCount; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Flow -->
                    <div class="bg-gradient-to-br from-orange-50 to-pink-50 rounded-2xl p-6 border border-orange-100">
                        <h3 class="font-bold text-gray-900 mb-4">Financial Flow</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">Outstanding</span>
                                <span class="text-2xl font-bold text-gray-900">
                                    <?php echo format_currency($journeyStats['total_balance'] ?? 0); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-600">This Month</span>
                                <span class="text-lg font-semibold text-gray-700">
                                    <?php echo format_currency($journeyStats['month_total'] ?? 0); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Stats -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-900 mb-4">Activity</h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-3xl font-bold text-gray-900"><?php echo $momentStats['total_moments']; ?></p>
                                <p class="text-sm font-medium text-gray-500">Total Moments</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-3xl font-bold text-gray-900"><?php echo $momentStats['week_moments'] ?? 0; ?></p>
                                <p class="text-sm font-medium text-gray-500">This Week</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Camp Section - Journey Management -->
        <section id="camp-section" class="section-content hidden">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-green-100 rounded-xl">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Base Camp</h2>
                        <p class="text-gray-600">Journey management and analytics</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Camp is Being Set Up</h3>
                <p class="text-gray-500">Advanced journey management tools coming soon</p>
            </div>
        </section>
    </main>
    
    <!-- Enhanced Quick Add Modal -->
    <div id="quick-add-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-6">
            <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Log a Journey Moment</h3>
                    <button onclick="toggleQuickAdd()" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Journey</label>
                        <select class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option>Select a journey...</option>
                            <?php foreach ($activeJourneys as $j): ?>
                            <option value="<?php echo $j['id']; ?>"><?php echo escapeContent($j['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">What happened?</label>
                        <textarea 
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                            rows="4"
                            placeholder="Describe your progress, challenges, or achievements..."
                        ></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Type</label>
                            <select class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="progress">Progress Update</option>
                                <option value="milestone">Milestone</option>
                                <option value="challenge">Challenge</option>
                                <option value="insight">Insight</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Mood</label>
                            <div class="flex space-x-2">
                                <button type="button" class="flex-1 p-3 border border-gray-200 rounded-xl hover:bg-green-50 hover:border-green-200 transition-colors">
                                    <span class="text-2xl">😊</span>
                                </button>
                                <button type="button" class="flex-1 p-3 border border-gray-200 rounded-xl hover:bg-yellow-50 hover:border-yellow-200 transition-colors">
                                    <span class="text-2xl">😐</span>
                                </button>
                                <button type="button" class="flex-1 p-3 border border-gray-200 rounded-xl hover:bg-red-50 hover:border-red-200 transition-colors">
                                    <span class="text-2xl">😤</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-100">
                        <button type="button" onclick="toggleQuickAdd()" class="px-6 py-3 text-gray-600 hover:text-gray-900 font-semibold transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="px-8 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-colors font-semibold shadow-md hover:shadow-lg">
                            Save Moment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Toggle Enhanced Quick Add Modal
    function toggleQuickAdd() {
        const modal = document.getElementById('quick-add-modal');
        modal.classList.toggle('hidden');
        
        if (!modal.classList.contains('hidden')) {
            // Focus first input when opening
            setTimeout(() => {
                modal.querySelector('select').focus();
            }, 100);
        }
    }
    
    // Desktop Navigation System
    document.addEventListener('DOMContentLoaded', function() {
        const navButtons = document.querySelectorAll('.nav-tab-header');
        const sections = {
            'circle': document.getElementById('circle-section'),
            'stacks': document.getElementById('stacks-section'),
            'camp': document.getElementById('camp-section')
        };
        
        // Navigation Handler
        navButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetSection = this.getAttribute('data-section');
                
                // Update active nav
                navButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide sections with smooth transition
                Object.keys(sections).forEach(key => {
                    const section = sections[key];
                    if (key === targetSection) {
                        section.classList.remove('hidden');
                        setTimeout(() => {
                            section.style.opacity = '1';
                            section.style.transform = 'translateY(0)';
                        }, 10);
                    } else {
                        section.style.opacity = '0';
                        section.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            section.classList.add('hidden');
                        }, 300);
                    }
                });
                
                // Update URL without reload
                history.pushState(null, '', `#${targetSection}`);
            });
        });
        
        // Handle back/forward browser buttons
        window.addEventListener('popstate', function() {
            const hash = window.location.hash.slice(1);
            const targetButton = document.querySelector(`[data-section="${hash}"]`);
            if (targetButton) {
                targetButton.click();
            }
        });
        
        // Initialize from URL hash
        const initialHash = window.location.hash.slice(1);
        if (initialHash && sections[initialHash]) {
            const targetButton = document.querySelector(`[data-section="${initialHash}"]`);
            if (targetButton) {
                targetButton.click();
            }
        }
        
        // Staggered Animation for Journey Cards
        function animateCards() {
            document.querySelectorAll('.journey-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        }
        
        // Animate cards on load
        animateCards();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape to close modal
            if (e.key === 'Escape') {
                const modal = document.getElementById('quick-add-modal');
                if (!modal.classList.contains('hidden')) {
                    toggleQuickAdd();
                }
            }
            
            // Ctrl/Cmd + N for new moment
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                toggleQuickAdd();
            }
            
            // Number keys for navigation
            if (e.key >= '1' && e.key <= '3') {
                const sections = ['circle', 'stacks', 'camp'];
                const targetSection = sections[parseInt(e.key) - 1];
                const targetButton = document.querySelector(`[data-section="${targetSection}"]`);
                if (targetButton) {
                    targetButton.click();
                }
            }
        });
        
        // Mood button interactions
        document.querySelectorAll('button[type="button"]').forEach(button => {
            if (button.querySelector('span')) { // Mood buttons have spans with emojis
                button.addEventListener('click', function() {
                    // Remove active state from siblings
                    this.parentNode.querySelectorAll('button').forEach(btn => {
                        btn.classList.remove('border-orange-500', 'bg-orange-50');
                    });
                    // Add active state
                    this.classList.add('border-orange-500', 'bg-orange-50');
                });
            }
        });
    });
    </script>
</body>
</html>