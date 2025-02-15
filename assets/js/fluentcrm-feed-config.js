( function($) {

    $(document).on( "ready", function () {


        /**
         * Hide or show the “fieldset” sections
         * depending on the value of the Entry Type dropdown.
         */
        function toggleFieldsets() {
            var entryTypeVal = $('#entryType').val();
    
            console.log( 'entryTypeVal', entryTypeVal );

            // Example: Let’s assume each “dynamic” section 
            // is using a field name like "fluentCrmColumn_form_submissions", etc.
            // So we can hide all sections first:
            $.each(['subscriber_form_submissions', 'purchase_history', 'donation_history'], function (i, typeKey) {
                console.log( 'Typekey:' , typeKey );
                // For each field with name="fluentCrmColumn_something"
                // find the entire <li> or .gaddon-section containing it and hide it:
                console.log( $('#gform-settings-section-' + typeKey ));
                $('#gform-settings-section-' + typeKey).hide();
            });
    
            // Now show only the matching fieldset
            $('#gform-settings-section-' + entryTypeVal ).show();
        }
    
        // Run on load
        toggleFieldsets();
    
        // Run on dropdown change
        $('#entryType').on('change', function () {
            console.log( 'this on change', this );
            toggleFieldsets();
        });

    });
    

    
})(jQuery);

