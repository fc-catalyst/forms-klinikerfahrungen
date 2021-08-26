<?php

// ++ move the functions somewhere, index.php with namespace or a class?

$imgs_dir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );


get_header();

if ( have_posts() ) :
    while ( have_posts() ) :
        the_post();

?>

<article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">
    <div class="post-content" itemprop="text">
        <div class="entry-content">

<!-- gutenberg copy start -->

<div class="wp-block-columns alignwide are-vertically-aligned-center fct-entity-hero">

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:66.66%">
        <div class="fct-entity-badges">
            <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'verified.png' ?>" alt="Verified" title="Verified" />
            <?php if ( fct_print_meta( 'entity-featured', true ) ) { ?>
                <img loading="lazy" width="46" height="76" src="<?php echo $imgs_dir . 'featured.png' ?>" alt="Featured" title="Featured" />
            <?php } ?>
        </div>
        
        <h1><?php the_title() ?></h1>
        <p><?php fct_print_meta( 'entity-specialty' ); fct_print_meta( 'entity-geo-city', false, ' in ' ) ?></p>
        
        <?php if ( method_exists( 'FCP_Comment_Rate', 'print_rating_summary_short' ) ) { ?>
            <?php FCP_Comment_Rate::print_rating_summary_short() ?>
        <?php } ?>
        
        <div class="wp-block-buttons">
            <div class="wp-block-button is-style-outline">
                <a class="wp-block-button__link has-white-color has-text-color" href="#bewertungen">
                    <?php echo wp_count_comments( get_the_ID() )->approved ? 'Bewertungen' : 'Bewerten' ?>
                </a>
            </div>
        </div>
    </div>

    <div class="wp-block-column is-vertically-aligned-center" style="flex-basis:33.33%">
        <?php if ( $logo = fct_print_meta( 'entity-avatar', true )[0] ) { ?>
        <div class="fct-entity-photo">
            <img loading="lazy" width="100%" height="100%"
                src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $logo ?>"
                alt="<?php the_title() ?> Logo"
            />
        </div>
        <?php } ?>
    </div>

</div>


<div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>


<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:66.66%">

        <h2>Über</h2>

        <?php fct_print_meta( 'entity-content' ) ?>

        <h2>Unser Behandlungsspektrum</h2>

        <?php fct_print_meta( 'entity-tags', false, '<p>', '</p>' ) ?>

        <div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>
        
        <div class="wp-block-buttons is-content-justification-full">

        <?php if ( $button = fct_print_meta( 'entity-phone', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="tel:<?php echo $button ?>" style="color:var(--h-color)"><strong>Telefon</strong></a></div>
        <?php } ?>
        <?php if ( $button = fct_print_meta( 'entity-email', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="mailto:<?php echo $button ?>" style="color:var(--h-color)"><strong>E-mail</strong></a></div>
        <?php } ?>
        <?php if ( $button = fct_print_meta( 'entity-website', true ) ) { ?>
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-text-color" href="<?php echo $button ?>" style="color:var(--h-color)"><strong>Website</strong></a></div>
        <?php } ?>

        </div>
        
        <div class="wp-block-buttons is-content-justification-full">
            <div class="wp-block-button is-style-outline fct-button-select fct-open-next"><a class="wp-block-button__link has-text-color" href="#" style="color:var(--h-color)"><strong>Öffnungszeiten</strong></a></div>
            <?php fct_print_schedule() ?>
        </div>
        
    </div>
    <div class="wp-block-column fct-vertical-gallery-wrap" style="flex-basis:33.33%">
    
        <h2 class="with-line">Gallerie</h2>

        <div class="fct-vertical-gallery">
            <?php 
                $gallery = fct_print_meta( 'gallery-images', true );
                if ( !empty( $gallery ) ) {
                    foreach ( $gallery as $v ) {
                    ?>
                        <figure class="wp-block-image">
                            <img loading="lazy" width="562" src="<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/gallery/' . $v ?>" alt="" />
                        </figure>
                    <?php
                    }
                }
            ?>
        </div>

    </div>
    
</div>


<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>


<div class="wp-block-columns">
    <div class="wp-block-column fct-video" style="flex-basis:66.66%">
    
        <?php fct_print_video() ?>

    </div>
    <div class="wp-block-column" style="flex-basis:33.33%">

        

    </div>
</div>


<!-- gutenberg copy end -->
        </div>
    </div>
</article>

<div style="height:100px" aria-hidden="true" class="wp-block-spacer"></div>

<div class="entry-content" id="bewertungen">
    <?php comments_template() ?>
</div>
	
<?php

        if ( $back_img = fct_print_meta( 'entity-photo', true )[0] ) {
            ?><style>
                .post-<?php the_ID() ?> .fct-entity-hero {
                    --entity-bg:url( '<?php echo wp_get_upload_dir()['url'] . '/entity/' . get_the_ID() . '/' . $back_img ?>' );
                }
            </style><?php
        }

    endwhile;
endif;

get_footer();


function fct_print_video() {
    $url = fct_print_meta( 'entity-video', true );

    if ( !$url ) { return; }

    // direct video
    $video_formats = ['mp4', 'webm', 'wmv', 'mov', 'avi', 'ogg'];
    $format = strtolower( substr( $url, strrpos( $url, '.' ) + 1 ) );
    if ( in_array( $format , $video_formats ) ) {

        ?>
        <video width="600" controls>
            <source src="<?php echo $url ?>" type="video/<?php echo $format ?>">
            Your browser does not support HTML video.
        </video>
        <?php

        return;
    }
    
    // youtube
	if ( preg_match(
        '/^(?:https?\:\/\/(?:www\.)?youtu(?:.?)+[=\/]{1}([\w-]{11})(?:.?))$/i', $url, $match
    ) ) {
        ?>
        <iframe src="https://www.youtube.com/embed/<?php echo $match[1] ?>?feature=oembed&autoplay=0" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" width="600" height="312" class="youtube"></iframe>
        <?php
    }

	return $filtered_data;

}


function fct_print_schedule() {

    $fields = [
        'entity-mo' => __( 'Monday' ), // -open, -close
        'entity-tu' => __( 'Tuesday' ),
        'entity-we' => __( 'Wednesday' ),
        'entity-th' => __( 'Thursday' ),
        'entity-fr' => __( 'Friday' ),
        'entity-sa' => __( 'Saturday' ),
        'entity-su' => __( 'Sunday' )
    ];

    $values = [];
    foreach ( $fields as $k => $v ) {
        $open = fct_print_meta( $k . '-open', true );
        $close = fct_print_meta( $k . '-close', true );

        if ( !empty( $open ) ) {
            foreach ( $open as $l => $w ) {
                if ( !$close[ $l ] ) {
                    continue;
                }
                $values[ $k ][] = $open[ $l ] . ' - ' . $close[ $l ]; // format
            }
            if ( !empty( $values[ $k ] ) ) { continue; }
        }
        
        $values[ $k ][] = '<small>' . __( 'Closed' ) . '</small>';

    }
    
    if ( empty( $values ) ) { return; }
    
    ?>
    <dl class="fct-schedule-list">
    <?php
    
    foreach ( $values as $k => $v ) {
        ?>
        <dt>
            <?php echo $fields[ $k ] ?>
        </dt>
        <dd>
            <?php echo implode( '<br/>', $v ) ?>
        </dd>
        <?php
    }
    
    ?>
    </dl>
    <?php
}


function fct_print_meta($name, $return = false, $before = '', $after = '') { // ++ add reset for lists ++ move to common ++ maybe wrap in class
    static $a = null;
    if ( !$name ) { return ''; }
    if ( $a === null ) {
        $a = get_post_meta( get_the_ID(), '' );
    }

    if ( is_serialized( $a[ $name ][0] ) ) {
        $result = unserialize( $a[ $name ][0] );
    } else {
        $result = trim( $a[ $name ][0] ) ? $before . $a[ $name ][0] . $after : '';
    }

    if ( $return ) {
        return $result;
    }
    echo $result;
}
