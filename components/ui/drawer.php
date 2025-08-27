<?php
/**
 * FlowJM Camp Drawer Component
 * Slide-out from right like opening tent flap with active journey trails
 */

/**
 * Render camp drawer (base camp)
 */
function renderCampDrawer($journeys = []) {
    $journeyCards = '';
    
    if (!empty($journeys)) {
        foreach ($journeys as $journey) {
            $journeyCards .= renderCampJourneyCard($journey);
        }
    } else {
        $journeyCards = renderEmptyState(
            'No Active Journeys', 
            'Start a new journey to see it in your circle.',
            'Create Journey',
            '/journey/create.php'
        );
    }
    
    return <<<HTML
    <!-- Camp Drawer Overlay -->
    <div 
        id="camp-drawer-overlay" 
        class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"
        onclick="closeCampDrawer()"
    ></div>
    
    <!-- Camp Drawer -->
    <div 
        id="camp-drawer" 
        class="fixed top-0 right-0 h-full w-80 bg-white transform translate-x-full transition-transform duration-300 ease-out z-50 shadow-2xl hidden"
    >
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-border-light bg-surface-primary">
            <h2 class="text-lg font-semibold text-night-sky flex items-center">
                <?php echo getSvgIcon('archive', 'w-5 h-5 mr-2'); ?> Camp (Archive)
            </h2>
            <button 
                onclick="closeCampDrawer()" 
                class="p-2 text-stone-gray hover:text-forest-floor rounded-full hover:bg-surface-secondary transition-colors"
                aria-label="Close archive"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="drawer-content h-full overflow-y-auto p-6">
            $journeyCards
        </div>
    </div>
    
    <script>
        // Camp drawer functionality is handled by FlowJM.campDrawer in app.js
    </script>
    HTML;
}

/**
 * Render compact journey card for camp drawer
 */
function renderCampJourneyCard($journey) {
    $title = escapeContent($journey['title']);
    $clientName = escapeContent($journey['client_name'] ?? 'No client');
    $statusColor = getStatusColorClass($journey['status'], 'border');
    $pulseColor = getStatusColorClass($journey['pulse_status'], 'bg');
    $statusIcon = getStatusIcon($journey['pulse_status']);
    $lastUpdate = time_ago($journey['updated_at']);
    $balanceDue = format_currency($journey['balance_due'] ?? 0);
    
    return <<<HTML
    <div class="bg-white rounded-lg p-3 border-l-3 $statusColor hover:shadow-sm transition-shadow cursor-pointer"
         onclick="window.location.href='/journey.php?id={$journey['id']}'">
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <h4 class="font-medium text-night-sky text-sm truncate mb-1">$title</h4>
                <p class="text-xs text-stone-gray truncate">$clientName</p>
            </div>
            <div class="flex items-center space-x-1 ml-2">
                <div class="w-2 h-2 $pulseColor rounded-full"></div>
                <span class="text-sm">$statusIcon</span>
            </div>
        </div>
        
        <div class="flex items-center justify-between mt-2 text-xs text-stone-gray">
            <span>$lastUpdate</span>
            <span class="font-medium">$balanceDue</span>
        </div>
    </div>
    HTML;
}

/**
 * Render quick actions floating button
 */
function renderQuickActions() {
    return <<<HTML
    <div class="relative">
        <!-- Main FAB -->
        <button 
            id="fab-main"
            class="w-14 h-14 bg-sunrise-orange hover:bg-trail-brown text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center"
            onclick="toggleQuickActions()"
            aria-label="Toggle quick actions menu"
            aria-expanded="false"
            aria-controls="fab-menu"
        >
            <svg class="w-6 h-6 transition-transform duration-200" id="fab-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
        </button>
        
        <!-- Sub FABs -->
        <div id="fab-menu" class="fab-menu absolute bottom-16 right-0 space-y-2 transition-transform duration-200 origin-bottom-right" role="menu" aria-labelledby="fab-main">
            <!-- Add Moment -->
            <button 
                class="w-12 h-12 bg-lake-blue hover:bg-blue-700 text-white rounded-full shadow-sm hover:shadow-md transition-all duration-150 flex items-center justify-center"
                onclick="openAddMomentModal()"
                title="Add moment"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m18.5 2.5-8 8V14h3.5l8-8-3.5-3.5Z"></path>
                </svg>
            </button>
            
            <!-- New Journey -->
            <button 
                class="w-12 h-12 bg-pine-green hover:bg-green-700 text-white rounded-full shadow-sm hover:shadow-md transition-all duration-150 flex items-center justify-center"
                onclick="window.location.href='/journey/create.php'"
                title="New journey"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 .553-.894L9 2l6 3 5.447-2.724A1 1 0 0 1 21 3.618v10.764a1 1 0 0 1-.553.894L15 18l-6-3z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 2v18"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v18"></path>
                </svg>
            </button>
            
            <!-- Add Fieldnote -->
            <button 
                class="w-12 h-12 bg-stone-gray hover:bg-gray-600 text-white rounded-full shadow-sm hover:shadow-md transition-all duration-150 flex items-center justify-center"
                onclick="openAddFieldnoteModal()"
                title="Add fieldnote"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <script>
        // Quick actions functionality is handled by FlowJM.quickActions in app.js
        function openAddMomentModal() {
            // TODO: Implement add moment modal
            FlowJM.showToast('Add moment feature coming soon', 'info');
        }
        
        function openAddFieldnoteModal() {
            // TODO: Implement add fieldnote modal
            FlowJM.showToast('Add fieldnote feature coming soon', 'info');
        }
    </script>
    HTML;
}