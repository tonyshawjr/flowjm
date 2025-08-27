<?php
/**
 * FlowJM Moment Form Component
 * Form for creating and editing moments
 */

/**
 * Render moment creation/edit form
 */
function renderMomentForm($moment = null, $journeys = [], $errors = []) {
    $isEdit = $moment !== null;
    $title = $isEdit ? 'Edit Moment' : 'Add New Moment';
    $action = $isEdit ? '/api/moments.php?action=update&id=' . $moment['id'] : '/api/moments.php?action=create';
    $submitText = $isEdit ? 'Update Moment' : 'Add Moment';
    
    // Set default values
    $formData = [
        'journey_id' => $moment['journey_id'] ?? '',
        'content' => $moment['content'] ?? '',
        'moment_type' => $moment['moment_type'] ?? 'update',
        'visibility' => $moment['visibility'] ?? 'private'
    ];
    
    // Journey options
    $journeyOptions = '<option value="">Select a journey...</option>';
    foreach ($journeys as $journey) {
        $selected = $journey['id'] == $formData['journey_id'] ? 'selected' : '';
        $journeyOptions .= '<option value="' . $journey['id'] . '" ' . $selected . '>' . escapeContent($journey['title']) . '</option>';
    }
    
    // Moment type options
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
        $selected = $value === $formData['moment_type'] ? 'selected' : '';
        $typeOptions .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }
    
    // Visibility options
    $visibilityOptions = [
        'private' => 'Private (only you)',
        'team' => 'Team (internal)',
        'client' => 'Client (visible to client)'
    ];
    
    $visibilitySelectOptions = '';
    foreach ($visibilityOptions as $value => $label) {
        $selected = $value === $formData['visibility'] ? 'selected' : '';
        $visibilitySelectOptions .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
    }
    
    // Error messages
    $journeyError = isset($errors['journey_id']) ? '<p class="text-xs text-red-600 mt-1">' . $errors['journey_id'] . '</p>' : '';
    $typeError = isset($errors['moment_type']) ? '<p class="text-xs text-red-600 mt-1">' . $errors['moment_type'] . '</p>' : '';
    $contentError = isset($errors['content']) ? '<p class="text-xs text-red-600 mt-1">' . $errors['content'] . '</p>' : '';
    $visibilityError = isset($errors['visibility']) ? '<p class="text-xs text-red-600 mt-1">' . $errors['visibility'] . '</p>' : '';
    
    return <<<HTML
    <form method="POST" action="$action" class="space-y-6">
        <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10">
            <h2 class="text-xl font-semibold text-night-sky mb-6">$title</h2>
            
            <!-- Journey Selection -->
            <div class="mb-4">
                <label for="journey_id" class="block text-sm font-medium text-night-sky mb-2">
                    Journey <span class="text-red-500">*</span>
                </label>
                <select 
                    id="journey_id" 
                    name="journey_id" 
                    required
                    class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent"
                >
                    $journeyOptions
                </select>
                $journeyError
            </div>
            
            <!-- Moment Type -->
            <div class="mb-4">
                <label for="moment_type" class="block text-sm font-medium text-night-sky mb-2">
                    Type
                </label>
                <select 
                    id="moment_type" 
                    name="moment_type"
                    class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent"
                >
                    $typeOptions
                </select>
                $typeError
            </div>
            
            <!-- Content -->
            <div class="mb-4">
                <label for="content" class="block text-sm font-medium text-night-sky mb-2">
                    Content <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="content" 
                    name="content" 
                    rows="6" 
                    required
                    placeholder="What's the latest update on this journey?"
                    class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent resize-vertical"
                    data-autogrow
                >{$formData['content']}</textarea>
                <p class="text-xs text-stone-gray mt-1">You can use *italic* and **bold** formatting</p>
                $contentError
            </div>
            
            <!-- Visibility -->
            <div class="mb-6">
                <label for="visibility" class="block text-sm font-medium text-night-sky mb-2">
                    Visibility
                </label>
                <select 
                    id="visibility" 
                    name="visibility"
                    class="w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent"
                >
                    $visibilitySelectOptions
                </select>
                <p class="text-xs text-stone-gray mt-1">Who can see this moment?</p>
                $visibilityError
            </div>
            
            <!-- Form Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-stone-gray/20">
                <a href="/moments.php" class="px-4 py-2 text-stone-gray border border-stone-gray/30 rounded-lg hover:bg-stone-gray/10 transition-colors">
                    Cancel
                </a>
                <button 
                    type="submit" 
                    class="px-6 py-2 bg-sunrise-orange text-white rounded-lg hover:bg-trail-brown transition-colors font-medium"
                >
                    $submitText
                </button>
            </div>
        </div>
    </form>
    HTML;
}