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

        // working hours fields operate
        
        // working hours picker
        
        // picker for branche?
        var workhours_popup = new FCP_Forms_Hidden( '#clinic-work-hours' );
        $( '#entity-working-hours_clinic' ).on( 'click', function() {
            workhours_popup.show();
        });

        // ++tmp
        workhours_popup.show();
    });

})();
