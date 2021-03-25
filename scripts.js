(function() {

    // ++list of autocomplete && pickers
    
    var b, a = setInterval( function() {
        if ( document.readyState !== "complete" && document.readyState !== "interactive" ) {
            return;
        }

        if ( typeof window.jQuery === 'undefined' ) {
            return;
        }

        window.clearInterval( a );
        var $ = window.jQuery;
        autofill();
/*
        var externals = {
            'google' : '<script src="https://maps.googleapis.com/maps/api/js?key=KEY&libraries=places"></script>',
            'datepicker' : '<script src="https://maps.googleapis.com/maps/api/js?key=KEY&libraries=places"></script>'
        };

        for ( let i in externals ) {
            jQuery( 'head' ).append( externals[i] );
        }

        b = setInterval( function() {
            let goOn = true;
            for ( let i in externals ) {
                if ( typeof window[i] === 'undefined' ) {
                    goOn = false;
                }
            }
            if ( !goOn ) {
                return;
            }

            window.clearInterval( b );
            console.log( 'Subs are loaded' );
            load_externals();

        }, 500);
//*/

    }, 500 );
    
    function autofill() {
        var uniques = [];
        $( '*[data-fcp-autofill]' ).each( function() {
            var $self = $( this ),
                autofill = $self.attr( 'data-fcp-autofill' ),
                $form = $self.parents( 'form' );
            if ( ~uniques.indexOf( autofill ) ) {
                return true;
            }
            uniques.push( autofill );

            $form.find( '*[name="'+autofill+'"]' ).each( function() {

                $( this ).on( 'blur', function() {
                    var $self = $( this ),
                        name = $self.attr( 'name' ),
                        val = $self.val(),
                        $target = $form.find( '*[data-fcp-autofill="'+name+'"]' );
                    if ( $target.val().trim() === '' ) {
                        $target.val( val );
                    }
                });
            });
        });
    }

})();
