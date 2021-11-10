<?php

function fct_print_video() {
    $url = fct1_meta_print( 'entity-video', true );

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
        '/^(?:https?\:\/\/(?:www\.)?youtu(?:.?)+[=\/]{1}([\w-]{11})(?:.?))$/i', $url, $match
    ) ) {
        ?>
        <div class="fct-video">
            <iframe src="https://www.youtube.com/embed/<?php echo $match[1] ?>?feature=oembed&autoplay=0" frameborder="0" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" width="600" height="312" class="youtube"></iframe>
        </div>
        <?php
    }

	return $filtered_data;

}

function fct_print_gmap() {
    
    $addr = fct1_meta( 'entity-address' );
    $lat = fct1_meta( 'entity-geo-lat' );
    $long = fct1_meta( 'entity-geo-long' );
    $zoom = fct1_meta( 'entity-zoom' );

    ?>
    <?php echo $addr ? '<meta itemprop="address" content="'.$addr.'">' : '' ?>
    <div class="fct-gmap-view" itemprop="geo" itemscope itemtype="https://schema.org/GeoCoordinates"
        <?php echo $addr ? 'data-addr="'.$addr.'"' : '' ?>
        <?php echo $lat ? 'data-lat="'.$lat.'"' : '' ?>
        <?php echo $long ? 'data-long="'.$long.'"' : '' ?>
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
        <meta itemprop="telephone" content="<?php fct1_meta_print( 'entity-phone' ) ?>">
    </div>
    <?php
}

function fct_print_contact_buttons() {
    fct_print_contact_button( 'entity-phone', fct1_meta( 'entity-phone' ), 'telephone' );
    fct_print_contact_button( 'entity-email', __( 'E-mail', 'fcpfo-ea' ) );
    fct_print_contact_button( 'entity-website', __( 'Website', 'fcpfo-ea' ), 'url' );
}

function fct_print_contact_button($meta, $name, $itemprop = '') {
    $button = fct1_meta( $meta );
    if ( !$button ) { return; }
    
    $commercial = !fct_free_account( fct1_meta( 'entity-tariff' ) );

    if ( strpos( $meta, 'phone' ) !== false ) { $prefix = 'tel:'; }
    if ( strpos( $meta, 'mail' ) !== false ) { $prefix = 'mailto:'; }

    ?>
        <div class="wp-block-button is-style-outline">
            <a class="wp-block-button__link has-text-color" href="<?php echo $prefix ?><?php echo $button ?>" style="color:var(--h-color)" rel="noopener<?php echo $commercial ? '' : ' nofollow noreferrer' ?>">
                <strong<?php echo $itemprop ? ' itemprop="'.$itemprop.'" content="'.$button.'" ' : '' ?>><?php echo $name ?></strong>
            </a>
        </div>
    <?php
}

function fct_entity_print_schedule($toggle_in = false) {

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
        
        $values[ $k ][] = '<small>' . __( 'Closed', 'fcpfo-ea' ) . '</small>';

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

function fct_entity_print_gallery() {

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
<?
}

function fct_entity_content_filter($content, $tariff = '') {
    if ( !$content ) { return; }
    return apply_filters( 'the_content', fct1_a_clear( $content, !fct_free_account( $tariff ) ) );
}

function fct_entity_print_tags() {
    fct1_meta_print( 'entity-tags', false, '<h2>'.__( 'Our range of treatments', 'fcpfo-ea' ).'</h2><p>', '</p>' );
    // Unser Behandlungsspektrum
}

function fct_free_account($tariff) {
    if ( !$tariff || $tariff === 'kostenloser_eintrag' ) {
        return true;
    }
    return false;
}
