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

        var workhours_popup = new FCP_Forms_Hidden( '#clinic-work-hours' );
        $( '#entity-working-hours_clinic' ).on( 'click', function() {
            workhours_popup.show( this );
        });
        
        var gmap_popup = new FCP_Forms_Hidden( '#clinic-specify-map' );
        $( '#entity-map_clinic' ).on( 'click', function() {
            gmap_popup.show( this );
        });

    });

})();
