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
