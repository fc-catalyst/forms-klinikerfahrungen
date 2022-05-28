<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:66.66%" itemprop="description">

        <h2><?php _e( 'About', 'fcpfo-ea' ) ?></h2>

        <?php \fcpf\eat\entity_photo_print() ?>

        <?php echo \fcpf\eat\entity_content_filter( fct1_meta( 'entity-content' ) ) ?>

        <?php \fcpf\eat\print_video() ?>

        <?php \fcpf\eat\entity_print_tags() ?>
       
    </div>

    <div class="wp-block-column" style="flex-basis:33.33%">
    
        <div style="height:97px" aria-hidden="true" class="wp-block-spacer"></div>

        <?php \fcpf\eat\print_gmap() ?>

        <div style="height:25px" aria-hidden="true" class="wp-block-spacer"></div>
        
        <?php \fcpf\eat\entity_print_workhours( true ) ?>

        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>

        <?php \fcpf\eat\print_contact_buttons( true ) ?>

    </div>
    
</div>