<?php
/**
 * FlowJM Journey List Component
 * Display list of journeys with filters and search
 */

/**
 * Render journey list with filters
 */
function renderJourneyList($journeys, $filters = [], $pagination = null) {
    $searchValue = $filters['search'] ?? '';
    $statusFilter = $filters['status'] ?? '';
    $sortBy = $filters['sort'] ?? 'updated_at';
    $sortOrder = $filters['order'] ?? 'desc';
    
    $statusOptions = [
        '' => 'All Statuses',
        'active' => 'Active',
        'on_hold' => 'On Hold', 
        'completed' => 'Completed',
        'archived' => 'Archived'
    ];
    
    $sortOptions = [
        'updated_at' => 'Last Updated',
        'created_at' => 'Date Created',
        'title' => 'Title',
        'due_date' => 'Due Date',
        'sale_amount' => 'Sale Amount'
    ];
    
    // Build order select options
    $descSelected = $sortOrder === 'desc' ? 'selected' : '';
    $ascSelected = $sortOrder === 'asc' ? 'selected' : '';
    
    // Filters
    $filtersHtml = <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <input 
                    type="search" 
                    name="search" 
                    value="$searchValue"
                    placeholder="Search journeys..."
                    class="w-full px-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent"
                >
            </div>
            
            <!-- Status Filter -->
            <div>
                <select name="status" class="px-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
    HTML;
    
    foreach ($statusOptions as $value => $label) {
        $selected = $value === $statusFilter ? 'selected' : '';
        $filtersHtml .= "<option value=\"$value\" $selected>$label</option>";
    }
    
    $filtersHtml .= <<<HTML
                </select>
            </div>
            
            <!-- Sort -->
            <div>
                <select name="sort" class="px-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
    HTML;
    
    foreach ($sortOptions as $value => $label) {
        $selected = $value === $sortBy ? 'selected' : '';
        $filtersHtml .= "<option value=\"$value\" $selected>$label</option>";
    }
    
    $filtersHtml .= <<<HTML
                </select>
            </div>
            
            <!-- Order -->
            <div>
                <select name="order" class="px-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                    <option value="desc" $descSelected>Newest First</option>
                    <option value="asc" $ascSelected>Oldest First</option>
                </select>
            </div>
            
            <button type="submit" class="px-4 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors">
                Filter
            </button>
        </form>
    </div>
    HTML;
    
    // Journey Cards
    $journeyCardsHtml = '';
    if (!empty($journeys)) {
        $journeyCardsHtml .= '<div class="space-y-6">';
        foreach ($journeys as $journey) {
            $journeyCardsHtml .= renderDetailedJourneyCard($journey);
        }
        $journeyCardsHtml .= '</div>';
        
        // Pagination
        if ($pagination) {
            $journeyCardsHtml .= renderPagination($pagination);
        }
    } else {
        $journeyCardsHtml = renderEmptyState(
            'No Journeys Found',
            'Start your first journey to see it appear here.',
            'Create Journey',
            '/journey/create.php'
        );
    }
    
    return $filtersHtml . $journeyCardsHtml;
}

/**
 * Render journey summary stats
 */
function renderJourneyStats($stats) {
    $activeJourneys = renderStatCard('Active Journeys', $stats['active_journeys'], 'pine-green');
    $completedJourneys = renderStatCard('Completed', $stats['completed_journeys'], 'stone-gray');
    $totalRevenue = renderStatCard('Total Revenue', format_currency($stats['total_sales']), 'lake-blue');
    $outstandingBalance = renderStatCard('Outstanding', format_currency($stats['outstanding_balance']), 'sunrise-orange');
    
    return <<<HTML
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        $activeJourneys
        $completedJourneys
        $totalRevenue
        $outstandingBalance
    </div>
    HTML;
}