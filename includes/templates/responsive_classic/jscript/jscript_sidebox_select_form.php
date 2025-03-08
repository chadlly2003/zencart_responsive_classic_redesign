<script>
jQuery(document).ready(function() {
    // Cycle through each form with a class of 'sidebox-select-form' that has a select tag marked as 'required'.
    jQuery('form.sidebox-select-form select:required').each(function() {
        var $select = jQuery(this);
        
        // Create the "Please Select" option (disabled and selected)
        var $pleaseSelectOption = jQuery('<option>', {
            value: '',
            disabled: true,
            selected: true,
            text: 'Please Select' // Placeholder text
        });

        // Prepend the "Please Select" option to the select element if it's not already there
        if ($select.find('option[value=""]').length === 0) {
            $select.prepend($pleaseSelectOption);
        }

        // Disable the "Please Select" option after a valid selection
        $select.on('change', function() {
            var selectedValue = $select.val();

            // If a valid option is selected, make sure "Please Select" is disabled
            if (selectedValue) {
                $select.find('option[value=""]').prop('disabled', true); // Disable "Please Select"
            } else {
                $select.find('option[value=""]').prop('disabled', false); // Enable "Please Select" if nothing is selected
            }

            // Enable/Disable submit button based on selection
            if (selectedValue) {
                $select.siblings('input[type="submit"], button[type="submit"]').prop('disabled', false).css('cursor', 'pointer');
            } else {
                $select.siblings('input[type="submit"], button[type="submit"]').prop('disabled', true).css('cursor', 'not-allowed');
            }
        });

        // Reset functionality: If the form is reset, enable "Please Select" option again
        $select.closest('form').on('reset', function() {
            // Re-enable the "Please Select" option
            $select.find('option[value=""]').prop('disabled', false);
            // Disable submit button as no option is selected
            $select.siblings('input[type="submit"], button[type="submit"]').prop('disabled', true).css('cursor', 'not-allowed');
        });

        // Initial check: If the form is already populated (pre-selected value), disable "Please Select"
        if ($select.val()) {
            $select.find('option[value=""]').prop('disabled', true);
        }
    });
});
</script>
