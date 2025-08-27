<?php
/**
 * FlowJM Footer Component
 * Simple footer for the application
 */

function renderFooter() {
    $currentYear = date('Y');
    $version = APP_VERSION;
    
    return <<<HTML
    <footer class="bg-white border-t border-stone-gray/20 mt-16">
        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <span class="text-xl">🏔️</span>
                    <span class="font-medium text-night-sky">FlowJM</span>
                    <span class="text-xs text-stone-gray bg-stone-gray/10 px-2 py-1 rounded">v$version</span>
                </div>
                
                <div class="text-center md:text-right">
                    <p class="text-sm text-stone-gray mb-2">
                        &copy; $currentYear FlowJM. Built for journey management.
                    </p>
                    <p class="text-xs text-morning-mist">
                        the view's clear from up here.
                    </p>
                </div>
            </div>
        </div>
    </footer>
    HTML;
}