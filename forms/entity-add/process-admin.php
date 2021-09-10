<?php
/*
Process meta boxes data
*/

// custom $_POST filters

// upload files
$dir = wp_get_upload_dir()['basedir'] . '/entity/' . $postID;

if ( !$uploads->upload([
    'entity-avatar' => $dir,
    'entity-photo' => $dir
])) {
    return;
}

$_POST = $_POST + $uploads->format_for_storing();
