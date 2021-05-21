(function() {

    var a = setInterval( function() {
        if ( document.readyState !== "complete" && document.readyState !== "interactive" ) {
            return;
        }

        if ( typeof window.jQuery === 'undefined' ) {
            return;
        }

        var $ = window.jQuery;
        window.clearInterval( a );

        var s = {
            "file" : ".fcp-form input[type=file]",
            "select" : ".fcp-form select",
            "empty" : "fcp-form-empty"
        };
        
        // change the content of file lable
        $( s.file ).on( 'change', function() {
            var $self = $( this ),
                $label = $self.next( 'label' );

            empty_file( $self );
            
            if ( $self[0].files.length === 0 ) {
                $label.html( 'Select File(s)' ); // ++ if is multiple - add .s
                return;
            }
            if ( $self[0].files.length === 1 ) {
                $label.html( $self[0].files[0]['name'] );
                return;
            }
            $label.html( $self[0].files.length + ' Files Chosen' );
        });
        
        // change the style of empty select
        $( s.select ).on( 'change', function() {
            empty_select( $( this ) );
        });
        
        // placeholder replacement on init
        $( s.file ).each( function() {
            empty_file( $( this ) );
        });
        $( s.select ).each( function() {
            empty_select( $( this ) );
        });
        
        function empty_file($self) {
            if ( $self[0].files.length === 0 ) {
                $self.addClass( s.empty );
                return;
            }
            $self.removeClass( s.empty );
        }
        function empty_select($self) {
            if ( $self.children( 'option:selected' ).val() === '' ) {
                $self.addClass( s.empty );
                return;
            }
            $self.removeClass( s.empty );
        }

    });

/*    
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
//*/

})();
