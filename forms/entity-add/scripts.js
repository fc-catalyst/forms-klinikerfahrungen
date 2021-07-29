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

        var workhours_popup = new FCP_Forms_Hidden( '#entity-working-hours' );
        $( '#entity-working-hours_entity-add' ).on( 'click', function() {
            workhours_popup.show( this );
        });
        
        var gmap_popup = new FCP_Forms_Hidden( '#entity-specify-map' );
        $( '#entity-map_entity-add' ).on( 'click', function() {
            gmap_popup.show( this );
        });

    });

})();
