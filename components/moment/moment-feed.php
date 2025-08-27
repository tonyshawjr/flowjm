<?php
/**
 * FlowJM Moment Feed Component
 * Display feeds of moments with filtering and pagination
 */

/**
 * Render moment feed for dashboard or moments page
 */
function renderMomentFeed($moments, $filters = [], $pagination = null, $showJourneyFilter = true) {
    $searchValue = $filters['search'] ?? '';
    $typeFilter = $filters['type'] ?? '';
    $visibilityFilter = $filters['visibility'] ?? '';
    $journeyFilter = $filters['journey_id'] ?? '';
    
    $typeOptions = [
        '' => 'All Types',
        'update' => 'Updates',
        'milestone' => 'Milestones',
        'payment' => 'Payments',
        'delivery' => 'Deliveries',
        'feedback' => 'Feedback',
        'note' => 'Notes'
    ];
    
    $visibilityOptions = [
        '' => 'All Visibility',
        'private' => 'Private',
        'team' => 'Team',
        'client' => 'Client'
    ];
    
    // Build filter form
    $filtersHtml = <<<HTML
    <div class="bg-white rounded-lg p-4 shadow-sm border border-stone-gray/10 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input 
                    type="search" 
                    name="search" 
                    value="$searchValue"
                    placeholder="Search moments..."
                    class="w-full px-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent text-sm"
                >
            </div>
            
            <!-- Type Filter -->
            <div>
                <select name="type" class="px-3 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent text-sm">
    HTML;
    
    foreach ($typeOptions as $value => $label) {
        $selected = $value === $typeFilter ? 'selected' : '';
        $filtersHtml .= "<option value=\"$value\" $selected>$label</option>";
    }
    
    $filtersHtml .= <<<HTML
                </select>
            </div>
            
            <!-- Visibility Filter -->
            <div>
                <select name="visibility" class="px-3 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent text-sm">
    HTML;
    
    foreach ($visibilityOptions as $value => $label) {
        $selected = $value === $visibilityFilter ? 'selected' : '';
        $filtersHtml .= "<option value=\"$value\" $selected>$label</option>";
    }
    
    $filtersHtml .= <<<HTML
                </select>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors text-sm">
                Filter
            </button>
            
            <a href="/moment/create.php" class="px-4 py-2 bg-pine-green text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                Add Moment
            </a>
        </form>
    </div>
    HTML;
    
    // Build moment cards
    $momentsHtml = '';
    if (!empty($moments)) {
        $momentsHtml .= '<div class="space-y-6">';
        foreach ($moments as $moment) {
            $momentsHtml .= renderDetailedMomentCard($moment);
        }
        $momentsHtml .= '</div>';
        
        // Pagination
        if ($pagination) {
            $momentsHtml .= renderPagination($pagination);
        }
    } else {
        $momentsHtml = renderEmptyState(
            'No Moments Found',
            'Start documenting your journey progress by adding your first moment.',
            'Add Moment',
            '/moment/create.php'
        );
    }
    
    return $filtersHtml . $momentsHtml;
}

/**
 * Render compact moment feed for sidebars
 */
function renderCompactMomentFeed($moments, $limit = 5, $title = 'Recent Moments') {
    if (empty($moments)) {
        return '';
    }
    
    $momentsHtml = <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-night-sky">$title</h3>
            <a href="/moments.php" class="text-sm text-sunrise-orange hover:text-trail-brown transition-colors">
                View all
            </a>
        </div>
        
        <div class="space-y-3">
    HTML;
    
    $count = 0;
    foreach ($moments as $moment) {
        if ($count >= $limit) break;
        $momentsHtml .= renderCompactMomentCard($moment);
        $count++;
    }
    
    $momentsHtml .= <<<HTML
        </div>
    </div>
    HTML;
    
    return $momentsHtml;
}

/**
 * Render moment timeline for journey view
 */
function renderMomentTimeline($moments, $journeyTitle = '') {
    if (empty($moments)) {
        return renderEmptyState(
            'No Moments Yet',
            'This journey is just getting started. Add the first moment to begin tracking progress.',
            'Add First Moment',
            '/moment/create.php'
        );
    }
    
    $timelineHtml = <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-night-sky">Journey Timeline</h3>
            <div class="text-sm text-stone-gray">
                {count($moments)} moment{count($moments) !== 1 ? 's' : ''}
            </div>
        </div>
        
        <div class="relative">
    HTML;
    
    $momentCount = count($moments);
    foreach ($moments as $index => $moment) {
        $isLast = $index === $momentCount - 1;
        $timelineHtml .= renderTimelineMoment($moment, $isLast);
    }
    
    $timelineHtml .= <<<HTML
        </div>
    </div>
    HTML;
    
    return $timelineHtml;
}

/**
 * Render moment statistics
 */
function renderMomentStats($stats) {
    $totalMoments = renderStatCard('Total Moments', $stats['total_moments'], 'lake-blue');
    $thisWeek = renderStatCard('This Week', $stats['this_week'], 'pine-green');
    $thisMonth = renderStatCard('This Month', $stats['this_month'], 'sunrise-orange');
    $milestones = renderStatCard('Milestones', $stats['milestones'], 'purple-500');
    
    return <<<HTML
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        $totalMoments
        $thisWeek
        $thisMonth
        $milestones
    </div>
    HTML;
}