<?php
/**
 * FlowJM - The Lookout
 * Main dashboard experience - like a social app but for creative work
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

// Get Circle Journeys (sorted by relevance: deadlines, overdue, recent activity)
$circleJourneys = $journey->getCircleJourneys($_SESSION['user_id']);

// Get all active journeys for Camp drawer
$activeJourneys = $journey->getByUserId($_SESSION['user_id'], 'active', 1, 50);

// Get Stack - recent moments across all journeys
$stackMoments = $moment->getRecentByUserId($_SESSION['user_id'], 1, 30);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="<?php echo Auth::generateCsrfToken(); ?>">
    <title>The Lookout - FlowJM</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'flow-purple': '#8B5CF6',
                        'flow-blue': '#1E3A8A',
                        'flow-dark': '#0F172A'
                    },
                    backdropBlur: {
                        '20': '20px'
                    }
                }
            }
        }
    </script>
    
    <!-- Custom Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Dark Gradient Design System - Purple to Blue */
        :root {
            --flow-bg-gradient: linear-gradient(180deg, #6B46C1 0%, #1E3A8A 100%);
            --flow-card: rgba(15, 23, 42, 0.6);
            --flow-card-border: rgba(148, 163, 184, 0.1);
            --flow-glass: rgba(15, 23, 42, 0.7);
            --flow-primary: #8B5CF6;
            --flow-accent: #A855F7;
            --flow-text: #F1F5F9;
            --flow-text-secondary: #CBD5E1;
            --flow-text-muted: #64748B;
            --flow-success: #10B981;
            --flow-warning: #F59E0B;
            --flow-critical: #EF4444;
            --safe-area-inset-top: env(safe-area-inset-top);
            --safe-area-inset-bottom: env(safe-area-inset-bottom);
        }
        
        * {
            font-family: 'Inter', -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            background: var(--flow-bg-gradient);
            min-height: 100vh;
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
            overflow-x: hidden;
            color: var(--flow-text);
        }
        
        /* Glassmorphic Card Base */
        .glass-card {
            background: var(--flow-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--flow-card-border);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        /* Circle - Journey Cards in Dark Design */
        .circle-container {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .circle-container::-webkit-scrollbar {
            display: none;
        }
        
        .circle-journey-card {
            scroll-snap-align: start;
            flex-shrink: 0;
            width: 280px;
            background: var(--flow-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--flow-card-border);
            border-radius: 16px;
            padding: 20px;
            margin-right: 16px;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }
        
        .circle-journey-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        
        .circle-journey-card:active {
            transform: scale(0.98);
        }
        
        .circle-card {
            scroll-snap-align: center;
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            position: relative;
            cursor: pointer;
            user-select: none;
        }
        
        .circle-ring {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--flow-success) 0%, #059669 100%);
        }
        
        .circle-ring.warning {
            background: linear-gradient(135deg, var(--flow-warning) 0%, #D97706 100%);
        }
        
        .circle-ring.critical {
            background: linear-gradient(135deg, var(--flow-critical) 0%, #DC2626 100%);
        }
        
        .circle-content {
            background: var(--flow-card);
            backdrop-filter: blur(20px);
            border-radius: 50%;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            color: var(--flow-text);
        }
        
        /* Stack - Dark Glassmorphic Moment Cards */
        .stack-card {
            background: var(--flow-card);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--flow-card-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
            color: var(--flow-text);
        }
        
        .stack-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
        }
        
        .stack-card:active {
            transform: scale(0.98);
        }
        
        /* Swipe Gesture Support */
        .swipeable {
            touch-action: pan-y;
            position: relative;
        }
        
        .swipeable.swiping {
            transition: transform 0.1s ease-out;
        }
        
        .swipeable.swiped-left {
            transform: translateX(-100px);
        }
        
        .swipeable.swiped-right {
            transform: translateX(100px);
        }
        
        /* Dark Camp Drawer */
        .camp-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 85%;
            max-width: 380px;
            background: var(--flow-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid var(--flow-card-border);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 60;
            box-shadow: -8px 0 40px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            color: var(--flow-text);
        }
        
        .camp-drawer.open {
            transform: translateX(0);
        }
        
        .camp-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 59;
            backdrop-filter: blur(4px);
        }
        
        .camp-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* Pulse Indicator Animation */
        @keyframes pulse-dot {
            0%, 100% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.3);
                opacity: 0.6;
            }
        }
        
        .pulse-indicator {
            animation: pulse-dot 2s ease-in-out infinite;
        }
        
        /* Dark Mobile Navigation Bar */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--flow-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--flow-card-border);
            padding: 8px 0 calc(8px + var(--safe-area-inset-bottom));
            z-index: 50;
        }
        
        /* Purple Floating Action Button */
        .fab {
            position: fixed;
            bottom: calc(70px + var(--safe-area-inset-bottom));
            right: 20px;
            width: 56px;
            height: 56px;
            background: var(--flow-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(139, 92, 246, 0.4);
            z-index: 49;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .fab:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 40px rgba(139, 92, 246, 0.5);
        }
        
        .fab:active {
            transform: scale(0.95);
        }
        
        /* Dark Quick Add Sheet */
        .quick-add-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--flow-glass);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--flow-card-border);
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 61;
            padding-bottom: var(--safe-area-inset-bottom);
            color: var(--flow-text);
        }
        
        /* Dark Form Controls */
        .dark-input {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--flow-card-border);
            color: var(--flow-text);
            border-radius: 12px;
        }
        
        .dark-input:focus {
            outline: none;
            border-color: var(--flow-primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .dark-input::placeholder {
            color: var(--flow-text-muted);
        }
        
        /* Purple CTA Buttons */
        .purple-btn {
            background: var(--flow-primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 16px rgba(139, 92, 246, 0.3);
        }
        
        .purple-btn:hover {
            background: var(--flow-accent);
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(139, 92, 246, 0.4);
        }
        
        .purple-btn:active {
            transform: translateY(0);
        }
        
        /* Dark Secondary Button */
        .dark-btn-secondary {
            background: rgba(15, 23, 42, 0.6);
            color: var(--flow-text);
            border: 1px solid var(--flow-card-border);
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .dark-btn-secondary:hover {
            background: rgba(15, 23, 42, 0.8);
            border-color: var(--flow-primary);
        }
        
        .quick-add-sheet.open {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Dark Lookout Header -->
    <header class="glass-card mx-4 mt-4 sticky top-4 z-40">
        <div class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-white">The Lookout</h1>
                <span class="text-sm text-gray-300"><?php echo date('M j'); ?></span>
            </div>
            
            <!-- Tent Icon - Opens Camp Drawer -->
            <button onclick="openCampDrawer()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 21h18L12 3 3 21zm9-13v6m0 2h.01"/>
                </svg>
            </button>
        </div>
    </header>
    
    <!-- Circle - Journey Cards -->
    <?php if (!empty($circleJourneys)): ?>
    <section class="px-4 py-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white mb-2">Circle</h2>
            <p class="text-sm text-gray-300">Active journeys that need your attention</p>
        </div>
        
        <div class="circle-container flex overflow-x-auto">
            <?php foreach ($circleJourneys as $journey): ?>
            <?php 
                $pulseClass = '';
                if ($journey['pulse_status'] == 'warning') $pulseClass = 'warning';
                if ($journey['pulse_status'] == 'critical') $pulseClass = 'critical';
                
                // Get initials or first letter
                $initials = substr($journey['title'], 0, 1);
                if ($journey['client_name']) {
                    $words = explode(' ', $journey['client_name']);
                    if (count($words) > 1) {
                        $initials = substr($words[0], 0, 1) . substr($words[1], 0, 1);
                    }
                }
            ?>
            <div class="circle-journey-card" onclick="viewJourney(<?php echo $journey['id']; ?>)">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                        <span class="text-white font-bold text-lg"><?php echo strtoupper($initials); ?></span>
                    </div>
                    <?php if ($journey['pulse_status'] == 'critical'): ?>
                    <div class="w-3 h-3 bg-red-500 rounded-full pulse-indicator"></div>
                    <?php elseif ($journey['pulse_status'] == 'warning'): ?>
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <?php else: ?>
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <?php endif; ?>
                </div>
                
                <h3 class="font-semibold text-white text-lg mb-2 leading-tight"><?php echo escapeContent($journey['title']); ?></h3>
                
                <?php if ($journey['client_name']): ?>
                <p class="text-gray-300 text-sm mb-3"><?php echo escapeContent($journey['client_name']); ?></p>
                <?php endif; ?>
                
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">
                        <?php echo $journey['moment_count'] ?? 0; ?> moments
                    </span>
                    <?php if ($journey['balance_due'] > 0): ?>
                    <span class="text-sm font-semibold text-green-400"><?php echo format_currency($journey['balance_due']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Add New Journey Card -->
            <div class="circle-journey-card border-2 border-dashed border-gray-500 hover:border-purple-500" onclick="createJourney()">
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <div class="w-12 h-12 rounded-full border-2 border-dashed border-gray-500 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <p class="text-gray-400 text-sm font-medium">New Journey</p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Stack - Dark Glassmorphic Moment Feed -->
    <main class="px-4 py-6 pb-24">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-white mb-2">Stacks</h2>
            <p class="text-sm text-gray-300">Recent moments across all journeys</p>
        </div>
        
        <div id="stack-feed">
            <?php if (!empty($stackMoments)): ?>
                <?php foreach ($stackMoments as $moment): ?>
                <div class="stack-card swipeable" data-moment-id="<?php echo $moment['id']; ?>">
                    <!-- Journey Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-semibold text-purple-400 uppercase tracking-wide">
                            <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>
                        </span>
                        <span class="text-xs text-gray-400">
                            <?php echo time_ago($moment['created_at']); ?>
                        </span>
                    </div>
                    
                    <!-- Moment Content -->
                    <p class="text-gray-100 leading-relaxed text-base">
                        <?php echo escapeContent($moment['content']); ?>
                    </p>
                    
                    <!-- Moment Type Badge -->
                    <?php if (!empty($moment['type']) && $moment['type'] != 'update'): ?>
                    <div class="mt-4">
                        <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full
                            <?php echo $moment['type'] == 'milestone' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : ''; ?>
                            <?php echo $moment['type'] == 'blocker' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : ''; ?>
                            <?php echo $moment['type'] == 'note' ? 'bg-gray-500/20 text-gray-300 border border-gray-500/30' : ''; ?>">
                            <?php echo ucfirst($moment['type']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Fieldnote Indicator -->
                    <?php if (!empty($moment['has_fieldnote'])): ?>
                    <div class="mt-3 flex items-center text-xs text-gray-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Has fieldnote
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Dark Empty State -->
                <div class="glass-card p-8 text-center">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-3">Start Your Journey</h3>
                    <p class="text-gray-300 mb-6">Log your first moment to begin tracking progress</p>
                    <button onclick="openQuickAdd()" class="purple-btn px-8 py-3 rounded-full font-semibold">
                        Add Moment
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Camp Drawer Overlay -->
    <div id="camp-overlay" class="camp-overlay" onclick="closeCampDrawer()"></div>
    
    <!-- Dark Camp Drawer -->
    <div id="camp-drawer" class="camp-drawer">
        <!-- Drawer Header -->
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">Camp</h2>
                <button onclick="closeCampDrawer()" class="p-2 hover:bg-white/10 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Journey List -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4 space-y-4">
                <?php foreach ($activeJourneys as $journey): ?>
                <div class="glass-card p-5 cursor-pointer hover:bg-white/5" 
                     onclick="viewJourney(<?php echo $journey['id']; ?>)">
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="font-semibold text-white text-lg"><?php echo escapeContent($journey['title']); ?></h3>
                        <?php if ($journey['pulse_status'] == 'critical'): ?>
                        <span class="w-3 h-3 bg-red-500 rounded-full pulse-indicator"></span>
                        <?php elseif ($journey['pulse_status'] == 'warning'): ?>
                        <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                        <?php else: ?>
                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300"><?php echo escapeContent($journey['client_name'] ?? 'Personal'); ?></span>
                        <?php if ($journey['balance_due'] > 0): ?>
                        <span class="font-semibold text-green-400"><?php echo format_currency($journey['balance_due']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Visit Camp Button -->
        <div class="p-6 border-t border-white/10">
            <button onclick="viewFullCamp()" class="w-full py-4 purple-btn text-white rounded-xl font-semibold text-lg">
                Visit Camp →
            </button>
        </div>
    </div>
    
    <!-- Dark Mobile Navigation -->
    <nav class="mobile-nav">
        <div class="flex items-center justify-around px-4">
            <button class="p-3 text-purple-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400 hover:text-white transition-colors" onclick="viewJourneys()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2l6 3 5.447-2.724A1 1 0 0121 3.618v10.764a1 1 0 01-.553.894L15 18l-6-3z"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400 hover:text-white transition-colors" onclick="viewProfile()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </button>
        </div>
    </nav>
    
    <!-- Floating Action Button -->
    <button class="fab" onclick="openQuickAdd()">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>
    
    <!-- Dark Quick Add Sheet -->
    <div id="quick-add-sheet" class="quick-add-sheet">
        <div class="p-6">
            <!-- Drag Handle -->
            <div class="w-12 h-1 bg-gray-500 rounded-full mx-auto mb-6"></div>
            
            <h3 class="text-xl font-semibold text-white mb-6">Log a Moment</h3>
            
            <textarea 
                id="moment-content"
                class="w-full p-4 dark-input resize-none text-base"
                rows="4"
                placeholder="What progress did you make?"
                autofocus
            ></textarea>
            
            <select id="journey-select" class="w-full mt-4 p-4 dark-input">
                <option value="" style="background: #0F172A; color: #64748B;">Select Journey</option>
                <?php foreach ($activeJourneys as $j): ?>
                <option value="<?php echo $j['id']; ?>" style="background: #0F172A; color: #F1F5F9;"><?php echo escapeContent($j['title']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <div class="flex space-x-3 mt-6">
                <button onclick="closeQuickAdd()" class="flex-1 py-4 dark-btn-secondary font-semibold">
                    Cancel
                </button>
                <button onclick="saveMoment()" class="flex-1 py-4 purple-btn font-semibold">
                    Save Moment
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Camp Drawer Functions
    function openCampDrawer() {
        document.getElementById('camp-drawer').classList.add('open');
        document.getElementById('camp-overlay').classList.add('active');
    }
    
    function closeCampDrawer() {
        document.getElementById('camp-drawer').classList.remove('open');
        document.getElementById('camp-overlay').classList.remove('active');
    }
    
    // Quick Add Functions
    function openQuickAdd() {
        document.getElementById('quick-add-sheet').classList.add('open');
        document.getElementById('camp-overlay').classList.add('active');
        // Focus on textarea
        setTimeout(() => {
            document.getElementById('moment-content').focus();
        }, 300);
    }
    
    function closeQuickAdd() {
        document.getElementById('quick-add-sheet').classList.remove('open');
        document.getElementById('camp-overlay').classList.remove('active');
    }
    
    // Navigation Functions
    function viewJourney(id) {
        window.location.href = `/journey.php?id=${id}`;
    }
    
    function createJourney() {
        window.location.href = '/journey/create.php';
    }
    
    function viewFullCamp() {
        window.location.href = '/camp.php';
    }
    
    function viewJourneys() {
        window.location.href = '/journeys.php';
    }
    
    function viewProfile() {
        window.location.href = '/profile.php';
    }
    
    // Save Moment
    function saveMoment() {
        const content = document.getElementById('moment-content').value;
        const journeyId = document.getElementById('journey-select').value;
        
        if (!content || !journeyId) {
            alert('Please enter content and select a journey');
            return;
        }
        
        // TODO: Implement AJAX save
        console.log('Saving moment:', { content, journeyId });
        closeQuickAdd();
    }
    
    // Swipe Gesture Support for Stack Cards
    let startX = null;
    let currentCard = null;
    
    document.querySelectorAll('.swipeable').forEach(card => {
        card.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            currentCard = card;
        });
        
        card.addEventListener('touchmove', (e) => {
            if (!startX) return;
            
            const x = e.touches[0].clientX;
            const diff = x - startX;
            
            if (Math.abs(diff) > 50) {
                card.style.transform = `translateX(${diff}px)`;
                card.style.opacity = 1 - Math.abs(diff) / 200;
            }
        });
        
        card.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const diff = endX - startX;
            
            if (Math.abs(diff) > 100) {
                // Swiped enough to trigger action
                card.style.transition = 'all 0.3s ease';
                card.style.transform = `translateX(${diff > 0 ? '100%' : '-100%'})`;
                card.style.opacity = '0';
                
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            } else {
                // Snap back
                card.style.transition = 'all 0.2s ease';
                card.style.transform = 'translateX(0)';
                card.style.opacity = '1';
            }
            
            startX = null;
            currentCard = null;
        });
    });
    
    // Pull to Refresh
    let pullStartY = null;
    let isPulling = false;
    
    document.addEventListener('touchstart', (e) => {
        if (window.scrollY === 0) {
            pullStartY = e.touches[0].clientY;
        }
    });
    
    document.addEventListener('touchmove', (e) => {
        if (!pullStartY) return;
        
        const y = e.touches[0].clientY;
        const diff = y - pullStartY;
        
        if (diff > 0 && diff < 150) {
            isPulling = true;
            document.body.style.transform = `translateY(${diff / 2}px)`;
        }
    });
    
    document.addEventListener('touchend', () => {
        if (isPulling && pullStartY) {
            document.body.style.transition = 'transform 0.3s ease';
            document.body.style.transform = 'translateY(0)';
            
            // Trigger refresh
            if (pullStartY > 100) {
                location.reload();
            }
        }
        
        pullStartY = null;
        isPulling = false;
    });
    </script>
</body>
</html>