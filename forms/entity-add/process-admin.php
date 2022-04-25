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


// custom workhours validation - can only have pairs open-close
$schedule_fields = [
    'entity-mo',
    'entity-tu',
    'entity-we',
    'entity-th',
    'entity-fr',
    'entity-sa',
    'entity-su',
];
foreach ( $schedule_fields as $v ) {
    if ( $_POST[ $v.'-open' ][0] && !$_POST[ $v.'-close' ][0] || $_POST[ $v.'-open' ][1] && !$_POST[ $v.'-close' ][1] ) {
        $warns->add_result( $v.'-close', __( 'Please add the closing time', 'fcpfo-ea' ) );
    }
    if ( !$_POST[ $v.'-open' ][0] && $_POST[ $v.'-close' ][0] || !$_POST[ $v.'-open' ][1] && $_POST[ $v.'-close' ][1] ) {
        $warns->add_result( $v.'-open', __( 'Please add the opening time', 'fcpfo-ea' ) );
    }
}


if ( current_user_can( 'administrator' ) ) { return; } // admins don't have to notify a moderator

require_once __DIR__ . '/../../mail/mail.php';

// notify the moderator about the new entity posted for review

/*
if ( count( $warns->result ) < 4 && $post->post_status === 'pending' ) { // ++a temporary measure for the aboves
    
    FCP_FormsTariffMail::to_moderator( 'entity_added', $postID );
    return;
}
//*/
// notify the moderator about the changes
FCP_FormsTariffMail::get_data( $postID ); // cache the meta data before saving
add_action( 'save_post', function() use ( $postID ) {
    FCP_FormsTariffMail::to_moderator( 'entity_updated', $postID ); // entity_update sends only if there are changes
}, 20 );