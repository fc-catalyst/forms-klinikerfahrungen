<?php

get_header();

include_once ( __DIR__ . '/functions.php' );

$args = [
    'post_type'        => ['clinic', 'doctor'],
    'orderby'          => 'date',
    'order'            => 'DESC',
    'posts_per_page'   => '12',
    'paged'            => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
    'post_status'      => ['publish', 'private'],
];

$args['meta_query'] = fct_archive_filters();

$wp_query = new WP_Query( $args );


?>
    <div class="wrap-width">
    <?php //the_archive_title( '<h1>', '</h1>' ) ?>
    <h1><?php _e( 'Clinics and Doctors', 'fcpfo-ea' ) ?></h1>
    
    <?php fct_search_stats( '<p style="margin-top:-25px;opacity:0.45">', '.</p>' ) ?>

    <?php echo do_shortcode('[fcp-form dir="entity-search" notcontent]') ?>
    
<?php

if ( $wp_query->have_posts() ) {
    while ( $wp_query->have_posts() ) {
        $wp_query->the_post();

        fct_entity_tile_print();
        
    }
    get_template_part( 'template-parts/pagination' );
    ?></div><!-- /wrap-width --><?php
} else {
    fct_search_stats( '<h2 id="nothing-found-headline">', '</h2>' );
    // delay the dramatic headline appearance as more results still can appear ++can do better ++can add loader
    ?>
<style>
#nothing-found-headline {
    text-align:center;
    opacity:0;
    animation:nothingFoundHeadline 0.4s ease-in 3s forwards;
}
@keyframes nothingFoundHeadline { to { opacity:1; } }
</style>
    <?php
}

// load results async in a wider area, if not many found ++can place in a separate file
if ( $args['meta_query'] && $wp_query->post_count < 7 ) {
?>
<script>
fcLoadScriptVariable(
    '',
    'jQuery',
    function() {

        const $ = jQuery,
              _ = new URLSearchParams( window.location.search ),
              [ plc, spc ] = [ _.get('place'), _.get('specialty') ],
              $holder = $( '#main-content .wrap-width' );

        if ( plc === null || spc === null ) { return }
        if ( $holder.length === 0 || $holder.find( 'article' ).length > 6 ) { return }

        fcLoadScriptVariable( // ++move outside?
            'https://maps.googleapis.com/maps/api/js?key='+fcGmapKey+'&libraries=places&language=de-DE',
            'google',
            function() {

                // get the already printed ids
                const pids = [];

                $holder.find( 'article' ).each( function() {
                    const cls = $( this ).attr( 'class' );
                    if ( !~cls.indexOf( 'post-' ) ) { return true }
                    pids.push( cls.replace( /^.*post\-(\d+).*$/, "$1" ) );
                });

                // get the lat lng by address (state, postcode)
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode(
                    {
                        componentRestrictions: { country: 'de' },
                        address: plc
                    },
                    function(places, status) {
                        if ( status !== 'OK' || !places[0] ) { return }

                        const [ lat, lng ] = [ places[0].geometry.location.lat(), places[0].geometry.location.lng() ];
                        if ( !lat || !lng ) { return }

                        $.get( '/wp-json/fcp-forms/v1/entities_around/' + [lat,lng,spc].join('/') + (pids[0] ? '/'+pids.join(',') : ''), function( data ) {
                            $holder.append( data.content );
                            $holder.children( 'h2' ).remove();
                        });

                    }
                );
            }
        );
    }
);
</script>
<?php
}

wp_reset_query();

?>
<div style="height:80px" aria-hidden="true" class="wp-block-spacer"></div>
<?php

get_footer();


function fct_archive_filters() {
    global $wpdb;

    $query_meta = [];

    if ( $_GET['place'] ) {
        $val = $wpdb->_real_escape( htmlspecialchars( urldecode( $_GET['place'] ) ) );

        $query_meta[] = [
            'relation' => 'OR',
            [
                'key' => 'entity-region',
                'value' => $val
            ],
            [
                'key' => 'entity-geo-city',
                'value' => $val
            ],
            [
                'key' => 'entity-geo-postcode',
                'value' => $val
            ]
        ];
    }

    if ( $_GET['specialty'] ) {
        $val = $wpdb->_real_escape( htmlspecialchars( urldecode( $_GET['specialty'] ) ) );

        $query_meta[] = [ [
                'key' => 'entity-specialty',
                'value' => $val
            ]
        ];
    }


    if ( count( $query_meta ) > 1 ) {
        $query_meta['relation'] = 'AND';
        return $query_meta;
    }
    
    return $query_meta[0];
}

function fct_search_stats($before = '', $after = '') {
    if ( !$_GET['specialty'] && !$_GET['place'] ) { return; }
    
    global $wp_query;
    if ( $wp_query->have_posts() ) {
        if ( $wp_query->post_count === 1 ) {
            $count = __( '1 result', 'fcpfo-ea' );
        } else {
            $count = sprintf( __( '%s results', 'fcpfo-ea' ), $wp_query->post_count );
        }
    } else {
        $count = __( 'Nothing', 'fcpfo-ea' );
    }
    
    echo $before .
        sprintf( __( '%s found', 'fcpfo-ea' ), $count ) . 
        ( $_GET['specialty'] ? ' für <strong>' . $_GET['specialty'] . '</strong>' : '' ) .
        ( $_GET['place'] ? ' in <strong>' . $_GET['place'] . '</strong>' : '' ) .
        $after;
    
}