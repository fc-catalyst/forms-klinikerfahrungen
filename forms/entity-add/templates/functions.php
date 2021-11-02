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
    
    $lat = fct1_meta_print( 'entity-geo-lat', true );
    $long = fct1_meta_print( 'entity-geo-long', true );
    $addr = fct1_meta_print( 'entity-address', true );
    $zoom = fct1_meta_print( 'entity-zoom', true );

    ?>
    <div class="fct-gmap-view"
        <?php echo $lat ? 'data-lat="'.$lat.'"' : '' ?>
        <?php echo $long ? 'data-long="'.$long.'"' : '' ?>
        <?php echo $zoom ? 'data-zoom="'.$zoom.'"' : '' ?>
        <?php echo $addr ? 'data-addr="'.$addr.'"' : '' ?>
        <?php echo 'data-title="'.get_the_title().'"' ?>
    ></div>
    <?php
}

function fct_print_contact_buttons() {
    fct_print_contact_button( 'entity-phone', fct1_meta( 'entity-phone' ) );
    fct_print_contact_button( 'entity-email', __( 'E-mail', 'fcpfo-ea' ) );
    fct_print_contact_button( 'entity-website', __( 'Website', 'fcpfo-ea' ) );
}

function fct_print_contact_button($meta, $name) {
    $button = fct1_meta_print( $meta, true );
    if ( !$button ) { return; }
    
    $commercial = !fct_free_account( fct1_meta( 'entity-tariff' ) );

    if ( strpos( $meta, 'phone' ) !== false ) { $prefix = 'tel:'; }
    if ( strpos( $meta, 'mail' ) !== false ) { $prefix = 'mailto:'; }

    ?>
        <div class="wp-block-button is-style-outline">
            <a class="wp-block-button__link has-text-color" href="<?php echo $prefix ?><?php echo $button ?>" style="color:var(--h-color)" rel="noopener<?php echo $commercial ? '' : ' nofollow noreferrer' ?>">
                <strong><?php echo $name ?></strong>
            </a>
        </div>
    <?php
}

function fct_entity_print_schedule($toggle_in = false) {

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
        $open = fct1_meta_print( $k . '-open', true );
        $close = fct1_meta_print( $k . '-close', true );

        if ( !empty( $open ) ) {
            foreach ( $open as $l => $w ) {
                if ( !$close[ $l ] ) {
                    continue;
                }
                $values[ $k ][] = $open[ $l ] . ' - ' . $close[ $l ]; // format
            }
            if ( !empty( $values[ $k ] ) ) { continue; }
        }
        
        $values[ $k ][] = '<small>' . __( 'Closed', 'fcpfo-ea' ) . '</small>';

    }
    
    if ( empty( $values ) ) { return; }
    
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
    return fct1_a_clear( $content, !fct_free_account( $tariff ) );
}

function fct_entity_print_tags() {
    fct1_meta_print( 'entity-tags', false, '<h2>'.__( 'Our range of treatments', 'fcpfo-ea' ).'</h2><p>', '</p>' );
    // Unser Behandlungsspektrum
}

function fct_free_account($tariff) {
    if ( !$tariff || $tariff == 'kostenloser_eintrag' ) {
        return true;
    }
    return false;
}
