<?php
/**
 * FlowJM Journey Form Component
 * Form for creating and editing journeys
 */

/**
 * Render journey creation/edit form
 */
function renderJourneyForm($journey = null, $errors = []) {
    $isEdit = $journey !== null;
    $title = $isEdit ? 'Edit Journey' : 'Create New Journey';
    $action = $isEdit ? '/api/journeys.php?action=update&id=' . $journey['id'] : '/api/journeys.php?action=create';
    $submitText = $isEdit ? 'Update Journey' : 'Create Journey';
    
    // Set default values
    $formData = [
        'title' => $journey['title'] ?? '',
        'client_name' => $journey['client_name'] ?? '',
        'description' => $journey['description'] ?? '',
        'sale_amount' => $journey['sale_amount'] ?? '',
        'paid_amount' => $journey['paid_amount'] ?? '',
        'start_date' => $journey['start_date'] ?? '',
        'due_date' => $journey['due_date'] ?? '',
        'status' => $journey['status'] ?? 'active',
        'in_circle' => $journey['in_circle'] ?? false
    ];
    
    $statusOptions = [
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'archived' => 'Archived'
    ];
    
    $form = <<<HTML
    <form method="POST" action="$action" class="space-y-6">
        <div class="bg-white rounded-lg p-6 shadow-sm border border-stone-gray/10">
            <h2 class="text-xl font-semibold text-night-sky mb-6">$title</h2>
            
            <!-- Title -->
            {$renderFormInput('title', 'Journey Title', $formData['title'], 'text', ['required' => true, 'error' => $errors['title'] ?? ''])}
            
            <!-- Client Name -->
            {$renderFormInput('client_name', 'Client Name', $formData['client_name'], 'text', ['error' => $errors['client_name'] ?? ''])}
            
            <!-- Description -->
            {$renderFormTextarea('description', 'Description', $formData['description'], ['rows' => 3, 'placeholder' => 'Brief description of the journey...', 'error' => $errors['description'] ?? ''])}
            
            <!-- Financial Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {$renderFormInput('sale_amount', 'Sale Amount', $formData['sale_amount'], 'number', ['step' => '0.01', 'min' => '0', 'error' => $errors['sale_amount'] ?? ''])}
                {$renderFormInput('paid_amount', 'Paid Amount', $formData['paid_amount'], 'number', ['step' => '0.01', 'min' => '0', 'error' => $errors['paid_amount'] ?? ''])}
            </div>
            
            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {$renderFormDate('start_date', 'Start Date', $formData['start_date'], ['error' => $errors['start_date'] ?? ''])}
                {$renderFormDate('due_date', 'Due Date', $formData['due_date'], ['error' => $errors['due_date'] ?? ''])}
            </div>
            
            <!-- Status and Circle -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {$renderFormSelect('status', 'Status', $statusOptions, $formData['status'], ['error' => $errors['status'] ?? ''])}
                {$renderFormCheckbox('in_circle', 'Include in Circle (Priority)', $formData['in_circle'], ['help' => 'Priority journeys appear in your circle on the dashboard'])}
            </div>
            
            {$renderFormButtons($submitText, '', 'Cancel', '/journeys.php')}
        </div>
    </form>
    HTML;
    
    return $form;
}

// Note: This is a placeholder implementation
// The actual form rendering would need the form component functions to be called properly