<?php
/**
 * FlowJM Component Loader System
 * Centralized loading for all UI components following the campsite theme
 */

// Prevent direct access
if (!defined('FLOWJM_ROOT')) {
    die('Direct access not permitted');
}

// Load core components
require_once COMPONENTS_PATH . 'core/header.php';
require_once COMPONENTS_PATH . 'core/navigation.php';
require_once COMPONENTS_PATH . 'core/footer.php';

// Load UI components
require_once COMPONENTS_PATH . 'ui/cards.php';
require_once COMPONENTS_PATH . 'ui/forms.php';
require_once COMPONENTS_PATH . 'ui/modals.php';
require_once COMPONENTS_PATH . 'ui/drawer.php';

// Load journey components
require_once COMPONENTS_PATH . 'journey/journey-card.php';
require_once COMPONENTS_PATH . 'journey/journey-form.php';
require_once COMPONENTS_PATH . 'journey/journey-list.php';

// Load moment components  
require_once COMPONENTS_PATH . 'moment/moment-card.php';
require_once COMPONENTS_PATH . 'moment/moment-form.php';
require_once COMPONENTS_PATH . 'moment/moment-feed.php';

/**
 * Component helper functions
 */

/**
 * Render component with error handling
 */
function renderComponent($componentFunction, ...$args) {
    try {
        if (function_exists($componentFunction)) {
            return call_user_func_array($componentFunction, $args);
        } else {
            if (DEBUG) {
                return "<div class='text-red-600 p-2 bg-red-50 border border-red-200 rounded'>Component function '{$componentFunction}' not found</div>";
            }
            return '';
        }
    } catch (Exception $e) {
        if (DEBUG) {
            return "<div class='text-red-600 p-2 bg-red-50 border border-red-200 rounded'>Component error: " . $e->getMessage() . "</div>";
        }
        error_log("Component error in {$componentFunction}: " . $e->getMessage());
        return '';
    }
}

/**
 * Get Tailwind color class for status
 */
function getStatusColorClass($status, $prefix = 'bg') {
    $colors = [
        'active' => 'green-500',
        'completed' => 'gray-500', 
        'archived' => 'gray-400',
        'on_hold' => 'blue-500',
        'critical' => 'red-600',
        'warning' => 'yellow-500',
        'healthy' => 'green-500'
    ];
    
    $color = $colors[$status] ?? 'gray-500';
    return "{$prefix}-{$color}";
}

/**
 * Get status icon - SVG version
 */
function getStatusIconSvg($status) {
    $icons = [
        'active' => '<div class="status-indicator status-active"></div>',
        'completed' => '<div class="status-indicator status-completed"></div>',
        'archived' => '<div class="status-indicator status-completed"></div>',
        'on_hold' => '<div class="status-indicator status-on-hold"></div>',
        'critical' => '<div class="status-indicator status-critical"></div>',
        'warning' => '<div class="status-indicator status-warning"></div>',
        'healthy' => '<div class="status-indicator status-active"></div>'
    ];
    
    return $icons[$status] ?? '<div class="status-indicator status-active"></div>';
}

/**
 * Get status icon - Legacy emoji version (fallback)
 */
function getStatusIcon($status) {
    $icons = [
        'active' => '🟢',
        'completed' => '✅', 
        'archived' => '📁',
        'on_hold' => '⏸️',
        'critical' => '🔴',
        'warning' => '⚠️',
        'healthy' => '💚'
    ];
    
    return $icons[$status] ?? '⚪';
}

/**
 * Get moment type icon - SVG version
 */
function getMomentTypeIconSvg($type) {
    $icons = [
        'update' => '<svg class="icon-document" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="m14,2 6,6"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>',
        'milestone' => '<svg class="icon-document" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10 9 11 5.16-1 9-5.45 9-11V7l-10-5z"></path><path d="M9 12l2 2 4-4"></path></svg>',
        'payment' => '<svg class="icon-document" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v12"></path><path d="M15 9a3 3 0 0 0-3-3 3 3 0 0 0-3 3c0 3 3 3 3 3s3 0 3-3"></path><path d="M9 15a3 3 0 0 0 3 3 3 3 0 0 0 3-3"></path></svg>',
        'delivery' => '<svg class="icon-document" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>',
        'feedback' => '<svg class="icon-document" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>',
        'note' => '<svg class="icon-document" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="m14,2 6,6"></path></svg>'
    ];
    
    return $icons[$type] ?? $icons['note'];
}

/**
 * Get moment type icon - Legacy emoji version (fallback)
 */
function getMomentTypeIcon($type) {
    $icons = [
        'update' => '📝',
        'milestone' => '🏁',
        'payment' => '💰',
        'delivery' => '📦',
        'feedback' => '💬',
        'note' => '📄'
    ];
    
    return $icons[$type] ?? '📝';
}

/**
 * Escape and prepare content for display
 */
function escapeContent($content) {
    return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}

/**
 * Format content with basic markdown-like formatting
 */
function formatContent($content) {
    $content = escapeContent($content);
    $content = nl2br($content);
    
    // Bold text **text**
    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
    
    // Italic text *text*
    $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
    
    // Links
    $content = preg_replace(
        '/(https?:\/\/[^\s]+)/',
        '<a href="$1" target="_blank" class="text-lake-blue hover:underline">$1</a>',
        $content
    );
    
    return $content;
}

/**
 * Generate avatar HTML
 */
function renderAvatar($user, $size = 'w-8 h-8') {
    $initials = get_initials($user['name']);
    $bgColors = ['bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-yellow-500', 'bg-pink-500'];
    $bgColor = $bgColors[array_sum(str_split(ord($user['name'][0]))) % count($bgColors)];
    
    return <<<HTML
    <div class="$size $bgColor text-white rounded-full flex items-center justify-center text-sm font-medium">
        $initials
    </div>
    HTML;
}

/**
 * Generate timestamp display
 */
function renderTimestamp($datetime, $showRelative = true) {
    if (!$datetime) return '';
    
    $formatted = format_datetime($datetime);
    $relative = time_ago($datetime);
    
    if ($showRelative) {
        return <<<HTML
        <time class="text-stone-gray text-sm" datetime="$datetime" title="$formatted">
            $relative
        </time>
        HTML;
    }
    
    return <<<HTML
    <time class="text-stone-gray text-sm" datetime="$datetime">
        $formatted
    </time>
    HTML;
}

/**
 * Comprehensive SVG Icon System - Line Icons for Campsite Theme
 */

/**
 * Get any SVG icon with customizable classes
 */
function getSvgIcon($name, $class = 'w-6 h-6', $strokeWidth = 2) {
    $icons = [
        // Core campsite icons
        'tent' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M3 21h18L12 3 3 21zm9-13v6m0 2h.01"/>',
        'mountain' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M13 10l5 8H6l5-8m2-7l7 18H4L11 3z"/>',
        'flame' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 1-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>',
        'compass' => '<circle cx="12" cy="12" r="10" stroke-width="' . $strokeWidth . '"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M16.24 7.76l-2.12 6.36-6.36 2.12 2.12-6.36 6.36-2.12z"/>',
        
        // Document and content
        'document' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'folder' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>',
        'bookmark' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>',
        
        // UI actions
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
        'minus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M20 12H4"/>',
        'menu' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M6 18L18 6M6 6l12 12"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M5 13l4 4L19 7"/>',
        
        // Crud operations
        'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'delete' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'archive' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>',
        'download' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>',
        'upload' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>',
        
        // Navigation
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'chevron-down' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M19 9l-7 7-7-7"/>',
        'chevron-up' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M5 15l7-7 7 7"/>',
        'chevron-left' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M15 19l-7-7 7-7"/>',
        'chevron-right' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M9 5l7 7-7 7"/>',
        'arrow-left' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>',
        'arrow-right' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M14 5l7 7m0 0l-7 7m7-7H3"/>',
        
        // User and auth  
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
        'login' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>',
        'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        
        // Communication
        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'chat' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
        'bell' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        
        // Status and info
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'error' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        
        // Utility
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>',
        'filter' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>',
        'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'dollar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="' . $strokeWidth . '" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'
    ];
    
    if (!isset($icons[$name])) {
        return '<!-- Icon not found: ' . escapeContent($name) . ' -->'; 
    }
    
    return '<svg class="' . escapeContent($class) . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' . $icons[$name] . '</svg>';
}

/**
 * Get branded campsite icons
 */
function getTentIcon($class = 'w-6 h-6') {
    return getSvgIcon('tent', $class);
}

function getMountainIcon($class = 'w-6 h-6') {
    return getSvgIcon('mountain', $class);
}

function getFlameIcon($class = 'w-6 h-6') {
    return getSvgIcon('flame', $class);
}

function getDocumentIcon($class = 'w-6 h-6') {
    return getSvgIcon('document', $class);
}

function getCompassIcon($class = 'w-6 h-6') {
    return getSvgIcon('compass', $class);
}