!function(){let a=setInterval(function(){let b=document.readyState;if(b!=='complete'&&b!=='interactive'||typeof jQuery==='undefined'){return}let $=jQuery;clearInterval(a);a=null;

    fcLoadScriptVariable(
        window.fcp_forms_assets_url + 'popup.js',
        'FCP_Forms_Popup',
        function() {
            
            const workhours_popup = new FCP_Forms_Popup( '#entity-working-hours' );
            $( '#entity-working-hours_entity-add' ).on( 'click', function() {
                workhours_popup.show( this );
            });

            const gmap_popup = new FCP_Forms_Popup( '#entity-specify-map' );
            $( '#entity-map_entity-add' ).on( 'click', function() {
                gmap_popup.show( this );
                
                /* add map by class */
                if ( !$( '.fct-gmap-pick' ).length ) { return }
                fcLoadScriptVariable(
                    '/wp-content/themes/fct1/assets/smarts/gmap-pick.js?' + + new Date(),
                    'fcAddGmapPick',
                    function() { fcAddGmapPick( '.fct-gmap-pick' ) },
                    ['google']
                );
            });
        },
        [],
        true
    );
    
    fcLoadScriptVariable(
        'https://maps.googleapis.com/maps/api/js?key='+fcGmapKey+'&libraries=places&&language=de-DE',
        'google',
        function() {

            let autocompleteFilled = false; // make sure, the visitor used the autocomplete popup
            const $autocompleteInput = $( '#entity-address_entity-add' );
            if ( !$autocompleteInput.length ) { return }

            const autocomplete = new google.maps.places.Autocomplete(
                    $autocompleteInput[0],
                    {
                        componentRestrictions: { country: ['de'] },
                        fields: ['address_components', 'formatted_address', 'geometry'], // ++'place_id'
                        types: ['address']
                    }
                );

            autocomplete.addListener( 'place_changed', function() { // ++better replace with local autocomplete?
                fillInValues( autocomplete.getPlace() );
            });

            $autocompleteInput.on( 'input', function() { // any manual input must be corrected
                autocompleteFilled = false;
            });

            $autocompleteInput.on( 'blur', function() {
                
                setTimeout( function() { // should wait for autocompete if is - a measure of economy
                
                    if ( autocompleteFilled ) { return }
                    
                    let geocoder = new google.maps.Geocoder();

                    geocoder.geocode(
                        {
                            componentRestrictions: { country: 'de' },
                            address: $autocompleteInput.val()
                            //location: latlng{}
                            //placeId: placeId
                        },
                        function(results, status) {
                            if ( status !== 'OK' ) { return }
                            if ( autocompleteFilled ) { return }
                            fillInValues( results[0] );
                        }
                    );
                }, 1000 ); // ++can add a loading icon

            });
            
            $autocompleteInput.keydown( function (e) {
                if ( e.key === 'Enter' && $( '.pac-container:visible' ).length ) {
                    e.preventDefault();
                }
            });


            function fillInValues(result) {

                autocompleteFilled = true;

                let postcode = '';

                for ( const component of result.address_components ) {
                    const componentType = component.types[0];
                    switch (componentType) {
                        case 'postal_code': {
                            postcode = `${component.long_name}${postcode}`;
                            break;
                        }
                        case "postal_code_suffix": {
                            postcode = `${postcode}-${component.long_name}`;
                            break;
                        }
                        case "locality": { // city
                            $( '#entity-geo-city_entity-add' ).val( component.long_name );
                            break;
                        }
                        case "administrative_area_level_1": { // region
                            $( '#entity-region_entity-add' ).val( component.short_name );
                            break;
                        }
                    }
                }

                //$( '#entity-region_entity-add' ).val(); // ^
                //$( '#entity-geo-city_entity-add' ).val(); // ^
                $( '#entity-geo-postcode_entity-add' ).val( postcode );

                $( '#entity-geo-lat_entity-add' ).val( result.geometry.location.lat() );
                $( '#entity-geo-long_entity-add' ).val( result.geometry.location.lng() );
                
                // format the main address field
                $autocompleteInput.val( result.formatted_address );
                
                // ++process the invalid address somehow
            }

        }
    );

},300 )}();
