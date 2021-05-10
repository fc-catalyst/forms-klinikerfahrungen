;'use strict';
( function() {

    var b, a = setInterval( function() { // wait for DOM and jQuery
        if ( document.readyState !== "complete" && document.readyState !== "interactive" ) {
            return;
        }
        if ( typeof window.jQuery === 'undefined' ) {
            return;
        }
        if ( typeof window.fcp_forms_assets_url === 'undefined' ) {
            return;
        }
        
        window.clearInterval( a );
        var $ = window.jQuery;

        // DOM can be operated with $ from here

        // load other needed scripts
        var assets = window.fcp_forms_assets_url,
            externals = {
//            'google' : 'https://maps.googleapis.com/maps/api/js?key={KEY}&libraries=places'
            'FCP_Advisor': assets + 'advisor',
            'FCP_Slider' : assets + 'slider'
        };

        for ( var i in externals ) {
            $( 'body' ).append( '<script src="' + externals[i] + '.js" async></script>' );
            $( 'body' ).append( '<link rel="stylesheet" href="' + externals[i] + '.css" type="text/css" media="all" />' );
        }

        b = setInterval( function() {
            var goOn = true;

            for ( var i in externals ) {
                if ( typeof window[i] === 'undefined' ) {
                    goOn = false;
                }
            }

            if ( !goOn ) {
                return;
            }

            window.clearInterval( b );
            
            // Libraries can be used from here

            new FCP_Advisor(
                $( '#fcp-f-clinic-search--spezialisierung' ),
                ['Allergologen', 'Allgemein- &amp; Hausärzte', 'Augenärzte', 'Chirurgen', 'Dermatologen', 'Gynäkologen', 'HNO-Ärzte', 'Kardiologen', 'Kinderärzte', 'Neurologen', 'Orthopäden', 'Plastische und Ästhetische Chirurgen', 'Psychologen &amp; Psychotherapie', 'Urologen', 'Zahnärzte']
            );
            
            // ++ google maps place germany region advisor instead of it
            new FCP_Advisor(
                $( '#fcp-f-clinic-search--region' ),
                ['Allergologen', 'Allgemein- &amp; Hausärzte', 'Augenärzte', 'Chirurgen', 'Dermatologen', 'Gynäkologen', 'HNO-Ärzte', 'Kardiologen', 'Kinderärzte', 'Neurologen', 'Orthopäden', 'Plastische und Ästhetische Chirurgen', 'Psychologen &amp; Psychotherapie', 'Urologen', 'Zahnärzte']
            );
            
            new FCP_Slider(
                '.fcp-group-slider > .wp-block-group__inner-container',
                { 'navigation': [ 'arrows', 'dots', 'can_block' ] }
            );

        }, 500 );

    }, 500 );
    
})();

// ++can add timeout to slop loading if takes too long..
// ++can do into a class!!
