<?php
/**
 * FlowJM Card Components
 * Reusable card components following the campsite theme
 */

/**
 * Render stat card for dashboard - Redesigned
 */
function renderStatCard($title, $value, $color = 'pine-green') {
    $title = escapeContent($title);
    $value = escapeContent($value);
    
    // Map colors to CSS variables
    $colorMap = [
        'pine-green' => 'var(--pine-green)',
        'lake-blue' => 'var(--lake-blue)',
        'sunrise-orange' => 'var(--sunrise-orange)',
        'red-600' => 'var(--danger-red)'
    ];
    $colorVar = $colorMap[$color] ?? 'var(--stone-gray)';
    
    return <<<HTML
    <div class="card-base card-elevated p-6 hover:border-medium transition-all">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <p class="text-small text-stone-gray font-medium mb-1">$title</p>
                <p class="text-h2 font-bold text-forest-floor">$value</p>
            </div>
            <div class="w-2 h-8 rounded-full" style="background-color: $colorVar;"></div>
        </div>
    </div>
    HTML;
}

/**
 * Render journey card (trail map) - Redesigned
 */
function renderJourneyCard($journey) {
    $title = escapeContent($journey['title']);
    $clientName = escapeContent($journey['client_name'] ?? 'No client');
    $lastUpdate = renderTimestamp($journey['updated_at']);
    $momentCount = intval($journey['moment_count'] ?? 0);
    $balanceDue = format_currency($journey['balance_due'] ?? 0);
    
    // Get status indicator class
    $statusClass = 'status-' . str_replace(' ', '-', strtolower($journey['pulse_status'] ?? 'active'));
    $isInCircle = !empty($journey['in_circle']);
    $circleIndicator = $isInCircle ? '<div class="w-2 h-2 bg-sunrise-orange rounded-full"></div>' : '';
    
    return <<<HTML
    <div class="card-base journey-card p-6 cursor-pointer group"
         onclick="window.location.href='/journey.php?id={$journey['id']}'">
        
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2 mb-1">
                    <h3 class="text-h4 font-semibold text-forest-floor group-hover:text-sunrise-orange transition-colors truncate">
                        $title
                    </h3>
                    $circleIndicator
                </div>
                <p class="text-small text-stone-gray truncate">$clientName</p>
            </div>
            <div class="status-indicator $statusClass"></div>
        </div>
        
        <!-- Metrics -->
        <div class="flex items-center justify-between py-3 border-t border-border-light">
            <div class="text-center">
                <div class="text-body font-semibold text-forest-floor">$momentCount</div>
                <div class="text-tiny text-morning-mist">moments</div>
            </div>
            <div class="text-right">
                <div class="text-body font-semibold text-sunrise-orange">$balanceDue</div>
                <div class="text-tiny text-morning-mist">balance</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="flex items-center justify-between mt-3">
            <div class="text-tiny text-morning-mist">
                $lastUpdate
            </div>
            <svg class="w-4 h-4 text-stone-gray group-hover:text-sunrise-orange transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </div>
    HTML;
}

/**
 * Render moment card (trail log entry) - Redesigned
 */
function renderMomentCard($moment) {
    $content = formatContent($moment['content']);
    $journeyTitle = escapeContent($moment['journey_title']);
    $userName = escapeContent($moment['user_name']);
    $timestamp = renderTimestamp($moment['created_at']);
    $fieldnoteCount = intval($moment['fieldnote_count'] ?? 0);
    
    // Get moment type icon (SVG)
    $typeIcon = getMomentTypeIconSvg($moment['moment_type']);
    
    return <<<HTML
    <div class="card-base moment-card p-6">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3">
                <div class="mt-1 text-stone-gray">
                    $typeIcon
                </div>
                <div class="flex-1">
                    <h4 class="text-h4 font-medium text-forest-floor mb-1">
                        <a href="/journey.php?id={$moment['journey_id']}" class="hover:text-sunrise-orange transition-colors">
                            $journeyTitle
                        </a>
                    </h4>
                    <div class="flex items-center space-x-2 text-small text-morning-mist">
                        <span>by $userName</span>
                        <span>•</span>
                        $timestamp
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div class="text-body text-stone-gray leading-relaxed mb-4">
            $content
        </div>
        
        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-border-light">
            <button class="flex items-center space-x-2 text-small text-stone-gray hover:text-sunrise-orange transition-colors">
                <svg class="icon-document" viewBox="0 0 24 24">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="m18.5 2.5-8 8V14h3.5l8-8-3.5-3.5Z"></path>
                </svg>
                <span>Add note</span>
            </button>
            <div class="flex items-center space-x-4 text-small text-morning-mist">
                <span>$fieldnoteCount notes</span>
                <button class="hover:text-sunrise-orange transition-colors">Share</button>
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render fieldnote card
 */
function renderFieldnoteCard($fieldnote) {
    $content = formatContent($fieldnote['content']);
    $attachedTo = escapeContent($fieldnote['attached_to_title']);
    $attachedType = $fieldnote['attached_to_type'];
    $timestamp = renderTimestamp($fieldnote['created_at']);
    $typeIcon = $attachedType === 'journey' ? '🗺️' : '📝';
    
    return <<<HTML
    <div class="bg-canvas/50 rounded-lg p-4 border border-stone-gray/20">
        <!-- Header -->
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center space-x-2">
                <span class="text-lg">$typeIcon</span>
                <div>
                    <p class="text-sm font-medium text-night-sky">$attachedTo</p>
                    <p class="text-xs text-stone-gray capitalize">$attachedType fieldnote</p>
                </div>
            </div>
            $timestamp
        </div>
        
        <!-- Content -->
        <div class="text-forest-floor text-sm leading-relaxed">
            $content
        </div>
    </div>
    HTML;
}

/**
 * Render empty state card
 */
function renderEmptyState($title, $message, $actionText = '', $actionUrl = '') {
    $title = escapeContent($title);
    $message = escapeContent($message);
    $actionButton = '';
    
    if ($actionText && $actionUrl) {
        $actionText = escapeContent($actionText);
        $actionButton = <<<HTML
        <a href="$actionUrl" class="inline-flex items-center px-4 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors">
            $actionText
        </a>
        HTML;
    }
    
    return <<<HTML
    <div class="text-center py-12 px-4">
        <div class="w-16 h-16 mx-auto mb-4 bg-stone-gray/10 rounded-full flex items-center justify-center">
            <span class="text-2xl text-stone-gray">🏔️</span>
        </div>
        <h3 class="text-lg font-medium text-night-sky mb-2">$title</h3>
        <p class="text-stone-gray mb-6 max-w-md mx-auto">$message</p>
        $actionButton
    </div>
    HTML;
}

/**
 * Render loading skeleton card
 */
function renderLoadingSkeleton($type = 'journey') {
    if ($type === 'moment') {
        return <<<HTML
        <div class="bg-white rounded-lg p-4 shadow-sm border border-stone-gray/10 animate-pulse">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <div class="w-6 h-6 bg-stone-gray/20 rounded"></div>
                    <div>
                        <div class="h-4 bg-stone-gray/20 rounded w-32 mb-1"></div>
                        <div class="h-3 bg-stone-gray/20 rounded w-20"></div>
                    </div>
                </div>
                <div class="h-3 bg-stone-gray/20 rounded w-16"></div>
            </div>
            <div class="space-y-2 mb-3">
                <div class="h-4 bg-stone-gray/20 rounded w-full"></div>
                <div class="h-4 bg-stone-gray/20 rounded w-3/4"></div>
            </div>
            <div class="flex items-center justify-between">
                <div class="h-3 bg-stone-gray/20 rounded w-24"></div>
                <div class="h-3 bg-stone-gray/20 rounded w-16"></div>
            </div>
        </div>
        HTML;
    }
    
    // Default journey card skeleton
    return <<<HTML
    <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-stone-gray/20 animate-pulse">
        <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
                <div class="h-5 bg-stone-gray/20 rounded w-3/4 mb-2"></div>
                <div class="h-4 bg-stone-gray/20 rounded w-1/2"></div>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-stone-gray/20 rounded-full"></div>
                <div class="w-6 h-6 bg-stone-gray/20 rounded"></div>
            </div>
        </div>
        <div class="flex items-center justify-between text-sm mb-3">
            <div class="h-3 bg-stone-gray/20 rounded w-20"></div>
            <div class="h-3 bg-stone-gray/20 rounded w-16"></div>
        </div>
        <div class="flex items-center justify-between">
            <div class="h-3 bg-stone-gray/20 rounded w-16"></div>
            <div class="h-3 bg-stone-gray/20 rounded w-20"></div>
        </div>
    </div>
    HTML;
}