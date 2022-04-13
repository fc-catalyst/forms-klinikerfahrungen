<?php

namespace fcpf\eat;

function print_video() {
    $url = fct1_meta( 'entity-video' );

    if ( !$url ) { return; }

    // direct video
    $video_formats = ['mp4', 'webm', 'wmv', 'mov', 'avi', 'ogg'];
    $format = strtolower( substr( $url, strrpos( $url, '.' ) + 1 ) );
    if ( in_array( $format , $video_formats ) ) {

        ?>
        <div class="fct-video">
            <video width="600" controls>
                <source src="<?php echo $url ?>" type="video/<?php echo $format ?>">
                <?php _e( 'Your browser does not support HTML video.', 'fcpfo-ea' ) ?>
            </video>
        </div>
        <?php

        return;
    }
    
    // youtube
	if ( preg_match(
   //'/^(?:https?\:\/\/(?:www\.)?youtu(?:.?)+[=\/]{1}([\w-]{11})(?:.?))$/i', $url, $match
        '/^https?\:\/\/(?:www\.)?youtu(?:.)+[=\/]{1}([\w_\-]{11})(?:[^\w_\-].+)*$/i', $url, $match
    ) ) {
        ?>
        <div class="fct-video">
            <iframe src="https://www.youtube.com/embed/<?php echo $match[1] ?>?feature=oembed&autoplay=0" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" width="600" height="312" class="youtube"></iframe>
        </div>
        <?php
    }

	return $filtered_data;

}

function print_gmap() {
    
    $addr = fct1_meta( 'entity-address' );
    $lat  = fct1_meta( 'entity-geo-lat' );
    $long = fct1_meta( 'entity-geo-long' );
    $zoom = fct1_meta( 'entity-zoom' );

    ?>
    <?php echo $addr ? '<meta itemprop="address" content="'.$addr.'">' : '' ?>
    <div class="fct-gmap-view" itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates"
        <?php echo $addr ? 'data-addr="'.$addr.'"' : '' ?>
        <?php echo $lat ? 'data-lat="'.$lat.'"' : '' ?>
        <?php echo $long ? 'data-lng="'.$long.'"' : '' ?>
        <?php echo $zoom ? 'data-zoom="'.$zoom.'"' : '' ?>
        <?php echo 'data-title="'.get_the_title().'"' ?>
    >
        <?php echo $lat ? '<meta itemprop="latitude" content="'.$lat.'">' : '' ?>
        <?php echo $long ? '<meta itemprop="longitude" content="'.$long.'">' : '' ?>
    </div>
    <?php
    
    // schema part
    ?>
    <div itemprop="contactPoint" itemscope itemtype="https://schema.org/ContactPoint">
        <meta itemprop="contactType" content="customer service">
        <meta itemprop="telephone" content="<?php echo fct1_meta( 'entity-phone' ) ?>">
    </div>
    <?php
}

function print_contact_buttons() {
    print_contact_button( 'entity-phone', fct1_meta( 'entity-phone' ), 'telephone' );
    print_contact_button( 'entity-email', __( 'E-mail', 'fcpfo-ea' ) );
    print_contact_button( 'entity-website', __( 'Website', 'fcpfo-ea' ), 'url' );
}

function print_contact_button($meta_name, $name, $itemprop = '') {
    $meta_value = fct1_meta( $meta_name );
    if ( !$meta_value ) { return; }

    if ( strpos( $meta_name, 'phone' ) !== false ) { $prefix = 'tel:'; }
    if ( strpos( $meta_name, 'mail' ) !== false ) { $prefix = 'mailto:'; }
    if ( strpos( $meta_name, 'website' ) !== false ) {
        $attrs = ' target="_blank"';

        $tariff_running = fcp_tariff_get()->running;
        switch ( $tariff_running ) {
            case 'standardeintrag':
                $attrs .= ' rel="nofollow noopener noreferrer"';
                break;
            case 'premiumeintrag':
                $attrs .= ' rel="noopener"';
                break;
            default:
                return;
        }
    }

    ?>
        <div class="wp-block-button is-style-outline">
            <a class="wp-block-button__link has-text-color" href="<?php echo $prefix ?><?php echo $meta_value ?>" style="color:var(--h-color)"<?php echo $attrs ?>>
                <strong<?php echo $itemprop ? ' itemprop="'.$itemprop.'" content="'.$meta_value.'" ' : '' ?>><?php echo $name ?></strong>
            </a>
        </div>
    <?php
}

function entity_print_schedule($toggle_in = false) {

    $fields = [
        'entity-mo' => 'Monday', // -open, -close, translation goes lower
        'entity-tu' => 'Tuesday',
        'entity-we' => 'Wednesday',
        'entity-th' => 'Thursday',
        'entity-fr' => 'Friday',
        'entity-sa' => 'Saturday',
        'entity-su' => 'Sunday'
    ];

    $values = [];
    $schema = []; // ++use lunch breaks later
    foreach ( $fields as $k => $v ) {
        $open = fct1_meta( $k . '-open' );
        $close = fct1_meta( $k . '-close' );

        if ( !empty( $open ) ) {
            foreach ( $open as $l => $w ) {
                if ( !$close[ $l ] ) {
                    continue;
                }
                $values[ $k ][] = $open[ $l ] . ' - ' . $close[ $l ]; // format
                $schema[ $k ]['open'] = $schema[ $k ]['open'] ? $schema[ $k ]['open'] : $open[ $l ];
                $schema[ $k ]['close'] = $close[ $l ];
            }
            if ( !empty( $values[ $k ] ) ) { continue; }
        }
        
        $values[ $k ][] = '<small>' . \__( 'Closed', 'fcpfo-ea' ) . '</small>';

    }
    
    if ( empty( $schema ) ) { return; }
    
    ?>
    <div class="wp-block-button is-style-outline fct-button-select fct-open-next<?php echo $toggle_in ? ' fct-active' : '' ?>">
        <a class="wp-block-button__link has-text-color" href="#" style="color:var(--h-color)">
            <strong><?php _e( 'Working hours', 'fcpfo-ea' ) ?></strong>
        </a>
    </div>
    <dl class="fct-schedule-list">
    <?php
    
    foreach ( $values as $k => $v ) {
        ?>
        <dt>
            <?php echo __( $fields[ $k ] ) ?>
        </dt>
        <dd>
            <?php echo implode( '<br/>', $v ) ?>
        </dd>

        <?php if ( !$schema[ $k ] ) { continue; } ?>
        <meta itemprop="openingHours" content="<?php
            echo substr( $fields[ $k ], 0, 2 ) . ' ' .
                 $schema[ $k ]['open'] . '-' .
                 $schema[ $k ]['close'];
        ?>">
        <?php
    }
    
    ?>
    </dl>
    <?php
}

function entity_print_gallery() {

    $gallery = fct1_meta( 'gallery-images' );
    if ( empty( $gallery ) ) { return; }

?>
    <h2 class="with-line"><?php _e( 'Gallery', 'fcpfo' ) ?></h2>

    <div id="entity-gallery">
        <?php foreach ( $gallery as $v ) { ?>
            <figure class="wp-block-image">
                <a href="<?php echo fct1_image_src( 'entity/' . get_the_ID() . '/gallery/' . $v )[0] ?>">
                    <?php fct1_image_print( 'entity/' . get_the_ID() . '/gallery/' . $v, [554,554] ) ?>
                </a>
            </figure>
        <?php } ?>
    </div>
<?php
}

function entity_content_filter($content) {
    if ( !$content ) { return ''; }
    remove_filter( 'the_content', 'do_shortcode', 11 );
    return apply_filters( 'the_content', fcp_tariff_filter_text( $content ) );
    add_filter( 'the_content', 'do_shortcode', 11 );
}

function entity_print_tags() {
    echo fct1_meta( 'entity-tags', '<h2>'.__( 'Our range of treatments', 'fcpfo-ea' ).'</h2><p>', '</p>' );
    // Unser Behandlungsspektrum
}

function entity_tile_print($footer = '') {
    static $badgesdir = '';
    if ( !$badgesdir ) {
        $badgesdir = str_replace( ABSPATH, get_site_url() . '/', dirname( __DIR__ ) . '/templates/images/' );
    }
?>
    <article class="post-<?php the_ID() ?> <?php echo get_post_type() ?> type-<?php echo get_post_type() ?> status-<?php echo get_post_status() ?> entry" itemscope="" itemtype="https://schema.org/CreativeWork">

        <a class="entry-link-cover" rel="bookmark" href="<?php the_permalink(); ?>" title="<?php the_title() ?>"></a>

        <header class="entry-header">
            <div class="entity-badges">
                <img loading="lazy" width="23" height="38" src="<?php echo $badgesdir . 'verified.png' ?>" alt="" title="<?php _e( 'Verified', 'fcpfo-ea' ) ?>" />
                <?php if ( fct1_meta( 'entity-featured' ) ) { ?>
                    <img loading="lazy" width="23" height="38" src="<?php echo $badgesdir . 'featured.png' ?>" alt="" title="<?php _e( 'Featured', 'fcpfo-ea' ) ?>" />
                <?php } ?>
            </div>
            <?php if ( $back_img = fct1_meta( 'entity-photo' )[0] ) { ?>
                <div class="entry-photo">
                    <?php
                        fct1_image_print(
                            'entity/' . get_the_ID() . '/' . $back_img,
                            [454, 210],
                            ['center', 'top'],
                            get_the_title() . ' ' . __( 'Photo', 'fcpfo-ea' )
                        )
                    ?>
                </div>
            <?php } ?>
            <h2 class="entry-title" itemprop="headline">
                <a href="<?php the_permalink() ?>"><?php the_title() ?></a>
            </h2>
        </header>
        <div class="entry-details">
            <?php if ( $ava = fct1_meta( 'entity-avatar' )[0] ) { ?>
            <div class="entity-avatar">
                <?php fct1_image_print( 'entity/' . get_the_ID() . '/' . $ava, [74,74], 0, get_the_title() . ' ' . __( 'Icon', 'fcpfo-ea' ) ) ?>
            </div>
            <?php } ?>
            <div class="entity-about">
                <p>
                    <?php echo fct1_meta( 'entity-specialty' ); echo fct1_meta( 'entity-geo-city', ' in ' ) ?>
                </p>
                <?php if ( method_exists( 'FCP_Comment_Rate', 'print_stars_total' ) ) { ?>
                    <?php \FCP_Comment_Rate::print_stars_total() ?>
                <?php } ?>
            </div>
        </div>
        <?php if ( !empty( $footer ) ) { ?>
        <footer>
            <?php echo $footer ?>
        </footer>
        <?php } ?>
    </article>
<?php
}