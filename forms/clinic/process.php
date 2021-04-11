<?php
/*
Process the form data
*/

/*
    upload to tmp
    upload to normal if user is logged in and id is provided
    fill in the hidden field
    save data to the engine
    make the template
    make the front-end for maps
//*/


if ( !empty( $_FILES ) ) {

    echo '<pre>';
    print_r( FCP_Forms__Files::tmp_dir() );
    print_r( $uploads->files );
    print_r( $uploads->tmp_upload() );
    print_r( $uploads->for_hiddens() );
    echo '</pre>';
    

    
//    exit;
    
}
//*/
