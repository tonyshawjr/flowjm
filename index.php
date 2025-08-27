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
    
    <!-- Custom Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Desktop-Constrained Mobile-First Design */
        :root {
            --flow-bg: #FAFAF8;
            --flow-card: #FFFFFF;
            --flow-primary: #FF6B35;
            --flow-text: #2D3436;
            --flow-secondary: #636E72;
            --flow-border: #E1E4E8;
            --flow-success: #00B894;
            --flow-warning: #FDCB6E;
            --flow-critical: #FF6B6B;
            --safe-area-inset-top: env(safe-area-inset-top);
            --safe-area-inset-bottom: env(safe-area-inset-bottom);
        }
        
        * {
            font-family: 'Inter', -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Desktop Background with Depth */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            padding: 0;
        }
        
        /* App Container - Mobile App in Desktop Frame */
        .app-container {
            max-width: 448px; /* Mobile width */
            margin: 0 auto;
            min-height: 100vh;
            background: var(--flow-bg);
            box-shadow: 
                0 0 0 1px rgba(255, 255, 255, 0.1),
                0 4px 24px rgba(0, 0, 0, 0.15),
                0 8px 48px rgba(0, 0, 0, 0.1);
            position: relative;
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
        }
        
        /* Mobile breakpoint - full width */
        @media (max-width: 448px) {
            body {
                background: var(--flow-bg);
            }
            .app-container {
                max-width: 100%;
                box-shadow: none;
            }
        }
        
        /* Desktop breakpoint - add breathing room */
        @media (min-width: 768px) {
            .app-container {
                margin: 20px auto;
                min-height: calc(100vh - 40px);
                border-radius: 24px;
                overflow: hidden;
            }
            
            /* Desktop-specific enhancements */
            .mobile-nav {
                left: 50%;
                right: auto;
                width: 448px;
                transform: translateX(-50%);
                border-radius: 0 0 24px 24px;
            }
            
            .fab {
                left: 50%;
                right: auto;
                transform: translateX(174px); /* Half of 448px - half of FAB width - margin */
            }
            
            .fab:hover {
                transform: translateX(174px) translateY(-2px) scale(1.05);
            }
            
            .fab:active {
                transform: translateX(174px) translateY(0) scale(0.98);
            }
            
            /* Desktop Camp Drawer positioning */
            .camp-drawer {
                left: 50%;
                right: auto;
                transform: translateX(-50%) translateX(448px);
            }
            
            .camp-drawer.open {
                transform: translateX(-50%) translateX(63px); /* Show within container bounds */
            }
            
            /* Desktop Quick Add positioning */
            .quick-add-sheet {
                left: 50%;
                right: auto;
                width: 448px;
                transform: translateX(-50%) translateY(100%);
                border-radius: 24px 24px 0 0;
            }
            
            .quick-add-sheet.open {
                transform: translateX(-50%) translateY(0);
            }
        }
        
        /* Circle - Horizontal Scrollable Stories */
        .circle-container {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 8px 0;
        }
        
        .circle-container::-webkit-scrollbar {
            display: none;
        }
        
        .circle-card {
            scroll-snap-align: center;
            flex-shrink: 0;
            width: 85px;
            height: 85px;
            position: relative;
            cursor: pointer;
            user-select: none;
            margin: 0 6px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .circle-card:hover {
            transform: translateY(-2px) scale(1.05);
        }
        
        .circle-card:active {
            transform: translateY(0) scale(0.98);
        }
        
        .circle-ring {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
            box-shadow: 
                0 4px 20px rgba(255, 107, 53, 0.4), 
                0 2px 8px rgba(255, 107, 53, 0.3),
                0 1px 3px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .circle-card:hover .circle-ring {
            box-shadow: 
                0 8px 30px rgba(255, 107, 53, 0.5), 
                0 4px 15px rgba(255, 107, 53, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.3);
        }
        
        .circle-ring.warning {
            background: linear-gradient(135deg, var(--flow-warning) 0%, #FFA502 100%);
            box-shadow: 0 4px 15px rgba(253, 203, 110, 0.35),
                        0 2px 4px rgba(253, 203, 110, 0.2);
        }
        
        .circle-ring.critical {
            background: linear-gradient(135deg, var(--flow-critical) 0%, #FF4757 100%);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.35),
                        0 2px 4px rgba(255, 107, 107, 0.2);
            animation: pulse-ring 2s ease-in-out infinite;
        }
        
        .circle-content {
            background: linear-gradient(135deg, #FFFFFF 0%, #FEFEFE 100%);
            border-radius: 50%;
            width: calc(100% - 6px);
            height: calc(100% - 6px);
            margin: 3px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            font-weight: 700;
            font-size: 20px;
            color: var(--flow-text);
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9);
        }
        
        /* Stack - Vertical Moment Feed */
        .stack-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #FCFCFC 100%);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 107, 53, 0.1);
            box-shadow: 
                0 2px 12px rgba(0,0,0,0.04),
                0 1px 6px rgba(0,0,0,0.03),
                0 0 0 1px rgba(255, 255, 255, 0.9);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            user-select: none;
            position: relative;
            backdrop-filter: blur(10px);
        }
        
        .stack-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--flow-primary) 0%, #FF8A65 100%);
            border-radius: 16px 16px 0 0;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stack-card:hover::before {
            opacity: 1;
        }
        
        .stack-card:active {
            transform: scale(0.98) translateY(1px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        }
        
        .stack-card:hover {
            box-shadow: 
                0 8px 32px rgba(255, 107, 53, 0.15),
                0 4px 16px rgba(0,0,0,0.08),
                0 0 0 1px rgba(255, 107, 53, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 107, 53, 0.3);
            transform: translateY(-4px) scale(1.02);
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
        
        /* Camp Drawer */
        .camp-drawer {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 85%;
            max-width: 380px;
            background: white;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 60;
            box-shadow: -4px 0 24px rgba(0,0,0,0.12);
            display: flex;
            flex-direction: column;
        }
        
        .camp-drawer.open {
            transform: translateX(0);
        }
        
        .camp-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 59;
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
        
        /* Enhanced Mobile Navigation Bar */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 0 calc(8px + var(--safe-area-inset-bottom));
            z-index: 50;
            box-shadow: 
                0 -1px 0 rgba(255, 255, 255, 0.8),
                0 -1px 3px rgba(0, 0, 0, 0.05);
        }
        
        /* Enhanced Floating Action Button */
        .fab {
            position: fixed;
            bottom: calc(70px + var(--safe-area-inset-bottom));
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 4px 20px rgba(255, 107, 53, 0.4),
                0 2px 8px rgba(255, 107, 53, 0.3),
                0 1px 3px rgba(0, 0, 0, 0.2);
            z-index: 49;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .fab:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 
                0 8px 30px rgba(255, 107, 53, 0.5),
                0 4px 15px rgba(255, 107, 53, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.3);
        }
        
        .fab:active {
            transform: translateY(0) scale(0.98);
        }
        
        /* Quick Add Sheet */
        .quick-add-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 61;
            padding-bottom: var(--safe-area-inset-bottom);
        }
        
        .quick-add-sheet.open {
            transform: translateY(0);
        }
        
        /* Visual Polish */
        .circle-ring {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .circle-ring.warning {
            box-shadow: 0 2px 8px rgba(255,184,0,0.3);
        }
        
        .circle-ring.critical {
            animation: pulse-ring 2s ease-in-out infinite;
        }
        
        @keyframes pulse-ring {
            0%, 100% {
                box-shadow: 0 2px 8px rgba(255,107,107,0.3);
            }
            50% {
                box-shadow: 0 4px 16px rgba(255,107,107,0.5);
            }
        }
        
        /* Enhanced header with depth */
        header {
            backdrop-filter: blur(20px);
            background: rgba(255,255,255,0.95) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 1px 0 rgba(255, 255, 255, 0.8),
                0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        /* Space at bottom for nav with safe area */
        #stack-feed {
            padding-bottom: calc(80px + var(--safe-area-inset-bottom));
        }
        
        /* Enhanced accessibility for screen readers */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Desktop scroll improvements */
        @media (min-width: 768px) {
            .app-container {
                /* Custom scrollbar for desktop */
                scrollbar-width: thin;
                scrollbar-color: rgba(255, 107, 53, 0.3) transparent;
            }
            
            .app-container::-webkit-scrollbar {
                width: 6px;
            }
            
            .app-container::-webkit-scrollbar-track {
                background: transparent;
            }
            
            .app-container::-webkit-scrollbar-thumb {
                background: rgba(255, 107, 53, 0.3);
                border-radius: 3px;
            }
            
            .app-container::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 107, 53, 0.5);
            }
        }
        
        /* Performance Optimizations */
        * {
            /* Hardware acceleration for smooth animations */
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
        }
        
        .circle-card,
        .stack-card,
        .fab,
        .camp-drawer,
        .quick-add-sheet {
            /* Force GPU compositing for smooth animations */
            will-change: transform, opacity;
        }
        
        /* Loading states */
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top-color: var(--flow-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Enhanced focus states for accessibility */
        button:focus-visible,
        select:focus-visible,
        textarea:focus-visible {
            outline: 2px solid var(--flow-primary);
            outline-offset: 2px;
        }
        
        /* Smooth scroll for better UX */
        html {
            scroll-behavior: smooth;
        }
        
        /* Reduce motion for users who prefer it */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        
        /* Better text rendering */
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        /* Enhanced mobile navbar with better touch targets */
        .mobile-nav button {
            min-height: 44px;
            min-width: 44px;
            position: relative;
            overflow: hidden;
        }
        
        .mobile-nav button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 107, 53, 0.1);
            transform: translate(-50%, -50%);
            transition: width 0.3s ease, height 0.3s ease;
        }
        
        .mobile-nav button:active::before {
            width: 40px;
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="app-container">
    <!-- The Lookout Header -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-40">
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-gray-900">The Lookout</h1>
                <span class="text-xs text-gray-500"><?php echo date('M j'); ?></span>
            </div>
            
            <!-- Tent Icon - Opens Camp Drawer -->
            <button onclick="openCampDrawer()" class="p-2 hover:bg-gray-50 rounded-lg" aria-label="Open Camp Drawer">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 21h18L12 3 3 21zm9-13v6m0 2h.01"/>
                </svg>
            </button>
        </div>
    </header>
    
    <!-- Circle - Horizontal Scrollable Priority Journeys -->
    <?php if (!empty($circleJourneys)): ?>
    <section class="bg-gradient-to-r from-orange-50 via-white to-pink-50 border-b border-orange-100 py-5">
        <div class="px-4 mb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Circle</h2>
            <span class="text-xs text-orange-600 font-medium"><?php echo count($circleJourneys); ?> active</span>
        </div>
        
        <div class="circle-container flex space-x-4 px-4 overflow-x-auto">
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
            <div class="circle-card" onclick="viewJourney(<?php echo $journey['id']; ?>)" role="button" tabindex="0" aria-label="View <?php echo escapeContent($journey['title']); ?> journey">
                <div class="circle-ring <?php echo $pulseClass; ?>">
                    <div class="circle-content">
                        <span class="text-lg font-semibold text-gray-700"><?php echo strtoupper($initials); ?></span>
                        <?php if ($journey['pulse_status'] == 'critical'): ?>
                        <div class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full pulse-indicator"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Add New Journey Circle -->
            <div class="circle-card" onclick="createJourney()" role="button" tabindex="0" aria-label="Create new journey">
                <div style="position: absolute; inset: 0; border-radius: 50%; border: 3px dashed rgba(255, 107, 53, 0.3); background: linear-gradient(135deg, rgba(255, 107, 53, 0.03) 0%, rgba(255, 138, 101, 0.03) 100%);">
                    <div class="circle-content" style="background: transparent; border: none;">
                        <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Stack - Vertical Moment Feed -->
    <main class="px-4 py-4 pb-24">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-bold text-gray-800 uppercase tracking-wider">Stack</h2>
            <?php if (!empty($stackMoments)): ?>
            <span class="text-xs text-gray-500">Latest moments</span>
            <?php endif; ?>
        </div>
        
        <div id="stack-feed">
            <?php if (!empty($stackMoments)): ?>
                <?php foreach ($stackMoments as $moment): ?>
                <div class="stack-card swipeable" data-moment-id="<?php echo $moment['id']; ?>" role="article" aria-label="Moment from <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>">
                    <!-- Journey Badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center px-2.5 py-1 bg-gradient-to-r from-orange-500 to-pink-500 text-white text-xs font-bold uppercase tracking-wide rounded-full">
                            <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>
                        </span>
                        <span class="text-xs text-gray-400">
                            <?php echo time_ago($moment['created_at']); ?>
                        </span>
                    </div>
                    
                    <!-- Moment Content -->
                    <p class="text-gray-800 leading-relaxed text-base font-medium">
                        <?php echo escapeContent($moment['content']); ?>
                    </p>
                    
                    <!-- Moment Type Badge -->
                    <?php if (!empty($moment['type']) && $moment['type'] != 'update'): ?>
                    <div class="mt-3">
                        <span class="inline-flex px-3 py-1.5 text-xs font-bold rounded-full shadow-sm
                            <?php echo $moment['type'] == 'milestone' ? 'bg-gradient-to-r from-green-400 to-green-600 text-white' : ''; ?>
                            <?php echo $moment['type'] == 'blocker' ? 'bg-gradient-to-r from-red-400 to-red-600 text-white' : ''; ?>
                            <?php echo $moment['type'] == 'note' ? 'bg-gradient-to-r from-gray-400 to-gray-600 text-white' : ''; ?>">
                            <?php echo ucfirst($moment['type']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Fieldnote Indicator -->
                    <?php if (!empty($moment['has_fieldnote'])): ?>
                    <div class="mt-2 text-xs text-gray-500 italic">
                        📝 Has fieldnote
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Start Your Journey</h3>
                    <p class="text-gray-500 mb-4">Log your first moment to begin</p>
                    <button onclick="openQuickAdd()" class="px-6 py-2 bg-orange-500 text-white rounded-full font-medium" aria-label="Add your first moment">
                        Add Moment
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Camp Drawer Overlay -->
    <div id="camp-overlay" class="camp-overlay" onclick="closeCampDrawer()"></div>
    
    <!-- Camp Drawer -->
    <div id="camp-drawer" class="camp-drawer">
        <!-- Drawer Header -->
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Camp</h2>
                <button onclick="closeCampDrawer()" class="p-2 hover:bg-gray-100 rounded-lg" aria-label="Close Camp Drawer">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Journey List -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-4 space-y-3">
                <?php foreach ($activeJourneys as $journey): ?>
                <div class="p-4 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100" 
                     onclick="viewJourney(<?php echo $journey['id']; ?>)" 
                     role="button" tabindex="0" 
                     aria-label="View <?php echo escapeContent($journey['title']); ?> journey">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-medium text-gray-900"><?php echo escapeContent($journey['title']); ?></h3>
                        <?php 
                        switch ($journey['pulse_status']) {
                            case 'critical':
                                echo '<span class="w-2 h-2 bg-red-500 rounded-full pulse-indicator"></span>';
                                break;
                            case 'warning':
                                echo '<span class="w-2 h-2 bg-yellow-500 rounded-full"></span>';
                                break;
                            default:
                                echo '<span class="w-2 h-2 bg-green-500 rounded-full"></span>';
                                break;
                        }
                        ?>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500"><?php echo escapeContent($journey['client_name'] ?? 'Personal'); ?></span>
                        <?php if ($journey['balance_due'] > 0): ?>
                        <span class="font-medium text-gray-700"><?php echo format_currency($journey['balance_due']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Camp CTA Button -->
        <div class="p-4 border-t border-gray-100 bg-white">
            <button onclick="viewFullCamp()" class="w-full py-3 bg-gray-900 text-white rounded-lg font-medium" aria-label="View full camp with all journeys">
                View Full Camp →
            </button>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <div class="flex items-center justify-around">
            <button class="p-3 text-orange-500" aria-label="Home" aria-current="page">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400" onclick="viewJourneys()" aria-label="View all journeys">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2l6 3 5.447-2.724A1 1 0 0121 3.618v10.764a1 1 0 01-.553.894L15 18l-6-3z"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400" onclick="viewProfile()" aria-label="View profile">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </button>
        </div>
    </nav>
    
    <!-- Floating Action Button -->
    <button class="fab" onclick="openQuickAdd()" aria-label="Add new moment">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>
    
    <!-- Quick Add Sheet -->
    <div id="quick-add-sheet" class="quick-add-sheet">
        <div class="p-4">
            <!-- Drag Handle -->
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Log a Moment</h3>
            
            <textarea 
                id="moment-content"
                class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200"
                rows="4"
                placeholder="What progress did you make?"
                oninput="autoResizeTextarea(this)"
                autofocus
            ></textarea>
            
            <select id="journey-select" class="w-full mt-3 p-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200">
                <option value="">Select Journey</option>
                <?php foreach ($activeJourneys as $j): ?>
                <option value="<?php echo $j['id']; ?>"><?php echo escapeContent($j['title']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <div class="flex space-x-2 mt-4">
                <button onclick="closeQuickAdd()" class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 active:bg-gray-100 transition-colors duration-200">
                    Cancel
                </button>
                <button onclick="saveMoment()" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 active:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                    Save Moment
                </button>
            </div>
        </div>
    </div>
    
    <script>
    // Enhanced Camp Drawer Functions
    function openCampDrawer() {
        const drawer = document.getElementById('camp-drawer');
        const overlay = document.getElementById('camp-overlay');
        
        drawer.classList.add('open');
        overlay.classList.add('active');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
        // Add subtle entrance animation
        drawer.style.transform = 'translateX(100%)';
        setTimeout(() => {
            drawer.style.transform = '';
        }, 50);
    }
    
    function closeCampDrawer() {
        const drawer = document.getElementById('camp-drawer');
        const overlay = document.getElementById('camp-overlay');
        
        drawer.classList.remove('open');
        overlay.classList.remove('active');
        
        // Re-enable body scroll
        document.body.style.overflow = '';
    }
    
    // Enhanced Quick Add Functions
    function openQuickAdd() {
        const sheet = document.getElementById('quick-add-sheet');
        const overlay = document.getElementById('camp-overlay');
        const textarea = document.getElementById('moment-content');
        
        sheet.classList.add('open');
        overlay.classList.add('active');
        
        // Enhanced focus with better UX
        setTimeout(() => {
            textarea.focus();
            
            // Auto-resize textarea based on content
            textarea.style.height = 'auto';
            textarea.style.height = Math.max(textarea.scrollHeight, 100) + 'px';
        }, 350);
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    function closeQuickAdd() {
        const sheet = document.getElementById('quick-add-sheet');
        const overlay = document.getElementById('camp-overlay');
        
        sheet.classList.remove('open');
        overlay.classList.remove('active');
        
        // Re-enable body scroll
        document.body.style.overflow = '';
        
        // Clear form with a slight delay for better UX
        setTimeout(() => {
            const textarea = document.getElementById('moment-content');
            const select = document.getElementById('journey-select');
            
            if (textarea.value.trim() === '' || confirm('Discard unsaved changes?')) {
                textarea.value = '';
                select.value = '';
                textarea.style.height = 'auto';
            }
        }, 300);
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
    
    // Save Moment with proper AJAX
    async function saveMoment() {
        const content = document.getElementById('moment-content').value.trim();
        const journeyId = document.getElementById('journey-select').value;
        const saveBtn = document.querySelector('[onclick="saveMoment()"]');
        
        if (!content || !journeyId) {
            showToast('Please enter content and select a journey', 'error');
            return;
        }
        
        // Show loading state
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        
        try {
            const response = await fetch('/api/moments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: content,
                    journey_id: parseInt(journeyId),
                    type: 'update'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Moment saved successfully!', 'success');
                // Clear form
                document.getElementById('moment-content').value = '';
                document.getElementById('journey-select').value = '';
                closeQuickAdd();
                
                // Refresh the stack feed
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Failed to save moment');
            }
        } catch (error) {
            console.error('Error saving moment:', error);
            showToast('Failed to save moment. Please try again.', 'error');
        } finally {
            // Reset button state
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Moment';
        }
    }
    
    // Enhanced Swipe Gesture Support for Stack Cards
    function initSwipeGestures() {
        let startX = null;
        let startY = null;
        let currentCard = null;
        let isHorizontalSwipe = null;
        
        document.querySelectorAll('.swipeable').forEach(card => {
            card.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
                currentCard = card;
                isHorizontalSwipe = null;
                
                // Add active state
                card.style.transition = 'none';
            }, { passive: true });
            
            card.addEventListener('touchmove', (e) => {
                if (!startX || !startY) return;
                
                const x = e.touches[0].clientX;
                const y = e.touches[0].clientY;
                const diffX = x - startX;
                const diffY = y - startY;
                
                // Determine swipe direction on first significant movement
                if (isHorizontalSwipe === null && (Math.abs(diffX) > 10 || Math.abs(diffY) > 10)) {
                    isHorizontalSwipe = Math.abs(diffX) > Math.abs(diffY);
                }
                
                // Only handle horizontal swipes
                if (isHorizontalSwipe && Math.abs(diffX) > 20) {
                    e.preventDefault();
                    
                    const progress = Math.min(Math.abs(diffX) / 150, 1);
                    const translateX = diffX * 0.8; // Slightly constrained movement
                    const opacity = 1 - (progress * 0.3);
                    const scale = 1 - (progress * 0.05);
                    
                    card.style.transform = `translateX(${translateX}px) scale(${scale})`;
                    card.style.opacity = opacity;
                    
                    // Add background color hints
                    if (diffX > 0) {
                        card.style.background = `linear-gradient(90deg, rgba(34, 197, 94, 0.1) 0%, rgba(255, 255, 255, 1) 20%)`;
                    } else {
                        card.style.background = `linear-gradient(270deg, rgba(239, 68, 68, 0.1) 0%, rgba(255, 255, 255, 1) 20%)`;
                    }
                }
            }, { passive: false });
            
            card.addEventListener('touchend', (e) => {
                if (!startX || !currentCard) return;
                
                const endX = e.changedTouches[0].clientX;
                const diff = endX - startX;
                const threshold = 100;
                
                card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                
                if (Math.abs(diff) > threshold) {
                    // Swipe action triggered
                    const direction = diff > 0 ? 'right' : 'left';
                    
                    card.style.transform = `translateX(${diff > 0 ? '100%' : '-100%'}) scale(0.9)`;
                    card.style.opacity = '0';
                    
                    // Haptic feedback
                    if (navigator.vibrate) navigator.vibrate(30);
                    
                    // Show action feedback
                    const action = direction === 'right' ? 'Archived' : 'Dismissed';
                    showToast(`${action} moment`, 'info');
                    
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                } else {
                    // Snap back with spring animation
                    card.style.transform = 'translateX(0) scale(1)';
                    card.style.opacity = '1';
                    card.style.background = '';
                }
                
                // Reset state
                setTimeout(() => {
                    if (card.style.display !== 'none') {
                        card.style.transition = '';
                        card.style.background = '';
                    }
                }, 300);
                
                startX = null;
                startY = null;
                currentCard = null;
                isHorizontalSwipe = null;
            }, { passive: true });
        });
    }
    
    // Initialize swipe gestures
    initSwipeGestures();
    
    // Enhanced Pull to Refresh with better UX
    let pullStartY = null;
    let isPulling = false;
    let pullThreshold = 80;
    const appContainer = document.querySelector('.app-container');
    
    function initPullToRefresh() {
        document.addEventListener('touchstart', (e) => {
            if (window.scrollY === 0) {
                pullStartY = e.touches[0].clientY;
                isPulling = false;
            }
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            if (!pullStartY || window.scrollY > 0) return;
            
            const y = e.touches[0].clientY;
            const diff = y - pullStartY;
            
            if (diff > 0 && diff < 150) {
                isPulling = true;
                const progress = Math.min(diff / pullThreshold, 1);
                const translateY = diff * 0.4;
                
                appContainer.style.transform = `translateY(${translateY}px)`;
                appContainer.style.transition = 'none';
                
                // Add visual feedback
                const header = document.querySelector('header');
                header.style.opacity = 1 - (progress * 0.2);
                
                // Haptic feedback on mobile
                if (diff > pullThreshold && !isPulling) {
                    if (navigator.vibrate) navigator.vibrate(50);
                }
            }
        }, { passive: false });
        
        document.addEventListener('touchend', (e) => {
            if (isPulling && pullStartY) {
                const endY = e.changedTouches[0].clientY;
                const diff = endY - pullStartY;
                
                appContainer.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                appContainer.style.transform = 'translateY(0)';
                
                const header = document.querySelector('header');
                header.style.opacity = '1';
                
                // Trigger refresh if pulled far enough
                if (diff > pullThreshold) {
                    showToast('Refreshing...', 'info');
                    setTimeout(() => {
                        location.reload();
                    }, 300);
                }
            }
            
            pullStartY = null;
            isPulling = false;
        }, { passive: true });
    }
    
    // Toast notification system
    function showToast(message, type = 'info') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast-notification');
        if (existingToast) existingToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `toast-notification fixed top-4 left-1/2 transform -translate-x-1/2 z-[70] px-4 py-2 rounded-lg text-white text-sm font-medium transition-all duration-300 opacity-0 translate-y-[-20px]`;
        
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        toast.classList.add(colors[type] || colors.info);
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translate(-50%, 0)';
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, -20px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Auto-resize textarea function
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(textarea.scrollHeight, 100) + 'px';
        
        // Update sheet height if needed
        const sheet = document.getElementById('quick-add-sheet');
        if (sheet.classList.contains('open')) {
            sheet.style.maxHeight = 'calc(100vh - 100px)';
        }
    }
    
    // Enhanced Navigation Functions with Loading States
    function viewJourney(id) {
        showToast('Loading journey...', 'info');
        window.location.href = `/journey.php?id=${id}`;
    }
    
    function createJourney() {
        showToast('Creating new journey...', 'info');
        window.location.href = '/journey/create.php';
    }
    
    function viewFullCamp() {
        showToast('Loading camp...', 'info');
        window.location.href = '/camp.php';
    }
    
    function viewJourneys() {
        showToast('Loading journeys...', 'info');
        window.location.href = '/journeys.php';
    }
    
    function viewProfile() {
        showToast('Loading profile...', 'info');
        window.location.href = '/profile.php';
    }
    
    // Intersection Observer for smooth animations
    function initAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // Observe stack cards for staggered entrance
        document.querySelectorAll('.stack-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
            observer.observe(card);
        });
    }
    
    // Keyboard shortcuts
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Cmd/Ctrl + K to open quick add
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                if (!document.getElementById('quick-add-sheet').classList.contains('open')) {
                    openQuickAdd();
                }
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                if (document.getElementById('quick-add-sheet').classList.contains('open')) {
                    closeQuickAdd();
                } else if (document.getElementById('camp-drawer').classList.contains('open')) {
                    closeCampDrawer();
                }
            }
            
            // Enter to save (when in textarea)
            if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.id === 'moment-content') {
                    e.preventDefault();
                    saveMoment();
                }
            }
        });
    }
    
    // Performance monitoring
    function initPerformanceMonitoring() {
        // Monitor Core Web Vitals
        if ('web-vital' in window) {
            // This would connect to actual performance monitoring
            console.log('Performance monitoring initialized');
        }
        
        // Lazy load images if any
        const lazyImages = document.querySelectorAll('img[data-src]');
        if (lazyImages.length > 0) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(img => imageObserver.observe(img));
        }
    }
    
    // Keyboard navigation for interactive elements
    function initKeyboardNavigation() {
        // Add keyboard support for circle cards
        document.querySelectorAll('.circle-card[role="button"]').forEach(card => {
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });
        
        // Add keyboard support for journey cards in camp
        document.querySelectorAll('[role="button"][onclick*="viewJourney"]').forEach(card => {
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
        });
    }
    
    // Initialize everything when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        initPullToRefresh();
        initAnimations();
        initKeyboardShortcuts();
        initKeyboardNavigation();
        initPerformanceMonitoring();
        
        // Add loading states and tactile feedback to navigation
        const navButtons = document.querySelectorAll('nav button, .circle-card, .stack-card');
        navButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                button.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    button.style.transform = '';
                }, 150);
                
                // Haptic feedback on mobile
                if (navigator.vibrate) navigator.vibrate(10);
            });
            
            // Add focus/blur visual feedback
            button.addEventListener('focus', (e) => {
                button.style.boxShadow = '0 0 0 3px rgba(255, 107, 53, 0.2)';
            });
            
            button.addEventListener('blur', (e) => {
                button.style.boxShadow = '';
            });
        });
        
        console.log('FlowJM frontend initialized successfully - Enhanced UX ready!');
    });
    </script>
    </div> <!-- End app-container -->
</body>
</html>