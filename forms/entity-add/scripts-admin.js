// basically, it is a copy of scripts.js, but without popups and with scripts loaded initially

fcLoadScriptVariable(
    'https://maps.googleapis.com/maps/api/js?key='+fcGmapKey+'&libraries=places&&language=de-DE',
    'google'
);

fcLoadScriptVariable(
    '/wp-content/themes/fct1/assets/smarts/gmap-view.js?' + + new Date(),
    'fcAddGmapView'
);

fcLoadScriptVariable(
    '/wp-content/themes/fct1/assets/smarts/gmap-pick.js?' + + new Date(),
    'fcAddGmapPick'
);

fcLoadScriptVariable(
    '',
    'google',
    function() {

        const $ = jQuery;


        // gmap-------------------------------
        const $gmap_holder = $( '.fct-gmap-pick' );
        $gmap_holder.css( 'min-height', '312px' );

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
        if ( !$gmap_holder.length ) { return }

        const gmap = fcAddGmapView( $gmap_holder, false, getLatLngZoom() ),
              marker = fcAddGmapPick( gmap, $gmap_holder[0] );

        // apply new values after moving the marker
        $gmap_holder[0].addEventListener( 'map_changed', function(e) {
            setTimeout( function() { // wait till new values are applied to the map
                $( '#entity-geo-lat_entity-add' ).val( e.detail.marker.getPosition().lat() );
                $( '#entity-geo-long_entity-add' ).val( e.detail.marker.getPosition().lng() );
                $( '#entity-geo-zoom_entity-add' ).val( e.detail.gmap.getZoom() );
            });
        });

        
        // autocomplete-------------------------------
        const $input = $( '#entity-address_entity-add' );
        if ( !$input.length ) { return }


        let is_correct = false; // make sure, the visitor used the autocomplete, so the hidden fields are filled correctly

        // autocomplete with an advisor
        const ac = new google.maps.places.Autocomplete(
            $input[0],
            {
                componentRestrictions: { country: ['de', 'at', 'ch'] }, // Germany, Austria, Switzerland
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
        $input.on( 'blur', function() { // verify / modify / format the value by the api
            freeze = false;
            setTimeout( function() { // just a measure of economy, as `blur` fires before `place_changed`
            
                if ( is_correct ) { return }
                
                let geocoder = new google.maps.Geocoder();

                geocoder.geocode(
                    {
                        componentRestrictions: { country: 'de' },
                        address: $input.val()
                        //placeId: placeId
                    },
                    function(places, status) {
                        if ( status !== 'OK' ) { fillInValues(); return }
                        if ( is_correct || freeze ) { return }
                        fillInValues( places[0] );
                    }
                );
            }, 200 );

        });

        const $form = $input.parents( 'form' );
        $form.on( 'submit', function(e) { // don't submit the form before the address is modified

            if ( is_correct ) { return } // && $input.not( ':focus' ) OR && !freeze

            e.preventDefault();
            
            const geocoder = new google.maps.Geocoder();
            if ( !geocoder.geocode ) { submit(); return }

            geocoder.geocode(
                {
                    componentRestrictions: { country: 'de' },
                    address: $input.val()
                    //placeId: placeId
                },
                function(places, status) {
                    if ( status !== 'OK' ) { submit(); return }
                    fillInValues( places[0] );
                    submit();
                }
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
            const props = getLatLngZoom();
            gmap.panTo( props );
            gmap.setZoom( 17 );
            marker.setPosition( props );
        }

    },
    ['jQuery', 'google', 'fcAddGmapView', 'fcAddGmapPick']
);

// lunch breaks
fcLoadScriptVariable(
    '',
    'jQuery',
    function() {
        const $ = jQuery,
        $lunch = $( '<button type="button" style="float:right;margin:4px 0 0 12px">Wir haben Mittagspausen</button>' );
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
    }
);