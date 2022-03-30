// set up popup blocks

// workhours popup----------------------------
fcLoadScriptVariable(
    window.fcp_forms_assets_url + 'popup.js',
    'FCP_Forms_Popup',
    function() {

        if ( jQuery( '#entity-working-hours' ).length === 0 ) { return; }

        const $ = jQuery;
        const workhours_popup = new FCP_Forms_Popup( '#entity-working-hours' );

        $( '#entity-working-hours_entity-add' ).on( 'click', function() {
            workhours_popup.show( this );
        });
        
        // lunch break add
        const $lunch = $( '<button type="button" style="float:right;margin:4px 0 0 12px">Wir haben Mittagspausen</button>' );
        $lunch.click( function() {
            const $copy = $( '#entity-working-hours input[type=text] ~ input[type=text]' ) // used to be +
            if ( $copy.length ) {
                $copy.each( function() {
                    const $self = $( this );
                    if ( !!~$self.attr( 'id' ).indexOf( 'open' ) ) {
                        $self.remove();
                    } else {
                        $self.prevAll( 'input[type=text]:first' ).remove();
                    }
                    // $( this ).remove(); // this is normally enough
                });
                return;
            }
            $( '#entity-working-hours input[type=text]' ).each( function(e) {
                const $self = $( this );
                if ( !!~$self.attr( 'id' ).indexOf( 'open' ) ) {
                    $self.clone().insertAfter( $self ).val( '' );
                } else {
                    $self.clone().insertAfter( $self ); // this is normally enough
                    $self.val( '' );
                }
            });
        });
        $( '#entity-working-hours h3' ).append( $lunch );
        
    },
    ['jQuery'],
    true
);


// gmap popup----------------------------
fcLoadScriptVariable(
    window.fcp_forms_assets_url + 'popup.js',
    'FCP_Forms_Popup',
    function() {

        if ( jQuery( '#entity-specify-map' ).length === 0 ) { return; }

        const $ = jQuery;
        const gmap_popup = new FCP_Forms_Popup( '#entity-specify-map' ),
              $gmap_holder = $( '.fct-gmap-pick' );
        let gmap, marker; // they are here to allow the address field to change the position

        $( '#entity-map_entity-add' ).on( 'click', function() {
            gmap_popup.show( this );
        });

        function getLatLngZoom() {
            const default_props = { // ++add default country pick by language or IP
                lat: 51.1243545,
                lng: 10.18524,
                zoom: 6
            },
            props = {
                lat: Number( $( '#entity-geo-lat_entity-add' ).val() ),
                lng: Number( $( '#entity-geo-long_entity-add' ).val() ),
                zoom: Number( $( '#entity-geo-zoom_entity-add' ).val() ) || default_props.zoom
            };
            
            return props.lat && props.lng ? props : default_props;
        }

        // gmap print
        gmap_popup.section.addEventListener( 'popup_opened', function() {

            if ( !$gmap_holder.length ) { return }

            fcLoadScriptVariable(
                'https://maps.googleapis.com/maps/api/js?key='+fcGmapKey+'&libraries=places&language=de-DE',
                'google'
            );
            
            fcLoadScriptVariable(
                '/wp-content/themes/fct1/assets/smarts/gmap-view.js?' + + new Date(),
                'fcAddGmapView',
                function() {
                    gmap = fcAddGmapView( $gmap_holder, false, getLatLngZoom() );

                    fcLoadScriptVariable(
                        '/wp-content/themes/fct1/assets/smarts/gmap-pick.js?' + + new Date(),
                        'fcAddGmapPick',
                        function() {
                            marker = fcAddGmapPick( gmap, $gmap_holder[0] );

                            // apply new values after moving the marker
                            $gmap_holder[0].addEventListener( 'map_changed', function(e) {
                                setTimeout( function() { // wait till new values are applied to the map
                                    $( '#entity-geo-lat_entity-add' ).val( e.detail.marker.getPosition().lat() );
                                    $( '#entity-geo-long_entity-add' ).val( e.detail.marker.getPosition().lng() );
                                    $( '#entity-geo-zoom_entity-add' ).val( e.detail.gmap.getZoom() );
                                });
                            });

                        }
                    );

                },
                ['google']
            );
        });
        gmap_popup.section.addEventListener( 'popup_closed', function() {
            $gmap_holder.empty();
        });        

        
        // moving the address field to the popup and back
        const $init_after = $( '#entity-address_entity-add' ).parent().children().first();
        gmap_popup.section.addEventListener( 'popup_opened', function() {
            $gmap_holder.before( $( '#entity-address_entity-add' ) );
        });
        gmap_popup.section.addEventListener( 'popup_closed', function() {
            $init_after.after( $( '#entity-address_entity-add' ) );
        });

        // moving the map by new address field
        gmap_popup.section.addEventListener( 'popup_map_move', function() {
            if ( !gmap || !marker ) { return }
            
            const props = getLatLngZoom();
            gmap.panTo( props );
            gmap.setZoom( 17 );
            marker.setPosition( props );
        });

    },
    ['jQuery'],
    true
);

// autocomplete
fcLoadScriptVariable(
    'https://maps.googleapis.com/maps/api/js?key='+fcGmapKey+'&libraries=places&language=de-DE',
    'google',
    function() {

        const $ = jQuery,
            $input = $( '#entity-address_entity-add' );
        if ( !$input.length ) { return }

        const countries = ['de', 'at', 'ch']; // Germany, Austria, Switzerland

        let is_correct = false; // make sure, the visitor used the autocomplete, so the hidden fields are filled correctly

        // autocomplete with an advisor
        const ac = new google.maps.places.Autocomplete(
            $input[0],
            {
                componentRestrictions: { country: countries },
                fields: ['address_components', 'formatted_address', 'geometry'], // ++'place_id' to load rating someday
                types: ['address']
            }
        );

        ac.addListener( 'place_changed', function() { // the correct way of filling the address field
            fillInValues( ac.getPlace() );
        });

        $input.on( 'input', function() { // any manual change must be verified / modified by the api
            is_correct = false;
        });


        let freeze = false; // freezes the value from changes by geocoder, if the field is in focus
        // verify / modify / format the value by the api
        $input.on( 'blur', function() {
            freeze = false;
            setTimeout( function() { // just a measure of economy, as `blur` fires before `place_changed`
            
                if ( is_correct ) { return }

                autosuggest( $input.val(),
                    function( place ) {
                        if ( is_correct || freeze ) { return }
                        fillInValues( place );
                    },
                    fillInValues // just passes empty value
                );

            }, 200 );

        });

        const $form = $input.parents( 'form' );
        $form.on( 'submit', function(e) { // don't submit the form before the address is modified

            if ( is_correct ) { return } // && $input.not( ':focus' ) OR && !freeze

            e.preventDefault();
            
            autosuggest( $input.val(),
                function( place ) {
                    fillInValues( place );
                    submit();
                },
                submit
            );
            
            function submit() {
                is_correct = true;
                $form.submit();
            }

        });

        if ( $input.is( ':focus' ) ) {
            freeze = true;
        }
        $input.on( 'focus', function() {
            freeze = true;
        });


        function fillInValues(place) {

            const values = {
                'region': '',
                'geo-city': '',
                'geo-postcode': '',
                'geo-lat': '',
                'geo-long': ''
            },
                prefix = 'entity-',
                postfix = '_entity-add';

            if ( !place || !place.geometry ) { apply_values(); return; } // autocomplete couldn't suggest anything proper
            
            is_correct = true;

            let postcode = '';
            for ( const component of place.address_components ) {
                const componentType = component.types[0];
                switch (componentType) {
                    case 'postal_code': { // postcode
                        postcode = `${component.long_name}${postcode}`;
                        break;
                    }
                    case "postal_code_suffix": { // postcode
                        postcode = `${postcode}-${component.long_name}`;
                        break;
                    }
                    case "locality": { // city
                        values['geo-city'] = component.long_name;
                        break;
                    }
                    case "administrative_area_level_1": { // region
                        values['region'] = component.short_name;
                        break;
                    }
                }
            }

            values['geo-postcode'] = postcode;
            values['geo-lat'] = place.geometry.location.lat();
            values['geo-long'] = place.geometry.location.lng();

            apply_values();
            
            gmap_move();

            // format the main address field
            $input.val( place.formatted_address );
            
            function apply_values() {
                for ( let i in values ) {
                    $( '#'+prefix+i+postfix ).val( values[i] );
                }                
            }
        }


        $input.keydown( function (e) { // don't submit the form if autocomplete is open
            if ( e.key === 'Enter' && $( '.pac-container:visible' ).length ) {
                e.preventDefault();
            }
        });
        
        function gmap_move() {
            $( '#entity-specify-map' )[0].dispatchEvent( new CustomEvent( 'popup_map_move' ) );
        }
        
        function autosuggest(address, success_func, fail_func) {

            if ( !address ) { return }
            if ( !success_func || typeof success_func !== 'function' ) { success_func = (a) => {}; }
            if ( !fail_func || typeof fail_func !== 'function' ) { fail_func = () => {}; }

            const geocoder = new google.maps.Geocoder();
            if ( !geocoder || !geocoder.geocode ) { fail_func(); return }

            let i = 0;
            iterate();

            function iterate() {
                if ( i >= countries.length ) { fail_func(); return }
                geocoder.geocode(
                    {
                        componentRestrictions: { country: countries[i] },
                        address: address
                        //placeId: placeId
                    },
                    function(places, status) {
                        if ( status !== 'OK' || !places[0] || !places[0].geometry ) {
                            fail_func();
                            return;
                        }
                        if ( !~places[0].formatted_address.indexOf( ',' ) ) { // the address is only the country name
                            i++;
                            iterate();
                            return;
                        }
                        success_func( places[0] );
                    }
                );                
            }
        }

    },
    ['jQuery'],
);