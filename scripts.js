;(function() {

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
            "button" : ".fcp-form button",
            "empty_class" : "fcp-form-empty"
        };
        
        // change the content of file lable
        $( s.file ).on( 'change', function() {
            var $self = $( this ),
                $label = $self.next( 'label' );
            empty_file( $self );
            if ( $self[0].files.length === 0 ) {
                var label = $self.prop( 'multiple' ) ?
                    $self.attr( 'data-select-files' ) :
                    $self.attr( 'data-select-file' );
                $label.html( label );
                return;
            }
            if ( $self[0].files.length === 1 ) {
                $label.html( $self[0].files[0]['name'] );
                return;
            }
            $label.html( $self[0].files.length + ' ' + $self.attr( 'data-files-selected' ) );
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
        $( s.button ).each( function() {
            empty_button( $( this ) );
        });
        
        function empty_file($self) {
            if ( $self[0].files.length === 0 ) {
                $self.addClass( s.empty_class );
                return;
            }
            $self.removeClass( s.empty_class );
        }
        function empty_select($self) {
            if ( $self.children( 'option:selected' ).val() === '' ) {
                $self.addClass( s.empty_class );
                return;
            }
            $self.removeClass( s.empty_class );
        }
        function empty_button($self) {
            $self.addClass( s.empty_class );
        }

    });

})();

// hidden section
function FCP_Forms_Hidden(section) {

    if ( typeof section === 'string' ) {
        this.section = document.querySelector( section );
    } else if ( section instanceof jQuery ) {
        this.section = section[0];
    } else {
        this.section = section;
    }
    
    if ( !this.section ) {
        this.show = this.hide = function(){};
        return;
    }
    
    var self = this;
    
    this.show = function(target) {
        this.section.classList.add( 'fcp-active' );
        document.querySelector( 'body' ).style.overflow = 'hidden';
        document.addEventListener( 'keydown', key_press );
        presave_values();
        this.section.querySelector( 'input, button, select, textarea' ).focus();
        if ( typeof target === 'object' ) {
            self.target = target;
        } else {
            delete self.target;
        }
    };
    
    this.hide = function() {
        this.section.classList.remove( 'fcp-active' );
        document.querySelector( 'body' ).style.overflow = null;
        document.removeEventListener( 'keydown', key_press );
        if ( typeof self.target !== 'undefined' ) {
            self.target.focus();
        }
    };

    // close buttons
    var apply = document.createElement( 'button' );
    apply.title = 'Apply';
    apply.type = 'button';
    apply.className = 'fcp-section--close fcp-section--apply';
    apply.addEventListener( 'click', function() {
       self.hide();
    });
    this.section.appendChild( apply );
    
    var discard = document.createElement( 'button' );
    discard.title = 'Discard';
    discard.type = 'button';
    discard.className = 'fcp-section--close fcp-section--discard';
    discard.addEventListener( 'click', function() {
       self.hide();
       restore_values();
    });
    this.section.appendChild( discard );
    
    function key_press(e) {
        if ( e.code === 'Enter' ) {
            if ( !~['input','button','select','textarea'].indexOf( e.target.nodeName.toLowerCase() ) ) { return true; }
            e.preventDefault();
            self.hide();
            return false;
        }
        if ( e.code === 'Escape' ) {
            e.preventDefault();
            self.hide();
            restore_values();
            return false;
        }
    }
    
    function presave_values() {
        self.section.querySelectorAll( 'input, button, select, textarea' ).forEach( function(a) {
            a.setAttribute( 'data-presaved-value', a.value );
        });
    }
    function restore_values() {
        self.section.querySelectorAll( 'input, button, select, textarea' ).forEach( function(a) {
            a.value = a.getAttribute( 'data-presaved-value' );
            a.removeAttribute( 'data-presaved-value' );
        });
    }
    
}
