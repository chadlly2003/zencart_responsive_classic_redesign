<script>
jQuery(document).ready(function() {
    // Cycle through each form with a class of 'sidebox-select-form' that has a select tag
    // marked as 'required'.
    jQuery('form.sidebox-select-form select:required').each(function() {
        var theOptions = '';
        var optGroup = false;
        var isSelected = '';
        jQuery('option', this).each(function() {
            if (jQuery(this).val() === '') {
                // Make sure "Please Select" is shown correctly on both desktop and mobile
                theOptions += '<option value="" disabled selected>' + jQuery(this).text() + '</option>';
            } else {
                isSelected = '';
                if (jQuery(this).is(':selected')) {
                    isSelected = ' selected="selected"';
                }
                theOptions += '<option value="'+jQuery(this).val()+'"'+isSelected+'>'+jQuery(this).text()+'</option>';
            }
        });
        if (optGroup === true) {
            theOptions += '</optgroup>';
        }
        jQuery(this).empty().append(theOptions);
        jQuery('optgroup', this).css({'font-style':'normal'});

        // If a non-'' option is currently selected, ensure that the form's submit button is
        // enabled and change the cursor to a pointer. Otherwise, disable the form's submit
        // button and change the cursor to indicate that the button is not allowed.
        if (jQuery('select option:selected', this).length > 0) {
            jQuery(this).siblings('input[type="submit"], button[type="submit"]').attr('disabled', false).css('cursor', 'pointer');
        } else {
            jQuery(this).siblings('input[type="submit"], button[type="submit"]').attr('disabled', true).css('cursor', 'not-allowed');
        }

        // If an option in the select tag is selected, re-enable the submit button and change the
        // cursor back to a pointer.
        jQuery(this).on('change', function() {
            jQuery(this).siblings('input[type="submit"], button[type="submit"]').attr('disabled', false).css('cursor', 'pointer');
        });
    });
});
</script>
