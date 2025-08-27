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
        /* Mobile-First Creative Journal Design */
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
        
        body {
            background: var(--flow-bg);
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
            overflow-x: hidden;
        }
        
        /* Circle - Horizontal Scrollable Stories */
        .circle-container {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        
        .circle-container::-webkit-scrollbar {
            display: none;
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
            background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
        }
        
        .circle-ring.warning {
            background: linear-gradient(135deg, var(--flow-warning) 0%, #FFA502 100%);
        }
        
        .circle-ring.critical {
            background: linear-gradient(135deg, var(--flow-critical) 0%, #FF4757 100%);
        }
        
        .circle-content {
            background: white;
            border-radius: 50%;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        /* Stack - Vertical Moment Feed */
        .stack-card {
            background: var(--flow-card);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
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
        
        /* Mobile Navigation Bar */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--flow-border);
            padding: 8px 0 calc(8px + var(--safe-area-inset-bottom));
            z-index: 50;
        }
        
        /* Floating Action Button */
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
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            z-index: 49;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .fab:active {
            transform: scale(0.95);
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
        
        /* Better header */
        header {
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95) !important;
        }
        
        /* Space at bottom for nav */
        #stack-feed {
            padding-bottom: 80px;
        }
    </style>
</head>
<body>
    <!-- The Lookout Header -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-40">
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-bold text-gray-900">The Lookout</h1>
                <span class="text-xs text-gray-500"><?php echo date('M j'); ?></span>
            </div>
            
            <!-- Tent Icon - Opens Camp Drawer -->
            <button onclick="openCampDrawer()" class="p-2 hover:bg-gray-50 rounded-lg">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 21h18L12 3 3 21zm9-13v6m0 2h.01"/>
                </svg>
            </button>
        </div>
    </header>
    
    <!-- Circle - Horizontal Scrollable Priority Journeys -->
    <?php if (!empty($circleJourneys)): ?>
    <section class="bg-white border-b border-gray-100 py-4">
        <div class="px-4 mb-3">
            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Circle</h2>
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
            <div class="circle-card" onclick="viewJourney(<?php echo $journey['id']; ?>)">
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
            <div class="circle-card" onclick="createJourney()">
                <div class="circle-content border-2 border-dashed border-gray-300">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Stack - Vertical Moment Feed -->
    <main class="px-4 py-4 pb-24">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Stack</h2>
        </div>
        
        <div id="stack-feed">
            <?php if (!empty($stackMoments)): ?>
                <?php foreach ($stackMoments as $moment): ?>
                <div class="stack-card swipeable" data-moment-id="<?php echo $moment['id']; ?>">
                    <!-- Journey Badge -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-semibold text-orange-600 uppercase tracking-wide">
                            <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>
                        </span>
                        <span class="text-xs text-gray-400">
                            <?php echo time_ago($moment['created_at']); ?>
                        </span>
                    </div>
                    
                    <!-- Moment Content -->
                    <p class="text-gray-800 leading-relaxed">
                        <?php echo escapeContent($moment['content']); ?>
                    </p>
                    
                    <!-- Moment Type Badge -->
                    <?php if (!empty($moment['type']) && $moment['type'] != 'update'): ?>
                    <div class="mt-3">
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                            <?php echo $moment['type'] == 'milestone' ? 'bg-green-100 text-green-700' : ''; ?>
                            <?php echo $moment['type'] == 'blocker' ? 'bg-red-100 text-red-700' : ''; ?>
                            <?php echo $moment['type'] == 'note' ? 'bg-gray-100 text-gray-700' : ''; ?>">
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
                    <button onclick="openQuickAdd()" class="px-6 py-2 bg-orange-500 text-white rounded-full font-medium">
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
                <button onclick="closeCampDrawer()" class="p-2 hover:bg-gray-100 rounded-lg">
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
                     onclick="viewJourney(<?php echo $journey['id']; ?>)">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="font-medium text-gray-900"><?php echo escapeContent($journey['title']); ?></h3>
                        <?php if ($journey['pulse_status'] == 'critical'): ?>
                        <span class="w-2 h-2 bg-red-500 rounded-full pulse-indicator"></span>
                        <?php elseif ($journey['pulse_status'] == 'warning'): ?>
                        <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                        <?php else: ?>
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                        <?php endif; ?>
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
            <button onclick="viewFullCamp()" class="w-full py-3 bg-gray-900 text-white rounded-lg font-medium">
                View Full Camp →
            </button>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <div class="flex items-center justify-around">
            <button class="p-3 text-orange-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400" onclick="viewJourneys()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2l6 3 5.447-2.724A1 1 0 0121 3.618v10.764a1 1 0 01-.553.894L15 18l-6-3z"/>
                </svg>
            </button>
            <button class="p-3 text-gray-400" onclick="viewProfile()">
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
    
    <!-- Quick Add Sheet -->
    <div id="quick-add-sheet" class="quick-add-sheet">
        <div class="p-4">
            <!-- Drag Handle -->
            <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
            
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Log a Moment</h3>
            
            <textarea 
                id="moment-content"
                class="w-full p-3 border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-orange-500"
                rows="4"
                placeholder="What progress did you make?"
                autofocus
            ></textarea>
            
            <select id="journey-select" class="w-full mt-3 p-3 border border-gray-200 rounded-lg">
                <option value="">Select Journey</option>
                <?php foreach ($activeJourneys as $j): ?>
                <option value="<?php echo $j['id']; ?>"><?php echo escapeContent($j['title']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <div class="flex space-x-2 mt-4">
                <button onclick="closeQuickAdd()" class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium">
                    Cancel
                </button>
                <button onclick="saveMoment()" class="flex-1 py-3 bg-orange-500 text-white rounded-lg font-medium">
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