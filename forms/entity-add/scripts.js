!function(){let a=setInterval(function(){let b=document.readyState;if(b!=='complete'&&b!=='interactive'||typeof jQuery==='undefined'){return}let $=jQuery;clearInterval(a);a=null;

    var workhours_popup = new FCP_Forms_Hidden( '#entity-working-hours' );
    $( '#entity-working-hours_entity-add' ).on( 'click', function() {
        workhours_popup.show( this );
    });
    
    var gmap_popup = new FCP_Forms_Hidden( '#entity-specify-map' );
    $( '#entity-map_entity-add' ).on( 'click', function() {
        gmap_popup.show( this );
    });

},300)}();
