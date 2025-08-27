<?php
/**
 * FlowJM Moment Card Component
 * Individual moment display components
 */

/**
 * Render detailed moment card for moment feeds
 */
function renderDetailedMomentCard($moment) {
    $content = formatContent($moment['content']);
    $journeyTitle = escapeContent($moment['journey_title']);
    $clientName = escapeContent($moment['client_name'] ?? '');
    $userName = escapeContent($moment['user_name']);
    $typeIcon = getMomentTypeIcon($moment['moment_type']);
    $typeColor = getStatusColorClass($moment['moment_type'], 'text');
    $timestamp = renderTimestamp($moment['created_at']);
    $fieldnoteCount = intval($moment['fieldnote_count'] ?? 0);
    $visibilityIcon = $moment['visibility'] === 'client' ? '👁️' : ($moment['visibility'] === 'team' ? '👥' : '🔒');
    
    $clientNameHtml = $clientName ? '<p class="text-sm text-stone-gray mb-1">' . $clientName . '</p>' : '';
    $fieldnoteHtml = $fieldnoteCount > 0 ? 
        '<div class="flex items-center space-x-1 text-xs text-stone-gray">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span>' . $fieldnoteCount . ' fieldnote' . ($fieldnoteCount !== 1 ? 's' : '') . '</span>
        </div>' : '';
    
    return <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10 hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <span class="text-2xl">$typeIcon</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-2 mb-1">
                        <h3 class="font-semibold text-night-sky">
                            <a href="/journey.php?id={$moment['journey_id']}" class="hover:text-sunrise-orange transition-colors">
                                $journeyTitle
                            </a>
                        </h3>
                        <span class="text-xs $typeColor px-2 py-1 bg-stone-gray/10 rounded-full capitalize">
                            {$moment['moment_type']}
                        </span>
                    </div>
                    $clientNameHtml
                    <div class="flex items-center space-x-2 text-xs text-morning-mist">
                        <span>by $userName</span>
                        <span>•</span>
                        $timestamp
                        <span>•</span>
                        <span title="Visibility: {$moment['visibility']}">$visibilityIcon</span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
                <button class="text-stone-gray hover:text-sunrise-orange transition-colors p-1 rounded" title="Edit moment">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </button>
                <button class="text-stone-gray hover:text-sunrise-orange transition-colors p-1 rounded" title="More options">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Content -->
        <div class="text-forest-floor leading-relaxed mb-4 prose prose-sm max-w-none">
            $content
        </div>
        
        <!-- Footer -->
        <div class="flex items-center justify-between border-t border-stone-gray/10 pt-4">
            <div class="flex items-center space-x-4">
                <button class="flex items-center space-x-1 text-stone-gray hover:text-sunrise-orange transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path>
                    </svg>
                    <span>Add fieldnote</span>
                </button>
                
                $fieldnoteHtml
            </div>
            
            <div class="flex items-center space-x-2 text-sm">
                <button class="text-stone-gray hover:text-sunrise-orange transition-colors">Share</button>
                <span class="text-stone-gray/50">•</span>
                <a href="/moment.php?id={$moment['id']}" class="text-stone-gray hover:text-sunrise-orange transition-colors">
                    View details
                </a>
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render compact moment card for sidebars or summaries
 */
function renderCompactMomentCard($moment) {
    $content = truncate(strip_tags($moment['content']), 80);
    $journeyTitle = escapeContent($moment['journey_title']);
    $typeIcon = getMomentTypeIcon($moment['moment_type']);
    $timestamp = time_ago($moment['created_at']);
    
    return <<<HTML
    <div class="bg-white rounded-lg p-4 border border-stone-gray/10 hover:bg-canvas/30 transition-colors cursor-pointer"
         onclick="window.location.href='/moment.php?id={$moment['id']}'">
        
        <div class="flex items-start space-x-3">
            <span class="text-lg flex-shrink-0">$typeIcon</span>
            
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-sm font-medium text-night-sky truncate">$journeyTitle</h4>
                    <span class="text-xs text-morning-mist ml-2">$timestamp</span>
                </div>
                
                <p class="text-sm text-stone-gray leading-snug">$content</p>
                
                <div class="mt-2">
                    <span class="inline-block px-2 py-1 text-xs bg-stone-gray/10 text-stone-gray rounded-full capitalize">
                        {$moment['moment_type']}
                    </span>
                </div>
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render moment in timeline format
 */
function renderTimelineMoment($moment, $isLast = false) {
    $content = formatContent($moment['content']);
    $typeIcon = getMomentTypeIcon($moment['moment_type']);
    $typeColor = getStatusColorClass($moment['moment_type'], 'bg');
    $timestamp = format_datetime($moment['created_at']);
    $userName = escapeContent($moment['user_name']);
    
    $timelineConnector = !$isLast ? 
        '<div class="absolute left-6 top-12 w-0.5 h-full bg-stone-gray/20"></div>' : '';
    
    return <<<HTML
    <div class="relative flex space-x-4 pb-8">
        <!-- Timeline Icon -->
        <div class="relative flex items-center justify-center w-12 h-12 $typeColor/20 rounded-full flex-shrink-0">
            <span class="text-lg">$typeIcon</span>
        </div>
        $timelineConnector
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="bg-white rounded-lg p-4 shadow-sm border border-stone-gray/10">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-night-sky capitalize">{$moment['moment_type']}</span>
                        <span class="text-xs text-stone-gray">by $userName</span>
                    </div>
                    <time class="text-xs text-morning-mist" datetime="{$moment['created_at']}" title="$timestamp">
                        $timestamp
                    </time>
                </div>
                
                <div class="text-forest-floor leading-relaxed">
                    $content
                </div>
            </div>
        </div>
    </div>
    HTML;
}