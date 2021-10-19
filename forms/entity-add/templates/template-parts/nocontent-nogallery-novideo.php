<div class="wp-block-columns are-vertically-aligned-stretch">
    <div class="wp-block-column" style="flex-basis:66.66%">

        <h2>Über</h2>

        <?php echo fct_entity_content_filter( 
            fct1_meta( 'entity-content' ),
            fct1_meta( 'entity-tariff' )
        ) ?>

        <?php fct1_meta_print( 'entity-tags', false, '<h2>Unser Behandlungsspektrum</h2><p>', '</p>' ) ?>

        <div style="height:35px" aria-hidden="true" class="wp-block-spacer"></div>
        
        <div class="is-content-justification-full">
            <?php fct_print_gmap() ?>
        </div>

    </div>

    <div class="wp-block-column" style="flex-basis:33.33%">

        <h2 class="with-line">Kontakt</h2>
        
        <div style="height:15px" aria-hidden="true" class="wp-block-spacer"></div>
    
        <div class="wp-block-buttons is-content-justification-full">
            <?php fct_print_contact_buttons( 'entity-phone', 'Telefon' ) ?>
            <?php fct_print_contact_buttons( 'entity-email', 'E-mail' ) ?>
            <?php fct_print_contact_buttons( 'entity-website', 'Website' ) ?>
        </div>
        
        <div class="wp-block-buttons is-content-justification-full">
            <?php fct_entity_print_schedule( true ) ?>
        </div>
    </div>
    
</div>
