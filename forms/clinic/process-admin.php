<?php
/*
Process meta boxes data
*/

// upload files
$dir = wp_get_upload_dir()['basedir'] . '/entity/' . $postID;

if ( !$uploads->upload([
    'entity-logo' => $dir,
    'entity-image' => $dir
])) {
    return;
}

$_POST = $_POST + $uploads->format_for_storing();

/*
INSERT INTO `wp_postmeta` ( `post_id`, `meta_key`, `meta_value` ) VALUES ( '714', 'entity-logo', 'a:1:{i:0;s:16:"99-copy-copy.jpg";}' );
INSERT INTO `wp_postmeta` ( `post_id`, `meta_key`, `meta_value` ) VALUES ( '714', 'entity-image', 'a:3:{i:0;s:6:"99.jpg";i:1;s:11:"5things.jpg";i:2;s:6:"22.jpg";}' )
*/
