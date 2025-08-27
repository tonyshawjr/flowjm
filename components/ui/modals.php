<?php
/**
 * FlowJM Modal Components
 * Reusable modal dialogs following the campsite theme
 */

/**
 * Render generic modal container
 */
function renderModal($modalId, $title, $content, $size = 'md') {
    $sizeClasses = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl'
    ];
    
    $modalClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    return <<<HTML
    <div id="$modalId" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl $modalClass w-full mx-4">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-stone-gray/20">
                <h3 class="text-lg font-semibold text-night-sky">$title</h3>
                <button 
                    type="button" 
                    onclick="FlowJM.closeModal('$modalId')"
                    class="text-stone-gray hover:text-night-sky p-1 rounded-lg hover:bg-stone-gray/10 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6">
                $content
            </div>
        </div>
    </div>
    HTML;
}

/**
 * Render confirmation modal
 */
function renderConfirmModal($modalId, $title, $message, $confirmText = 'Confirm', $cancelText = 'Cancel', $confirmAction = '', $isDangerous = false) {
    $confirmClass = $isDangerous ? 'bg-red-600 hover:bg-red-700' : 'bg-sunrise-orange hover:bg-trail-brown';
    $confirmAction = $confirmAction ? "onclick=\"$confirmAction\"" : '';
    
    $content = <<<HTML
    <div class="text-center">
        <div class="w-12 h-12 mx-auto mb-4 bg-stone-gray/10 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-stone-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h4 class="text-lg font-medium text-night-sky mb-2">$title</h4>
        <p class="text-stone-gray mb-6">$message</p>
        
        <div class="flex justify-center space-x-3">
            <button 
                type="button" 
                onclick="FlowJM.closeModal('$modalId')"
                class="px-4 py-2 text-stone-gray border border-stone-gray/30 rounded-lg hover:bg-stone-gray/10 transition-colors"
            >
                $cancelText
            </button>
            <button 
                type="button" 
                class="px-4 py-2 text-white $confirmClass rounded-lg transition-colors"
                $confirmAction
            >
                $confirmText
            </button>
        </div>
    </div>
    HTML;
    
    return renderModal($modalId, '', $content, 'sm');
}

/**
 * Render add moment modal
 */
function renderAddMomentModal($journeys = []) {
    $journeyOptions = '<option value="">Select a journey...</option>';
    foreach ($journeys as $journey) {
        $journeyOptions .= '<option value="' . $journey['id'] . '">' . escapeContent($journey['title']) . '</option>';
    }
    
    $momentTypes = [
        'update' => 'Update',
        'milestone' => 'Milestone',
        'payment' => 'Payment',
        'delivery' => 'Delivery',
        'feedback' => 'Feedback',
        'note' => 'Note'
    ];
    
    $typeOptions = '';
    foreach ($momentTypes as $value => $label) {
        $typeOptions .= '<option value="' . $value . '">' . $label . '</option>';
    }
    
    $visibility = [
        'private' => 'Private (only you)',
        'team' => 'Team (internal)',
        'client' => 'Client (visible to client)'
    ];
    
    $visibilityOptions = '';
    foreach ($visibility as $value => $label) {
        $selected = $value === 'private' ? 'selected' : '';
        $visibilityOptions .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }
    
    $content = <<<HTML
    <form id="add-moment-form" method="POST" action="/api/moments.php">
        <input type="hidden" name="action" value="create">
        
        <div class="mb-4">
            <label for="journey_id" class="block text-sm font-medium text-night-sky mb-2">
                Journey <span class="text-red-500">*</span>
            </label>
            <select id="journey_id" name="journey_id" required class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                $journeyOptions
            </select>
        </div>
        
        <div class="mb-4">
            <label for="moment_type" class="block text-sm font-medium text-night-sky mb-2">
                Type
            </label>
            <select id="moment_type" name="moment_type" class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                $typeOptions
            </select>
        </div>
        
        <div class="mb-4">
            <label for="content" class="block text-sm font-medium text-night-sky mb-2">
                Content <span class="text-red-500">*</span>
            </label>
            <textarea 
                id="content" 
                name="content" 
                rows="4" 
                required
                placeholder="What's the latest update on this journey?"
                class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent resize-vertical"
                data-autogrow
            ></textarea>
            <p class="text-xs text-stone-gray mt-1">You can use *italic* and **bold** formatting</p>
        </div>
        
        <div class="mb-6">
            <label for="visibility" class="block text-sm font-medium text-night-sky mb-2">
                Visibility
            </label>
            <select id="visibility" name="visibility" class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                $visibilityOptions
            </select>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button 
                type="button" 
                onclick="FlowJM.closeModal('add-moment-modal')"
                class="px-4 py-2 text-stone-gray border border-stone-gray/30 rounded-lg hover:bg-stone-gray/10 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="submit" 
                class="px-4 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors"
            >
                Add Moment
            </button>
        </div>
    </form>
    HTML;
    
    return renderModal('add-moment-modal', 'Add New Moment', $content, 'lg');
}

/**
 * Render add fieldnote modal
 */
function renderAddFieldnoteModal($journeys = [], $moments = []) {
    $journeyOptions = '<option value="">Select a journey...</option>';
    foreach ($journeys as $journey) {
        $journeyOptions .= '<option value="' . $journey['id'] . '">' . escapeContent($journey['title']) . '</option>';
    }
    
    $momentOptions = '<option value="">Select a moment...</option>';
    foreach ($moments as $moment) {
        $preview = truncate($moment['content'], 50);
        $momentOptions .= '<option value="' . $moment['id'] . '">' . escapeContent($preview) . '</option>';
    }
    
    $content = <<<HTML
    <form id="add-fieldnote-form" method="POST" action="/api/fieldnotes.php">
        <input type="hidden" name="action" value="create">
        
        <div class="mb-4">
            <p class="text-sm text-stone-gray mb-3">Attach this fieldnote to either a journey or a specific moment:</p>
            
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="radio" name="attach_to" value="journey" class="mr-2" checked>
                    <span class="text-sm text-night-sky">Journey</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="attach_to" value="moment" class="mr-2">
                    <span class="text-sm text-night-sky">Specific Moment</span>
                </label>
            </div>
        </div>
        
        <div id="journey-select" class="mb-4">
            <label for="journey_id" class="block text-sm font-medium text-night-sky mb-2">
                Journey <span class="text-red-500">*</span>
            </label>
            <select id="journey_id" name="journey_id" class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                $journeyOptions
            </select>
        </div>
        
        <div id="moment-select" class="mb-4 hidden">
            <label for="moment_id" class="block text-sm font-medium text-night-sky mb-2">
                Moment <span class="text-red-500">*</span>
            </label>
            <select id="moment_id" name="moment_id" class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent">
                $momentOptions
            </select>
        </div>
        
        <div class="mb-6">
            <label for="fieldnote_content" class="block text-sm font-medium text-night-sky mb-2">
                Fieldnote <span class="text-red-500">*</span>
            </label>
            <textarea 
                id="fieldnote_content" 
                name="content" 
                rows="4" 
                required
                placeholder="Add your private notes and observations..."
                class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent resize-vertical"
                data-autogrow
            ></textarea>
            <p class="text-xs text-stone-gray mt-1">Fieldnotes are always private and only visible to you</p>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button 
                type="button" 
                onclick="FlowJM.closeModal('add-fieldnote-modal')"
                class="px-4 py-2 text-stone-gray border border-stone-gray/30 rounded-lg hover:bg-stone-gray/10 transition-colors"
            >
                Cancel
            </button>
            <button 
                type="submit" 
                class="px-4 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors"
            >
                Add Fieldnote
            </button>
        </div>
    </form>
    
    <script>
        // Handle radio button changes
        document.querySelectorAll('input[name="attach_to"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const journeySelect = document.getElementById('journey-select');
                const momentSelect = document.getElementById('moment-select');
                
                if (this.value === 'journey') {
                    journeySelect.classList.remove('hidden');
                    momentSelect.classList.add('hidden');
                    document.getElementById('journey_id').required = true;
                    document.getElementById('moment_id').required = false;
                } else {
                    journeySelect.classList.add('hidden');
                    momentSelect.classList.remove('hidden');
                    document.getElementById('journey_id').required = false;
                    document.getElementById('moment_id').required = true;
                }
            });
        });
    </script>
    HTML;
    
    return renderModal('add-fieldnote-modal', 'Add Fieldnote', $content, 'lg');
}