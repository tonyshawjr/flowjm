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
        /* Professional Desktop-First Design System */
        :root {
            --flow-bg: linear-gradient(135deg, #FAFAF9 0%, #F7F6F3 100%);
            --flow-card: #FFFFFF;
            --flow-primary: #FF6B35;
            --flow-text: #1F2937;
            --flow-secondary: #6B7280;
            --flow-border: #E5E7EB;
            --flow-success: #00B894;
            --flow-warning: #FDCB6E;
            --flow-critical: #FF6B6B;
            --flow-green: #10B981;
            --flow-shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --flow-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --flow-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --flow-shadow-xl: 0 16px 40px rgba(0, 0, 0, 0.16);
            --safe-area-inset-top: env(safe-area-inset-top);
            --safe-area-inset-bottom: env(safe-area-inset-bottom);
        }
        
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Professional Desktop Background */
        body {
            background: var(--flow-bg);
            min-height: 100vh;
            overflow-x: hidden;
            padding: 0;
            font-size: 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        /* Professional Desktop App Container */
        .app-container {
            width: 100%;
            min-height: 100vh;
            background: var(--flow-bg);
            position: relative;
            padding-top: var(--safe-area-inset-top);
            padding-bottom: var(--safe-area-inset-bottom);
        }
        
        /* Responsive Breakpoints */
        @media (max-width: 767px) {
            /* Mobile Layout */
            .mobile-nav {
                display: flex;
            }
            
            .desktop-nav {
                display: none;
            }
            
            body {
                font-size: 14px;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1023px) {
            /* Tablet Layout */
            .mobile-nav {
                display: none;
            }
            
            .desktop-nav {
                display: flex;
            }
            
            body {
                font-size: 15px;
            }
        }
        
        @media (min-width: 1024px) {
            /* Desktop Layout */
            .mobile-nav {
                display: none;
            }
            
            .desktop-nav {
                display: flex;
            }
            
            body {
                font-size: 16px;
            }
            
            /* Professional Desktop Spacing */
            .desktop-spacing {
                padding: 32px;
            }
            
            /* Desktop FAB */
            .fab {
                right: 40px;
                bottom: 40px;
                width: 72px;
                height: 72px;
            }
            
            .fab:hover {
                transform: translateY(-4px) scale(1.08);
                box-shadow: var(--flow-shadow-xl), 0 0 0 8px rgba(255, 107, 53, 0.1);
            }
            
            /* Desktop Modals */
            .camp-drawer {
                left: auto;
                right: 0;
                transform: translateX(100%);
                max-width: 480px;
                width: 480px;
                box-shadow: var(--flow-shadow-xl);
            }
            
            .camp-drawer.open {
                transform: translateX(0);
            }
            
            .quick-add-sheet {
                left: 50%;
                right: auto;
                width: 600px;
                max-width: 90vw;
                transform: translateX(-50%) translateY(100%);
                border-radius: 24px;
                margin-bottom: 40px;
                box-shadow: var(--flow-shadow-xl);
            }
            
            .quick-add-sheet.open {
                transform: translateX(-50%) translateY(0);
            }
        }
        
        /* Rich Visual Journey Cards */
        .circle-container {
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            padding: 16px 0;
        }
        
        .circle-container::-webkit-scrollbar {
            display: none;
        }
        
        .circle-card {
            scroll-snap-align: center;
            flex-shrink: 0;
            width: 100px;
            position: relative;
            cursor: pointer;
            user-select: none;
            margin: 0 8px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        /* Tablet Circle Layout */
        @media (min-width: 768px) {
            .circle-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 24px;
                max-width: 1400px;
                margin: 0 auto;
                padding: 24px 0;
                overflow: visible;
            }
            
            .circle-card {
                width: 200px;
                height: 140px;
                margin: 0;
                scroll-snap-align: none;
                border-radius: 20px;
            }
        }
        
        /* Desktop Circle Layout - Premium Journey Cards */
        @media (min-width: 1024px) {
            .circle-container {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 32px;
                max-width: 1600px;
                padding: 32px 0;
            }
            
            .circle-card {
                width: 240px;
                height: 160px;
                border-radius: 24px;
            }
        }
        
        .circle-card:hover {
            transform: translateY(-4px) scale(1.03);
        }
        
        .circle-card:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        /* Mobile Circle Ring */
        .circle-ring {
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            padding: 3px;
            background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
            box-shadow: var(--flow-shadow-md);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        /* Desktop Journey Card Design */
        @media (min-width: 768px) {
            .circle-ring {
                inset: 0;
                border-radius: 20px;
                padding: 0;
                background: linear-gradient(135deg, #FFFFFF 0%, #FEFEFE 100%);
                border: 2px solid rgba(255, 107, 53, 0.1);
                box-shadow: var(--flow-shadow-lg);
            }
            
            .circle-card:hover .circle-ring {
                box-shadow: var(--flow-shadow-xl);
                border-color: rgba(255, 107, 53, 0.3);
            }
        }
        
        @media (min-width: 1024px) {
            .circle-ring {
                border-radius: 24px;
            }
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
        
        /* Mobile Circle Content */
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
            font-size: 18px;
            color: var(--flow-text);
            text-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.9);
        }
        
        /* Desktop Journey Card Content */
        @media (min-width: 768px) {
            .circle-content {
                background: transparent;
                border-radius: 18px;
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 20px;
                align-items: flex-start;
                justify-content: space-between;
                font-size: 14px;
                font-weight: 600;
                border: none;
                box-shadow: none;
                text-shadow: none;
            }
        }
        
        @media (min-width: 1024px) {
            .circle-content {
                border-radius: 22px;
                padding: 24px;
                font-size: 16px;
            }
        }
        
        /* Journey Card Header */
        .journey-card-header {
            display: none;
        }
        
        @media (min-width: 768px) {
            .journey-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                width: 100%;
                margin-bottom: 12px;
            }
        }
        
        /* Journey Card Body */
        .journey-card-body {
            display: none;
        }
        
        @media (min-width: 768px) {
            .journey-card-body {
                display: block;
                width: 100%;
            }
        }
        
        /* Journey Card Footer */
        .journey-card-footer {
            display: none;
        }
        
        @media (min-width: 768px) {
            .journey-card-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                margin-top: auto;
                padding-top: 12px;
                border-top: 1px solid rgba(0, 0, 0, 0.05);
            }
        }
        
        /* Professional Moment Cards */
        .stack-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #FCFCFC 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid rgba(255, 107, 53, 0.08);
            box-shadow: var(--flow-shadow-sm);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            user-select: none;
            position: relative;
            backdrop-filter: blur(20px);
        }
        
        /* Tablet Stack Layout */
        @media (min-width: 768px) {
            .stack-feed-container {
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .stack-card {
                border-radius: 20px;
                padding: 24px;
                margin-bottom: 20px;
            }
        }
        
        /* Desktop Stack - Multi-Column Masonry Layout */
        @media (min-width: 1024px) {
            .stack-feed-container {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 32px;
                max-width: 1600px;
                align-items: start;
            }
            
            .stack-card {
                margin-bottom: 0;
                padding: 32px;
                border-radius: 24px;
            }
        }
        
        /* Large Desktop - 3 Column Layout */
        @media (min-width: 1440px) {
            .stack-feed-container {
                grid-template-columns: repeat(3, 1fr);
            }
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
            box-shadow: var(--flow-shadow-lg);
            border-color: rgba(255, 107, 53, 0.2);
            transform: translateY(-2px) scale(1.01);
        }
        
        @media (min-width: 1024px) {
            .stack-card:hover {
                box-shadow: var(--flow-shadow-xl);
                border-color: rgba(255, 107, 53, 0.3);
                transform: translateY(-4px) scale(1.02);
            }
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
        
        /* Enhanced Mobile Navigation */
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 0 calc(12px + var(--safe-area-inset-bottom));
            z-index: 50;
            box-shadow: var(--flow-shadow-lg);
        }
        
        /* Professional Desktop Navigation */
        .desktop-nav {
            display: none;
        }
        
        @media (min-width: 768px) {
            .desktop-nav {
                display: flex;
                gap: 8px;
            }
            
            .desktop-nav button {
                position: relative;
                padding: 12px 24px;
                font-weight: 500;
                font-size: 14px;
                border-radius: 12px;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            
            .desktop-nav button:hover {
                background-color: rgba(255, 107, 53, 0.08);
                color: var(--flow-primary);
            }
            
            .desktop-nav button[aria-current="page"] {
                background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
                color: white;
                box-shadow: var(--flow-shadow-md);
            }
        }
        
        @media (min-width: 1024px) {
            .desktop-nav button {
                padding: 16px 32px;
                font-size: 16px;
                border-radius: 16px;
            }
        }
        
        /* Professional FAB */
        .fab {
            position: fixed;
            bottom: calc(80px + var(--safe-area-inset-bottom));
            right: 24px;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--flow-primary) 0%, #FF8A65 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--flow-shadow-lg);
            z-index: 49;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            outline: none;
        }
        
        .fab:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: var(--flow-shadow-xl);
        }
        
        .fab:active {
            transform: translateY(0) scale(0.95);
        }
        
        /* Desktop FAB positioning */
        @media (min-width: 768px) {
            .fab {
                bottom: 32px;
                right: 32px;
                width: 64px;
                height: 64px;
            }
            
            .fab svg {
                width: 28px;
                height: 28px;
            }
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
        
        /* Space at bottom for mobile nav only */
        @media (max-width: 768px) {
            #stack-feed {
                padding-bottom: calc(80px + var(--safe-area-inset-bottom));
            }
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
        
        /* Professional Desktop Enhancements */
        @media (min-width: 768px) {
            /* Custom scrollbar for desktop */
            ::-webkit-scrollbar {
                width: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: rgba(0, 0, 0, 0.05);
                border-radius: 4px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: rgba(255, 107, 53, 0.3);
                border-radius: 4px;
                transition: background 0.2s ease;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 107, 53, 0.5);
            }
            
            /* Professional Desktop Navigation Effects */
            .desktop-nav button {
                position: relative;
                overflow: hidden;
            }
            
            .desktop-nav button::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 50%;
                width: 0;
                height: 3px;
                background: linear-gradient(90deg, var(--flow-primary), #FF8A65);
                border-radius: 2px 2px 0 0;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                transform: translateX(-50%);
            }
            
            .desktop-nav button:hover::after {
                width: 100%;
            }
            
            .desktop-nav button[aria-current="page"]::after {
                width: 100%;
                background: rgba(255, 255, 255, 0.9);
            }
            
            /* Professional Card Hover Effects */
            .circle-card {
                transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }
            
            .circle-card:hover {
                transform: translateY(-8px) scale(1.03);
            }
            
            .stack-card {
                transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            }
            
            .stack-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            /* Professional Desktop Layout */
            header {
                border-bottom: 1px solid rgba(255, 107, 53, 0.08);
                backdrop-filter: blur(20px);
            }
            
            /* Better desktop spacing */
            .circle-container {
                padding: 32px 0;
                gap: 40px;
            }
            
            /* Desktop content containers */
            .desktop-container {
                max-width: 1600px;
                margin: 0 auto;
                padding: 0 48px;
            }
        }
        
        /* Ultra-wide desktop support */
        @media (min-width: 1600px) {
            .desktop-container {
                padding: 0 80px;
            }
            
            .circle-container {
                gap: 48px;
            }
            
            .stack-feed-container {
                gap: 40px;
            }
        }
        
        /* Professional Focus States */
        button:focus-visible,
        [role="button"]:focus-visible {
            outline: 3px solid rgba(255, 107, 53, 0.4);
            outline-offset: 2px;
            border-radius: 8px;
        }
        
        /* WCAG 2.1 AA Compliance */
        .text-gray-500 {
            color: #6B7280; /* Ensures 4.5:1 contrast ratio */
        }
        
        .text-gray-600 {
            color: #4B5563; /* Ensures 7:1 contrast ratio */
        }
        
        /* Reduced motion preferences */
        @media (prefers-reduced-motion: reduce) {
            .circle-card,
            .stack-card {
                animation: none;
                opacity: 1;
                transform: none;
            }
            
            * {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }
        
        /* Professional Loading States */
        body {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        body.loaded {
            opacity: 1;
        }
        
        /* Staggered Card Animations */
        .circle-card,
        .stack-card {
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        
        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .circle-card:nth-child(1) { animation-delay: 0.1s; }
        .circle-card:nth-child(2) { animation-delay: 0.2s; }
        .circle-card:nth-child(3) { animation-delay: 0.3s; }
        .circle-card:nth-child(4) { animation-delay: 0.4s; }
        .circle-card:nth-child(5) { animation-delay: 0.5s; }
        .circle-card:nth-child(n+6) { animation-delay: 0.6s; }
        
        .stack-card:nth-child(1) { animation-delay: 0.2s; }
        .stack-card:nth-child(2) { animation-delay: 0.3s; }
        .stack-card:nth-child(3) { animation-delay: 0.4s; }
        .stack-card:nth-child(4) { animation-delay: 0.5s; }
        .stack-card:nth-child(n+5) { animation-delay: 0.6s; }
        
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
        
        /* Professional Typography Scale */
        .text-xs { font-size: 12px; line-height: 16px; }
        .text-sm { font-size: 14px; line-height: 20px; }
        .text-base { font-size: 16px; line-height: 24px; }
        .text-lg { font-size: 18px; line-height: 28px; }
        .text-xl { font-size: 20px; line-height: 28px; }
        .text-2xl { font-size: 24px; line-height: 32px; }
        .text-3xl { font-size: 30px; line-height: 36px; }
        
        @media (min-width: 1024px) {
            .text-sm { font-size: 15px; line-height: 22px; }
            .text-base { font-size: 17px; line-height: 26px; }
            .text-lg { font-size: 19px; line-height: 30px; }
            .text-xl { font-size: 22px; line-height: 30px; }
            .text-2xl { font-size: 26px; line-height: 34px; }
            .text-3xl { font-size: 32px; line-height: 38px; }
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
    <!-- Professional Fixed/Sticky Desktop Header -->
    <header class="bg-white/95 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40 shadow-sm">
        <div class="px-6 lg:px-12 py-4 lg:py-5 flex items-center justify-between max-w-7xl mx-auto">
            <!-- Logo and Date -->
            <div class="flex items-center space-x-4">
                <h1 class="text-xl lg:text-2xl font-bold text-gray-900 tracking-tight">FlowJM</h1>
                <div class="hidden lg:flex items-center gap-2 px-3 py-1 bg-gray-100 rounded-full">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 font-medium"><?php echo date('M j, Y'); ?></span>
                </div>
            </div>
            
            <!-- Professional Desktop Navigation -->
            <nav class="desktop-nav hidden lg:flex items-center">
                <button class="px-6 py-3 text-white font-semibold" aria-current="page">
                    Dashboard
                </button>
                <button onclick="viewJourneys()" class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium transition-all duration-200">
                    Journeys
                </button>
                <button onclick="viewFullCamp()" class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium transition-all duration-200">
                    Camp
                </button>
                <button onclick="viewProfile()" class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium transition-all duration-200">
                    Profile
                </button>
            </nav>
            
            <!-- Header Actions -->
            <div class="flex items-center gap-3">
                <!-- Quick Actions (Desktop) -->
                <div class="hidden lg:flex items-center gap-2">
                    <button onclick="openQuickAdd()" class="p-3 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-200" title="Quick Add (⌘K)" aria-label="Quick add moment">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Mobile Camp Button -->
                <button onclick="openCampDrawer()" class="lg:hidden p-3 hover:bg-gray-50 rounded-full" aria-label="Open Camp Drawer">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                
                <!-- Desktop User Menu -->
                <button onclick="openCampDrawer()" class="hidden lg:flex items-center gap-2 p-2 hover:bg-gray-50 rounded-full" aria-label="Open user menu">
                    <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-pink-400 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-semibold"><?php echo strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)); ?></span>
                    </div>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Circle - Rich Visual Journey Cards -->
    <?php if (!empty($circleJourneys)): ?>
    <section class="bg-gradient-to-r from-orange-50/40 via-white to-pink-50/40 border-b border-orange-100/50 py-8 lg:py-12">
        <div class="px-6 lg:px-12 max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6 lg:mb-8">
                <h2 class="text-lg lg:text-2xl font-bold text-gray-900 tracking-tight">Active Journeys</h2>
                <div class="flex items-center gap-3">
                    <span class="text-sm lg:text-base text-orange-600 font-medium"><?php echo count($circleJourneys); ?> active</span>
                    <button onclick="createJourney()" class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-full font-medium text-sm transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Journey
                    </button>
                </div>
            </div>
            
            <div class="circle-container flex lg:grid space-x-4 lg:space-x-0 overflow-x-auto lg:overflow-visible px-4 lg:px-0">
                <?php foreach ($circleJourneys as $journey): ?>
                <?php 
                    $pulseClass = '';
                    if ($journey['pulse_status'] == 'warning') $pulseClass = 'warning';
                    if ($journey['pulse_status'] == 'critical') $pulseClass = 'critical';
                    
                    // Get initials for mobile
                    $initials = substr($journey['title'], 0, 1);
                    if ($journey['client_name']) {
                        $words = explode(' ', $journey['client_name']);
                        if (count($words) > 1) {
                            $initials = substr($words[0], 0, 1) . substr($words[1], 0, 1);
                        }
                    }
                    
                    // Calculate days until deadline
                    $daysLeft = '';
                    if ($journey['deadline']) {
                        $deadline = new DateTime($journey['deadline']);
                        $now = new DateTime();
                        $diff = $now->diff($deadline);
                        if ($deadline < $now) {
                            $daysLeft = 'Overdue';
                        } else {
                            $daysLeft = $diff->days . ' days left';
                        }
                    }
                ?>
                <div class="circle-card group" onclick="viewJourney(<?php echo $journey['id']; ?>)" role="button" tabindex="0" aria-label="View <?php echo escapeContent($journey['title']); ?> journey">
                    <div class="circle-ring <?php echo $pulseClass; ?>">
                        <div class="circle-content">
                            <!-- Mobile: Show Initials -->
                            <span class="text-xl font-bold text-gray-700 lg:hidden"><?php echo strtoupper($initials); ?></span>
                            
                            <!-- Desktop: Rich Journey Card -->
                            <div class="journey-card-header">
                                <div class="flex items-start justify-between w-full">
                                    <div class="flex-1">
                                        <h3 class="font-bold text-gray-900 text-lg leading-tight mb-1 line-clamp-2">
                                            <?php echo escapeContent($journey['title']); ?>
                                        </h3>
                                        <?php if ($journey['client_name']): ?>
                                        <p class="text-sm text-gray-600 font-medium"><?php echo escapeContent($journey['client_name']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2 ml-3">
                                        <?php if ($journey['pulse_status'] == 'critical'): ?>
                                        <div class="w-3 h-3 bg-red-500 rounded-full pulse-indicator"></div>
                                        <?php elseif ($journey['pulse_status'] == 'warning'): ?>
                                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                        <?php else: ?>
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="journey-card-body">
                                <?php if ($daysLeft): ?>
                                <div class="text-sm text-gray-500 mb-2">
                                    <span class="<?php echo strpos($daysLeft, 'Overdue') !== false ? 'text-red-600 font-semibold' : ''; ?>">
                                        📅 <?php echo $daysLeft; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="journey-card-footer">
                                <div class="flex items-center justify-between w-full">
                                    <?php if ($journey['balance_due'] > 0): ?>
                                    <span class="font-bold text-gray-900"><?php echo format_currency($journey['balance_due']); ?></span>
                                    <?php else: ?>
                                    <span class="text-green-600 font-semibold text-sm">✓ Paid</span>
                                    <?php endif; ?>
                                    
                                    <div class="text-xs text-gray-400 uppercase tracking-wider font-medium">
                                        <?php echo strtoupper($journey['status'] ?? 'Active'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Mobile pulse indicator -->
                            <?php if ($journey['pulse_status'] == 'critical'): ?>
                            <div class="lg:hidden absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full pulse-indicator"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Beautiful Add New Journey Card -->
                <div class="circle-card group lg:hidden" onclick="createJourney()" role="button" tabindex="0" aria-label="Create new journey">
                    <div style="position: absolute; inset: 0; border-radius: 50%; border: 3px dashed rgba(255, 107, 53, 0.3); background: linear-gradient(135deg, rgba(255, 107, 53, 0.05) 0%, rgba(255, 138, 101, 0.05) 100%);">
                        <div class="circle-content" style="background: transparent; border: none;">
                            <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Desktop Add New Journey Card -->
                <div class="hidden lg:block circle-card group" onclick="createJourney()" role="button" tabindex="0" aria-label="Create new journey">
                    <div class="circle-ring" style="border: 3px dashed rgba(255, 107, 53, 0.3); background: linear-gradient(135deg, rgba(255, 107, 53, 0.02) 0%, rgba(255, 138, 101, 0.02) 100%);">
                        <div class="circle-content" style="background: transparent;">
                            <div class="flex flex-col items-center justify-center h-full text-center">
                                <svg class="w-12 h-12 text-orange-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <h3 class="font-semibold text-gray-700 text-base">Add New Journey</h3>
                                <p class="text-sm text-gray-500 mt-1">Start tracking a new project</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Stack - Multi-Column Desktop Masonry Layout -->
    <main class="px-6 lg:px-12 py-8 pb-32 lg:pb-12 max-w-7xl mx-auto">
        <div class="mb-6 lg:mb-10 flex items-center justify-between">
            <h2 class="text-lg lg:text-2xl font-bold text-gray-900 tracking-tight">Recent Activity</h2>
            <div class="flex items-center gap-4">
                <?php if (!empty($stackMoments)): ?>
                <span class="text-sm lg:text-base text-gray-600 font-medium"><?php echo count($stackMoments); ?> moments</span>
                <?php endif; ?>
                <button onclick="openQuickAdd()" class="hidden lg:inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-full font-medium text-sm transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Log Moment
                </button>
            </div>
        </div>
        
        <div id="stack-feed" class="stack-feed-container">
            <?php if (!empty($stackMoments)): ?>
                <?php foreach ($stackMoments as $moment): ?>
                <div class="stack-card swipeable" data-moment-id="<?php echo $moment['id']; ?>" role="article" aria-label="Moment from <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>">
                    <!-- Rich Journey Badge -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-orange-500 to-pink-500 text-white text-sm font-semibold rounded-full shadow-sm">
                                <?php echo escapeContent($moment['journey_title'] ?? 'Untitled Journey'); ?>
                            </span>
                            <!-- Visual Type Indicator -->
                            <?php if (!empty($moment['type']) && $moment['type'] != 'update'): ?>
                            <div class="flex items-center gap-1">
                                <?php if ($moment['type'] == 'milestone'): ?>
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Milestone</span>
                                <?php elseif ($moment['type'] == 'blocker'): ?>
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-xs font-medium text-red-700 uppercase tracking-wide">Blocker</span>
                                <?php elseif ($moment['type'] == 'note'): ?>
                                <div class="w-2 h-2 bg-gray-500 rounded-full"></div>
                                <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Note</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500 font-medium">
                            <?php echo time_ago($moment['created_at']); ?>
                        </span>
                    </div>
                    
                    <!-- Rich Moment Content -->
                    <div class="prose prose-gray max-w-none">
                        <p class="text-gray-800 leading-relaxed text-base lg:text-lg font-medium mb-0">
                            <?php echo escapeContent($moment['content']); ?>
                        </p>
                    </div>
                    
                    <!-- Fieldnote Indicator -->
                    <?php if (!empty($moment['has_fieldnote'])): ?>
                    <div class="mt-2 text-xs text-gray-500 italic">
                        📝 Has fieldnote
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Beautiful Empty State -->
                <div class="text-center py-20 lg:py-32 col-span-full">
                    <div class="mb-8">
                        <div class="w-32 h-32 lg:w-40 lg:h-40 mx-auto mb-6 bg-gradient-to-br from-orange-100 to-pink-100 rounded-full flex items-center justify-center">
                            <svg class="w-16 h-16 lg:w-20 lg:h-20 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-3">Your Journey Starts Here</h3>
                        <p class="text-gray-600 mb-8 lg:text-xl max-w-md mx-auto leading-relaxed">
                            Log your first moment to begin tracking your creative progress
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <button onclick="createJourney()" class="px-8 py-4 bg-gradient-to-r from-orange-500 to-pink-500 text-white rounded-full font-semibold lg:text-lg hover:from-orange-600 hover:to-pink-600 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1" aria-label="Create your first journey">
                                Create Journey
                            </button>
                            <button onclick="openQuickAdd()" class="px-8 py-4 bg-white border-2 border-gray-200 text-gray-700 rounded-full font-semibold lg:text-lg hover:border-gray-300 hover:bg-gray-50 transition-all duration-200" aria-label="Log your first moment">
                                Log Moment
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Camp Drawer Overlay -->
    <div id="camp-overlay" class="camp-overlay" onclick="closeCampDrawer()"></div>
    
    <!-- Professional Camp Drawer -->
    <div id="camp-drawer" class="camp-drawer">
        <!-- Enhanced Drawer Header -->
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Camp</h2>
                    <p class="text-sm text-gray-600 mt-1">All your active journeys</p>
                </div>
                <button onclick="closeCampDrawer()" class="p-2 hover:bg-white rounded-full transition-all duration-200 shadow-sm" aria-label="Close Camp Drawer">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Enhanced Journey List -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-6 space-y-4">
                <?php foreach ($activeJourneys as $journey): ?>
                <?php 
                    // Calculate progress and status
                    $statusColor = 'green';
                    $statusText = 'On Track';
                    if ($journey['pulse_status'] == 'warning') {
                        $statusColor = 'yellow';
                        $statusText = 'Attention';
                    } elseif ($journey['pulse_status'] == 'critical') {
                        $statusColor = 'red';
                        $statusText = 'Critical';
                    }
                ?>
                <div class="group p-5 bg-white border border-gray-200 rounded-2xl cursor-pointer hover:border-orange-200 hover:shadow-lg transition-all duration-300" 
                     onclick="viewJourney(<?php echo $journey['id']; ?>)" 
                     role="button" tabindex="0" 
                     aria-label="View <?php echo escapeContent($journey['title']); ?> journey">
                    <!-- Journey Header -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 text-base group-hover:text-orange-600 transition-colors duration-200"><?php echo escapeContent($journey['title']); ?></h3>
                            <?php if ($journey['client_name']): ?>
                            <p class="text-sm text-gray-600 mt-1"><?php echo escapeContent($journey['client_name']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 ml-3">
                            <div class="w-3 h-3 bg-<?php echo $statusColor; ?>-500 rounded-full <?php echo $journey['pulse_status'] == 'critical' ? 'pulse-indicator' : ''; ?>"></div>
                        </div>
                    </div>
                    
                    <!-- Journey Metadata -->
                    <div class="flex items-center justify-between text-sm">
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full font-medium"><?php echo $statusText; ?></span>
                        <?php if ($journey['balance_due'] > 0): ?>
                        <span class="font-semibold text-gray-900"><?php echo format_currency($journey['balance_due']); ?></span>
                        <?php else: ?>
                        <span class="text-green-600 font-semibold">✓ Paid</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Enhanced Camp CTA -->
        <div class="p-6 border-t border-gray-200 bg-gray-50">
            <button onclick="viewFullCamp()" class="w-full py-4 bg-gradient-to-r from-gray-900 to-gray-800 hover:from-gray-800 hover:to-gray-700 text-white rounded-2xl font-semibold text-base transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5" aria-label="View full camp with all journeys">
                <div class="flex items-center justify-center gap-2">
                    <span>View All Journeys</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </div>
            </button>
        </div>
    </div>
    
    <!-- Enhanced Mobile Navigation -->
    <nav class="mobile-nav lg:hidden">
        <div class="flex items-center justify-around px-2">
            <button class="p-3 text-orange-500 bg-orange-50 rounded-full" aria-label="Dashboard" aria-current="page">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </button>
            <button class="p-3 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-200" onclick="viewJourneys()" aria-label="View all journeys">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2l6 3 5.447-2.724A1 1 0 0121 3.618v10.764a1 1 0 01-.553.894L15 18l-6-3z"/>
                </svg>
            </button>
            <button class="p-3 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-200" onclick="viewFullCamp()" aria-label="View camp">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </button>
            <button class="p-3 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-200" onclick="viewProfile()" aria-label="View profile">
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
    // Professional Desktop App Initialization
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize loading state
        document.body.style.opacity = '0';
        
        // Progressive enhancement for desktop
        if (window.innerWidth >= 1024) {
            document.documentElement.classList.add('desktop-mode');
        }
        
        // Show app after brief loading
        setTimeout(() => {
            document.body.classList.add('loaded');
        }, 100);
    });
    
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
    
    // Professional Keyboard shortcuts
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Cmd/Ctrl + K to open quick add (Quick Action)
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (!document.getElementById('quick-add-sheet').classList.contains('open')) {
                    openQuickAdd();
                    showToast('Quick add opened (⌘K)', 'info');
                }
            }
            
            // Cmd/Ctrl + J for Journeys
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'j') {
                e.preventDefault();
                viewJourneys();
            }
            
            // Cmd/Ctrl + C for Camp
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'c') {
                e.preventDefault();
                viewFullCamp();
            }
            
            // Cmd/Ctrl + N for New Journey
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'n') {
                e.preventDefault();
                createJourney();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                if (document.getElementById('quick-add-sheet').classList.contains('open')) {
                    closeQuickAdd();
                } else if (document.getElementById('camp-drawer').classList.contains('open')) {
                    closeCampDrawer();
                }
            }
            
            // Cmd/Ctrl + Enter to save (when in textarea)
            if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) {
                const activeElement = document.activeElement;
                if (activeElement && activeElement.id === 'moment-content') {
                    e.preventDefault();
                    saveMoment();
                }
            }
        });
        
        // Show keyboard shortcuts hint on first load
        if (!localStorage.getItem('keyboard-shortcuts-shown')) {
            setTimeout(() => {
                showToast('Press ⌘K for quick actions, ⌘J for journeys', 'info');
                localStorage.setItem('keyboard-shortcuts-shown', 'true');
            }, 2000);
        }
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
        
        // Show app ready indicator with stats
        const journeyCount = <?php echo count($circleJourneys); ?>;
        const momentCount = <?php echo count($stackMoments); ?>;
        
        console.log(`🚀 FlowJM Desktop Ready! ${journeyCount} journeys, ${momentCount} moments loaded.`);
        
        // Add loading completion indicator
        document.body.classList.add('loaded');
        
        // Professional loading completion
        if (journeyCount > 0 || momentCount > 0) {
            setTimeout(() => {
                const statusMessage = journeyCount > 0 
                    ? `Welcome back! ${journeyCount} active journeys loaded.`
                    : 'Ready to start your first journey!';
                showToast(statusMessage, 'success');
            }, 500);
        }
    });
    </script>
    </div> <!-- End app-container -->
</body>
</html>