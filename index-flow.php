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
        
        /* Navigation Tabs */
        .nav-tab {
            position: relative;
            padding-bottom: 0.75rem;
            color: var(--trail-marker);
            transition: color 0.2s;
        }
        
        .nav-tab.active {
            color: var(--mountain-shadow);
        }
        
        .nav-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--campfire);
            border-radius: 2px 2px 0 0;
        }
    </style>
</head>
<body>
    <!-- Creative Header -->
    <header class="bg-white/80 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-6xl mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Logo with Journey Metaphor -->
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <svg class="w-8 h-8 text-orange-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M13 10l5 8H6l5-8m2-7l7 18H4L11 3z"/>
                        </svg>
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full pulse-dot"></div>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">The Lookout</h1>
                        <p class="text-xs text-gray-500"><?php echo date('l, M j'); ?></p>
                    </div>
                </div>
                
                <!-- Creative User Menu -->
                <div class="flex items-center space-x-3">
                    <!-- Journey Health Indicator -->
                    <div class="flex items-center space-x-1 px-3 py-1 bg-gray-50 rounded-full">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs font-medium text-gray-700"><?php echo $journeyStats['active_journeys']; ?> Active</span>
                    </div>
                    
                    <!-- User Avatar -->
                    <button class="flex items-center space-x-2 p-1.5 hover:bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-pink-500 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-white"><?php echo get_initials($currentUser['name']); ?></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Journey Content -->
    <main class="max-w-6xl mx-auto px-4 py-6">
        
        <!-- Today's Focus - What Needs Attention -->
        <?php if (!empty($todaysFocus)): ?>
        <section class="mb-8">
            <div class="flex items-center space-x-2 mb-4">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-900">Today's Focus</h2>
                <span class="px-2 py-0.5 text-xs font-medium bg-orange-100 text-orange-700 rounded-full">
                    <?php echo count($todaysFocus); ?> need attention
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach (array_slice($todaysFocus, 0, 3) as $journey): ?>
                <div class="journey-card p-5 rounded-xl border border-gray-100 cursor-pointer" 
                     onclick="window.location.href='/journey.php?id=<?php echo $journey['id']; ?>'">
                    
                    <!-- Journey Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-900"><?php echo escapeContent($journey['title']); ?></h3>
                            <p class="text-sm text-gray-500 mt-0.5"><?php echo escapeContent($journey['client_name'] ?? 'Personal'); ?></p>
                        </div>
                        <?php
                        $statusClass = 'status-' . $journey['pulse_status'];
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusClass; ?>">
                            <?php echo ucfirst($journey['pulse_status']); ?>
                        </span>
                    </div>
                    
                    <!-- Journey Progress Story -->
                    <div class="flex items-center space-x-4 text-sm">
                        <div class="flex items-center space-x-1 text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><?php echo $journey['due_date'] ? date('M j', strtotime($journey['due_date'])) : 'Ongoing'; ?></span>
                        </div>
                        <?php if ($journey['balance_due'] > 0): ?>
                        <div class="flex items-center space-x-1 font-medium text-gray-900">
                            <span><?php echo format_currency($journey['balance_due']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Last Moment Preview -->
                    <?php if ($journey['last_moment_at']): ?>
                    <div class="mt-3 pt-3 border-t border-gray-50">
                        <p class="text-xs text-gray-500">
                            Last update <?php echo time_ago($journey['last_moment_at']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Navigation Tabs -->
        <div class="flex items-center space-x-6 mb-6 border-b border-gray-100">
            <button class="nav-tab active" data-tab="stacks">
                <span class="font-medium">The Stacks</span>
                <span class="ml-1 text-xs text-gray-400">(<?php echo count($recentMoments); ?>)</span>
            </button>
            <button class="nav-tab" data-tab="circle">
                <span class="font-medium">Circle</span>
                <span class="ml-1 text-xs text-gray-400">(<?php echo $journeyStats['circle_journeys']; ?>)</span>
            </button>
            <button class="nav-tab" data-tab="camp">
                <span class="font-medium">Camp</span>
                <span class="ml-1 text-xs text-gray-400">(<?php echo $journeyStats['active_journeys']; ?>)</span>
            </button>
        </div>
        
        <!-- Content Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- The Stacks - Moment Timeline (2/3 width) -->
            <div class="lg:col-span-2" id="stacks-content">
                <div class="bg-white rounded-xl p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900 mb-4">Your Journey Timeline</h3>
                    
                    <div class="space-y-6">
                        <?php foreach ($recentMoments as $moment): ?>
                        <div class="moment-item">
                            <div class="flex items-start space-x-3">
                                <div class="flex-1">
                                    <!-- Moment Content -->
                                    <p class="text-gray-800 leading-relaxed">
                                        <?php echo escapeContent($moment['content']); ?>
                                    </p>
                                    
                                    <!-- Moment Meta -->
                                    <div class="flex items-center space-x-3 mt-2">
                                        <span class="text-xs font-medium text-orange-600">
                                            <?php echo escapeContent($moment['journey_title']); ?>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo time_ago($moment['created_at']); ?>
                                        </span>
                                        <?php if ($moment['type'] == 'milestone'): ?>
                                        <span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded-full">
                                            Milestone
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (empty($recentMoments)): ?>
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <p class="text-gray-500">Start logging moments to build your journey story</p>
                        <button class="mt-3 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                            Log Your First Moment
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Journey Health & Stats (1/3 width) -->
            <div class="space-y-4">
                <!-- Journey Pulse -->
                <div class="bg-white rounded-xl p-5 shadow-sm">
                    <h3 class="font-semibold text-gray-900 mb-4">Journey Pulse</h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Healthy</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500" style="width: <?php echo ($journeyStats['healthy_journeys'] / max($journeyStats['active_journeys'], 1)) * 100; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $journeyStats['healthy_journeys'] ?? 0; ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Warning</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-yellow-500" style="width: <?php echo ($journeyStats['warning_journeys'] / max($journeyStats['active_journeys'], 1)) * 100; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $journeyStats['warning_journeys'] ?? 0; ?></span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Critical</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500" style="width: <?php echo ($journeyStats['critical_journeys'] / max($journeyStats['active_journeys'], 1)) * 100; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $journeyStats['critical_journeys'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Flow -->
                <div class="bg-gradient-to-br from-orange-50 to-pink-50 rounded-xl p-5 border border-orange-100">
                    <h3 class="font-semibold text-gray-900 mb-3">Financial Flow</h3>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-gray-600">Outstanding</span>
                            <span class="text-xl font-bold text-gray-900">
                                <?php echo format_currency($journeyStats['total_balance'] ?? 0); ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-baseline">
                            <span class="text-sm text-gray-600">This Month</span>
                            <span class="text-sm font-medium text-gray-700">
                                <?php echo format_currency($journeyStats['month_total'] ?? 0); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="bg-white rounded-xl p-5 shadow-sm">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo $momentStats['total_moments']; ?></p>
                            <p class="text-xs text-gray-500">Total Moments</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo $momentStats['week_moments'] ?? 0; ?></p>
                            <p class="text-xs text-gray-500">This Week</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Creative FAB -->
    <div class="fixed bottom-6 right-6 z-50">
        <button class="fab-main w-14 h-14 rounded-full text-white flex items-center justify-center transition-all duration-300"
                onclick="toggleQuickAdd()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    </div>
    
    <!-- Quick Add Panel -->
    <div id="quick-add-panel" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl p-6 z-40 quick-add">
        <div class="max-w-lg mx-auto">
            <h3 class="font-semibold text-gray-900 mb-4">Log a Moment</h3>
            <textarea 
                class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-orange-500"
                rows="3"
                placeholder="What progress did you make today?"
            ></textarea>
            <div class="flex items-center justify-between mt-4">
                <select class="px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option>Select Journey</option>
                    <?php foreach ($activeJourneys as $j): ?>
                    <option value="<?php echo $j['id']; ?>"><?php echo escapeContent($j['title']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="space-x-2">
                    <button class="px-4 py-2 text-gray-600 hover:text-gray-900" onclick="toggleQuickAdd()">
                        Cancel
                    </button>
                    <button class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        Save Moment
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Toggle Quick Add Panel
    function toggleQuickAdd() {
        const panel = document.getElementById('quick-add-panel');
        panel.classList.toggle('active');
    }
    
    // Tab Navigation
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            // Content switching logic here
        });
    });
    
    // Smooth Animations on Load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.journey-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    </script>
</body>
</html>