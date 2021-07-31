<?php
/*
Process meta boxes data
*/
// upload files
$dir = wp_get_upload_dir()['basedir'] . '/entity/' . $postID . '/gallery';

if ( !$uploads->upload([
    'gallery-images' => $dir
])) {
    return;
}

$_POST = $_POST + $uploads->format_for_storing();
