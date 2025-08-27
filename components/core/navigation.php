<?php
/**
 * FlowJM Navigation Component
 * Breadcrumb and contextual navigation
 */

/**
 * Render breadcrumb navigation
 */
function renderBreadcrumb($items) {
    if (empty($items)) return '';
    
    $breadcrumbHtml = '<nav class="flex items-center space-x-2 text-sm text-stone-gray mb-4" aria-label="Breadcrumb">';
    
    foreach ($items as $index => $item) {
        $isLast = $index === count($items) - 1;
        
        if (!$isLast) {
            $breadcrumbHtml .= <<<HTML
            <a href="{$item['url']}" class="hover:text-night-sky transition-colors">
                {$item['label']}
            </a>
            <svg class="w-4 h-4 text-morning-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            HTML;
        } else {
            $breadcrumbHtml .= <<<HTML
            <span class="text-night-sky font-medium">{$item['label']}</span>
            HTML;
        }
    }
    
    $breadcrumbHtml .= '</nav>';
    return $breadcrumbHtml;
}

/**
 * Render tab navigation
 */
function renderTabs($tabs, $activeTab) {
    if (empty($tabs)) return '';
    
    $tabsHtml = '<div class="border-b border-stone-gray/20 mb-6">';
    $tabsHtml .= '<nav class="-mb-px flex space-x-8">';
    
    foreach ($tabs as $tab) {
        $isActive = $tab['key'] === $activeTab;
        $activeClass = $isActive ? 'border-sunrise-orange text-sunrise-orange' : 'border-transparent text-stone-gray hover:text-night-sky hover:border-stone-gray/50';
        
        $countBadge = '';
        if (isset($tab['count']) && $tab['count']) {
            $countBadge = '<span class="ml-2 bg-stone-gray/20 text-stone-gray px-2 py-1 rounded-full text-xs">' . $tab['count'] . '</span>';
        }
        
        $tabsHtml .= <<<HTML
        <a href="{$tab['url']}" 
           class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors $activeClass">
            {$tab['label']}
            $countBadge
        </a>
        HTML;
    }
    
    $tabsHtml .= '</nav></div>';
    return $tabsHtml;
}

/**
 * Render pagination
 */
function renderPagination($pagination) {
    if ($pagination['total'] <= 1) return '';
    
    $paginationHtml = '<div class="flex items-center justify-between mt-8">';
    
    // Info
    $start = ($pagination['current'] - 1) * 20 + 1;
    $end = min($start + 19, $pagination['total'] * 20);
    
    $paginationHtml .= <<<HTML
    <div class="text-sm text-stone-gray">
        Showing $start to $end of results
    </div>
    HTML;
    
    // Navigation
    $paginationHtml .= '<div class="flex items-center space-x-1">';
    
    // Previous
    if ($pagination['prev']) {
        $paginationHtml .= <<<HTML
        <a href="{$pagination['prev']['url']}" 
           class="px-3 py-2 text-sm text-stone-gray hover:text-night-sky hover:bg-white rounded-lg transition-colors">
            Previous
        </a>
        HTML;
    }
    
    // Pages
    foreach ($pagination['pages'] as $page) {
        $activeClass = $page['current'] ? 'bg-sunrise-orange text-white' : 'text-stone-gray hover:text-night-sky hover:bg-white';
        
        $paginationHtml .= <<<HTML
        <a href="{$page['url']}" 
           class="px-3 py-2 text-sm rounded-lg transition-colors $activeClass">
            {$page['number']}
        </a>
        HTML;
    }
    
    // Next
    if ($pagination['next']) {
        $paginationHtml .= <<<HTML
        <a href="{$pagination['next']['url']}" 
           class="px-3 py-2 text-sm text-stone-gray hover:text-night-sky hover:bg-white rounded-lg transition-colors">
            Next
        </a>
        HTML;
    }
    
    $paginationHtml .= '</div></div>';
    return $paginationHtml;
}