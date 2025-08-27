<?php
/**
 * FlowJM Journey Card Component
 * Individual journey cards for lists and grids
 */

/**
 * Render detailed journey card for journey list page
 */
function renderDetailedJourneyCard($journey) {
    $title = escapeContent($journey['title']);
    $clientName = escapeContent($journey['client_name'] ?? 'No client');
    $description = escapeContent($journey['description'] ?? '');
    $descriptionPreview = truncate($description, 120);
    
    $statusColor = getStatusColorClass($journey['status'], 'border');
    $pulseColor = getStatusColorClass($journey['pulse_status'], 'bg');
    $statusIcon = getStatusIcon($journey['status']);
    $pulseIcon = getStatusIcon($journey['pulse_status']);
    
    $lastUpdate = renderTimestamp($journey['updated_at']);
    $momentCount = intval($journey['moment_count'] ?? 0);
    $saleAmount = format_currency($journey['sale_amount'] ?? 0);
    $paidAmount = format_currency($journey['paid_amount'] ?? 0);
    $balanceDue = format_currency($journey['balance_due'] ?? 0);
    
    $progressPercentage = 0;
    if ($journey['sale_amount'] > 0) {
        $progressPercentage = round(($journey['paid_amount'] / $journey['sale_amount']) * 100);
    }
    
    $startDate = $journey['start_date'] ? format_date($journey['start_date']) : 'Not set';
    $dueDate = $journey['due_date'] ? format_date($journey['due_date']) : 'Not set';
    
    $circleIcon = $journey['in_circle'] ? '🔥' : '';
    $descriptionHtml = $description ? '<p class="text-sm text-morning-mist mt-1">' . $descriptionPreview . '</p>' : '';
    $progressHtml = $journey['sale_amount'] > 0 ? 
        '<div class="mb-4">
            <div class="flex justify-between text-xs text-stone-gray mb-1">
                <span>Payment Progress</span>
                <span>' . $progressPercentage . '%</span>
            </div>
            <div class="w-full bg-stone-gray/20 rounded-full h-2">
                <div class="bg-pine-green h-2 rounded-full" style="width: ' . $progressPercentage . '%"></div>
            </div>
        </div>' : '';
    
    return <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border-l-4 $statusColor hover:shadow-md transition-shadow cursor-pointer group"
         onclick="window.location.href='/journey.php?id={$journey['id']}'">
        
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2 mb-1">
                    <h3 class="text-xl font-semibold text-night-sky group-hover:text-sunrise-orange transition-colors">
                        $title
                    </h3>
                    $circleIcon
                </div>
                <p class="text-stone-gray">$clientName</p>
                $descriptionHtml
            </div>
            
            <div class="flex items-center space-x-3 ml-4">
                <div class="text-center">
                    <div class="w-4 h-4 $pulseColor rounded-full mx-auto mb-1"></div>
                    <span class="text-xs text-stone-gray capitalize">{$journey['pulse_status']}</span>
                </div>
                <div class="text-2xl">$statusIcon</div>
            </div>
        </div>
        
        <!-- Metrics Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4 p-4 bg-canvas/30 rounded-lg">
            <div class="text-center">
                <div class="text-lg font-semibold text-night-sky">$momentCount</div>
                <div class="text-xs text-stone-gray">Moments</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-night-sky">$saleAmount</div>
                <div class="text-xs text-stone-gray">Sale Amount</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-pine-green">$paidAmount</div>
                <div class="text-xs text-stone-gray">Paid</div>
            </div>
            <div class="text-center">
                <div class="text-lg font-semibold text-sunrise-orange">$balanceDue</div>
                <div class="text-xs text-stone-gray">Balance Due</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        $progressHtml
        
        <!-- Timeline -->
        <div class="flex items-center justify-between text-sm text-stone-gray border-t border-stone-gray/10 pt-4">
            <div class="flex items-center space-x-4">
                <div>
                    <span class="text-xs text-morning-mist">Started:</span>
                    <span>$startDate</span>
                </div>
                <div>
                    <span class="text-xs text-morning-mist">Due:</span>
                    <span>$dueDate</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <div class="text-xs">$lastUpdate</div>
                <svg class="w-4 h-4 text-stone-gray group-hover:text-sunrise-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render compact journey card for search results or sidebars
 */
function renderCompactJourneyCard($journey) {
    $title = escapeContent($journey['title']);
    $clientName = escapeContent($journey['client_name'] ?? 'No client');
    $statusColor = getStatusColorClass($journey['status'], 'border');
    $pulseColor = getStatusColorClass($journey['pulse_status'], 'bg');
    $lastUpdate = time_ago($journey['updated_at']);
    $balanceDue = format_currency($journey['balance_due'] ?? 0);
    
    return <<<HTML
    <div class="bg-white rounded-lg p-4 border-l-3 $statusColor hover:shadow-sm transition-shadow cursor-pointer"
         onclick="window.location.href='/journey.php?id={$journey['id']}'">
        
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <h4 class="font-medium text-night-sky text-sm mb-1 truncate">$title</h4>
                <p class="text-xs text-stone-gray truncate">$clientName</p>
                <p class="text-xs text-morning-mist mt-1">$lastUpdate</p>
            </div>
            
            <div class="flex items-center space-x-2 ml-2">
                <div class="w-2 h-2 $pulseColor rounded-full"></div>
                <div class="text-xs font-medium text-night-sky">$balanceDue</div>
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render journey stats card
 */
function renderJourneyStatsCard($journey) {
    $title = escapeContent($journey['title']);
    $momentCount = intval($journey['moment_count'] ?? 0);
    $fieldnoteCount = intval($journey['fieldnote_count'] ?? 0);
    $saleAmount = format_currency($journey['sale_amount'] ?? 0);
    $paidAmount = format_currency($journey['paid_amount'] ?? 0);
    $balanceDue = format_currency($journey['balance_due'] ?? 0);
    
    $progressPercentage = 0;
    if ($journey['sale_amount'] > 0) {
        $progressPercentage = round(($journey['paid_amount'] / $journey['sale_amount']) * 100);
    }
    
    $progressHtml = $journey['sale_amount'] > 0 ? 
        '<div class="pt-2">
            <div class="flex justify-between text-xs text-stone-gray mb-1">
                <span>Progress</span>
                <span>' . $progressPercentage . '%</span>
            </div>
            <div class="w-full bg-stone-gray/20 rounded-full h-2">
                <div class="bg-pine-green h-2 rounded-full" style="width: ' . $progressPercentage . '%"></div>
            </div>
        </div>' : '';
    
    return <<<HTML
    <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10">
        <h3 class="text-lg font-semibold text-night-sky mb-4">Journey Overview</h3>
        
        <!-- Activity Stats -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="text-center p-4 bg-canvas/30 rounded-lg">
                <div class="text-2xl font-bold text-lake-blue">$momentCount</div>
                <div class="text-sm text-stone-gray">Moments</div>
            </div>
            <div class="text-center p-4 bg-canvas/30 rounded-lg">
                <div class="text-2xl font-bold text-stone-gray">$fieldnoteCount</div>
                <div class="text-sm text-stone-gray">Fieldnotes</div>
            </div>
        </div>
        
        <!-- Financial Stats -->
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-stone-gray">Sale Amount</span>
                <span class="font-medium text-night-sky">$saleAmount</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-stone-gray">Paid Amount</span>
                <span class="font-medium text-pine-green">$paidAmount</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-stone-gray">Balance Due</span>
                <span class="font-medium text-sunrise-orange">$balanceDue</span>
            </div>
            
            $progressHtml
        </div>
    </div>
    HTML;
}