;'use strict';
( function() {

    var a = setInterval( function() {
        if ( document.readyState !== "complete" && document.readyState !== "interactive" ) {
            return;
        }

        window.clearInterval( a );
        var $ = window.jQuery;

        $( '[data-autocomplete-advise]' ).each( function() {

            new FCP_Advisor(
                $( this ),
                ['Allergologen', 'Allgemein- &amp; Hausärzte', 'Augenärzte', 'Chirurgen', 'Dermatologen', 'Gynäkologen', 'HNO-Ärzte', 'Kardiologen', 'Kinderärzte', 'Neurologen', 'Orthopäden', 'Plastische und Ästhetische Chirurgen', 'Psychologen &amp; Psychotherapie', 'Urologen', 'Zahnärzte']
            );

        });

    });
    
})();
