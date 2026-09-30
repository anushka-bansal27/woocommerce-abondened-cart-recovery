jQuery( function( $ )  {

    // Choices JS code fix.

    $.choices_js = {
        instances: [],
        render_choices_js_elements: function( index, value, class_name ) {

            // Skip if element does not exist in DOM.
            if ( 0 === jQuery( class_name ).length ) {
                return;
            }

            // Create Choices object is it does not exist already.
            if ( undefined === $.choices_js.instances[ index ] ) {
                
				let options = { removeItemButton: true, searchResultLimit:6, shouldSort:false };
				if ( 'products' === index && 'yes' === orddd_params.ordd_ajax_product_search ) {					
					options.placeholderValue = 'Type 3 or more Characters';					
				}
				
                $.choices_js.instances[ index ] = new Choices( class_name, options );
            }

            if ( undefined !== value && value.length > 0 ) {

                // Check value exists before trying to set - sometimes, values passed may not exist in select box.
                let all_values   = $.choices_js.instances[ index ].config.choices;
                let record_found = false;

                // value is an array of values.

                value.forEach( function( _value ) {

                    let is_found = all_values.some( function( item ) {
                        return _value === item.value;
                    } );
    
                    if ( is_found ) {
                        record_found = true;
                        $.choices_js.instances[ index ].setChoiceByValue( _value ); // Mark value as selected in drop-down.
                    }
                });

                if ( ! record_found && value.length > 0 ) {
                    $.choices_js.instances[ index ].removeActiveItems(); // Remove all selected items - we could have moved to a new record where nothing has been found.
                }  
            } else {
                $.choices_js.instances[ index ].removeActiveItems();
            }
        }
    }

});

