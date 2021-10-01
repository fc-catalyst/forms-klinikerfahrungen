<?php

// meta select for 

if ( !class_exists( 'FCP_Add_Meta_Boxes' ) ) {
    include_once $this->self_path . 'classes/add-meta-boxes.class.php';
}
if ( !class_exists( 'FCP_Forms__Draw' ) ) {
    include_once $this->self_path . 'classes/draw-fields.class.php';
}

$json = FCP_Forms::structure( $dir );
if ( $json === false ) { return; }


new FCP_Add_Meta_Boxes( $json, (object) [
    'title' => 'Tariff is active till',
    'post_types' => ['clinic', 'doctor'],
    'context' => 'side',
    'priority' => 'default'
] );


add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui-css', $this->self_url . 'forms/' . basename( __DIR__ ) . '/assets/jquery-ui.css' );
});

add_action( 'admin_footer', function() {
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function($){
            $( '#entity-tariff-till_entity-tariff' ).datepicker( {
                dateFormat : 'dd.mm.yy'
            });
        });
    </script>
    <?php
});
