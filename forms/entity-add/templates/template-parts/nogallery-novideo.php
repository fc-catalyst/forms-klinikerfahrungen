<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:66.66%" itemprop="description">

        <h2><?php _e( 'About', 'fcpfo-ea' ) ?></h2>

        <?php echo \fcpf\eat\entity_content_filter( fct1_meta( 'entity-content' ) ) ?>

        <?php \fcpf\eat\entity_print_tags() ?>
       
    </div>

    <div class="wp-block-column" style="flex-basis:33.33%">

        <h2 class="with-line"><?php _e( 'Contact', 'fcpfo-ea' ) ?></h2>
        
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
    
        <div class="wp-block-buttons is-content-justification-full">
            <?php \fcpf\eat\print_contact_buttons() ?>
        </div>
        
        <div class="wp-block-buttons is-content-justification-full">
            <?php \fcpf\eat\entity_print_schedule() ?>
        </div>

        <div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>

        <div class="is-content-justification-full">
            <?php \fcpf\eat\print_gmap() ?>
        </div>
    </div>
    
</div>
