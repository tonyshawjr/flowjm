<?php
/**
 * FlowJM Form Components
 * Reusable form elements following the campsite theme
 */

/**
 * Render form input field
 */
function renderFormInput($name, $label, $value = '', $type = 'text', $options = []) {
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $placeholder = isset($options['placeholder']) ? $options['placeholder'] : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $helpText = isset($options['help']) ? $options['help'] : '';
    $error = isset($options['error']) ? $options['error'] : '';
    
    $inputClass = "form-input w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors $class";
    
    if ($error) {
        $inputClass .= ' border-red-500';
    }
    
    $requiredSpan = $required ? '<span class="text-red-500">*</span>' : '';
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1">' . $error . '</p>' : '';
    
    $inputHtml = <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-night-sky mb-2">
            $label
            $requiredSpan
        </label>
        <input 
            type="$type" 
            id="$name" 
            name="$name" 
            value="$value"
            placeholder="$placeholder"
            class="$inputClass"
            $required
        >
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $inputHtml;
}

/**
 * Render textarea field
 */
function renderFormTextarea($name, $label, $value = '', $options = []) {
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $placeholder = isset($options['placeholder']) ? $options['placeholder'] : '';
    $rows = isset($options['rows']) ? $options['rows'] : '4';
    $class = isset($options['class']) ? $options['class'] : '';
    $helpText = isset($options['help']) ? $options['help'] : '';
    $error = isset($options['error']) ? $options['error'] : '';
    $autogrow = isset($options['autogrow']) && $options['autogrow'] ? 'data-autogrow' : '';
    
    $textareaClass = "form-input form-textarea w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors resize-vertical $class";
    
    if ($error) {
        $textareaClass .= ' border-red-500';
    }
    
    $requiredSpan = $required ? '<span class="text-red-500">*</span>' : '';
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1">' . $error . '</p>' : '';
    
    $textareaHtml = <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-night-sky mb-2">
            $label
            $requiredSpan
        </label>
        <textarea 
            id="$name" 
            name="$name" 
            rows="$rows"
            placeholder="$placeholder"
            class="$textareaClass"
            $required
            $autogrow
        >$value</textarea>
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $textareaHtml;
}

/**
 * Render select dropdown
 */
function renderFormSelect($name, $label, $options, $selected = '', $fieldOptions = []) {
    $required = isset($fieldOptions['required']) && $fieldOptions['required'] ? 'required' : '';
    $class = isset($fieldOptions['class']) ? $fieldOptions['class'] : '';
    $helpText = isset($fieldOptions['help']) ? $fieldOptions['help'] : '';
    $error = isset($fieldOptions['error']) ? $fieldOptions['error'] : '';
    
    $selectClass = "form-input w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors $class";
    
    if ($error) {
        $selectClass .= ' border-red-500';
    }
    
    $requiredSpan = $required ? '<span class="text-red-500">*</span>' : '';
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1">' . $error . '</p>' : '';
    
    $selectHtml = <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-night-sky mb-2">
            $label
            $requiredSpan
        </label>
        <select 
            id="$name" 
            name="$name" 
            class="$selectClass"
            $required
        >
    HTML;
    
    foreach ($options as $value => $text) {
        $selectedAttr = $value == $selected ? 'selected' : '';
        $selectHtml .= "<option value=\"$value\" $selectedAttr>$text</option>";
    }
    
    $selectHtml .= <<<HTML
        </select>
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $selectHtml;
}

/**
 * Render checkbox field
 */
function renderFormCheckbox($name, $label, $checked = false, $options = []) {
    $value = isset($options['value']) ? $options['value'] : '1';
    $class = isset($options['class']) ? $options['class'] : '';
    $helpText = isset($options['help']) ? $options['help'] : '';
    $error = isset($options['error']) ? $options['error'] : '';
    $checkedAttr = $checked ? 'checked' : '';
    
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1 ml-6">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1 ml-6">' . $error . '</p>' : '';
    
    $checkboxHtml = <<<HTML
    <div class="mb-4">
        <label class="flex items-center $class">
            <input 
                type="checkbox" 
                name="$name" 
                value="$value"
                class="w-4 h-4 text-sunrise-orange border-stone-gray/30 rounded focus:ring-sunrise-orange focus:ring-2"
                $checkedAttr
            >
            <span class="ml-2 text-sm text-night-sky">$label</span>
        </label>
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $checkboxHtml;
}

/**
 * Render file upload field
 */
function renderFormFile($name, $label, $options = []) {
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $accept = isset($options['accept']) ? 'accept="' . $options['accept'] . '"' : '';
    $multiple = isset($options['multiple']) && $options['multiple'] ? 'multiple' : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $helpText = isset($options['help']) ? $options['help'] : '';
    $error = isset($options['error']) ? $options['error'] : '';
    $preview = isset($options['preview']) && $options['preview'];
    
    $requiredSpan = $required ? '<span class="text-red-500">*</span>' : '';
    $previewDiv = $preview ? '<div id="' . $name . '-preview" class="mt-4 space-y-2"></div>' : '';
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1">' . $error . '</p>' : '';
    $dropZoneAttr = $preview ? 'data-drop-zone' : '';
    $previewAttr = $preview ? 'data-preview' : '';
    
    $fileHtml = <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-night-sky mb-2">
            $label
            $requiredSpan
        </label>
        <div class="border-2 border-dashed border-stone-gray/30 rounded-lg p-6 text-center hover:border-sunrise-orange/50 transition-colors $dropZoneAttr">
            <input 
                type="file" 
                id="$name" 
                name="$name" 
                class="hidden"
                $accept
                $multiple
                $required
                $previewAttr
            >
            <label for="$name" class="cursor-pointer">
                <div class="text-stone-gray mb-2">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span class="text-sm">Click to upload or drag and drop</span>
                </div>
            </label>
        </div>
        $previewDiv
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $fileHtml;
}

/**
 * Render form buttons
 */
function renderFormButtons($primaryText = 'Save', $primaryAction = '', $secondaryText = '', $secondaryAction = '') {
    $buttonsHtml = '<div class="flex items-center justify-between pt-6 border-t border-stone-gray/20">';
    
    // Secondary button (usually cancel)
    if ($secondaryText) {
        if ($secondaryAction) {
            $buttonsHtml .= <<<HTML
            <a href="$secondaryAction" class="btn-secondary">
                $secondaryText
            </a>
            HTML;
        } else {
            $buttonsHtml .= <<<HTML
            <button type="button" class="btn-secondary" onclick="history.back()">
                $secondaryText
            </button>
            HTML;
        }
    } else {
        $buttonsHtml .= '<div></div>'; // Spacer
    }
    
    // Primary button
    $buttonsHtml .= <<<HTML
    <button type="submit" class="btn-primary">
        $primaryText
    </button>
    HTML;
    
    $buttonsHtml .= '</div>';
    
    return $buttonsHtml;
}

/**
 * Render search form
 */
function renderSearchForm($placeholder = 'Search...', $value = '', $action = '') {
    $value = escapeContent($value);
    
    return <<<HTML
    <form method="GET" action="$action" class="relative">
        <input 
            type="search" 
            name="q" 
            value="$value"
            placeholder="$placeholder"
            class="w-full pl-10 pr-4 py-2 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors"
        >
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-4 h-4 text-stone-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </form>
    HTML;
}

/**
 * Render date input with proper formatting
 */
function renderFormDate($name, $label, $value = '', $options = []) {
    $required = isset($options['required']) && $options['required'] ? 'required' : '';
    $class = isset($options['class']) ? $options['class'] : '';
    $helpText = isset($options['help']) ? $options['help'] : '';
    $error = isset($options['error']) ? $options['error'] : '';
    $min = isset($options['min']) ? 'min="' . $options['min'] . '"' : '';
    $max = isset($options['max']) ? 'max="' . $options['max'] . '"' : '';
    
    $inputClass = "form-input w-full px-4 py-3 border border-stone-gray/30 rounded-lg focus:ring-2 focus:ring-sunrise-orange focus:border-transparent transition-colors $class";
    
    if ($error) {
        $inputClass .= ' border-red-500';
    }
    
    $requiredSpan = $required ? '<span class="text-red-500">*</span>' : '';
    $helpTextP = $helpText ? '<p class="text-xs text-stone-gray mt-1">' . $helpText . '</p>' : '';
    $errorP = $error ? '<p class="text-xs text-red-600 mt-1">' . $error . '</p>' : '';
    
    $dateHtml = <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-night-sky mb-2">
            $label
            $requiredSpan
        </label>
        <input 
            type="date" 
            id="$name" 
            name="$name" 
            value="$value"
            class="$inputClass"
            $required
            $min
            $max
        >
        $helpTextP
        $errorP
    </div>
    HTML;
    
    return $dateHtml;
}