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

if ( current_user_can( 'administrator' ) ) { return; } // admin doesn't have to notify a moderator

require_once __DIR__ . '/../../mail/mail.php';

// notify the moderator about the new entity posted for review
/*
// ++better use the transition hooks
    add_action( 'draft_to_pending', 'notify_me_for_pending' );
    add_action( 'auto-draft_to_pending', 'notify_me_for_pending' );
    add_action( 'transition_post_status', 'my_post_new' );
//    https://wordpress.stackexchange.com/questions/111863/custom-function-for-submit-for-review-hook
    ++!!change to pending only if all fields are valid!!!
//*/
if ( count( $warns->result ) < 4 && $post->post_status === 'pending' ) { // ++a temporary measure for the aboves
    
    FCP_FormsTariffMail::to_moderator( 'entity_added', $postID );
    return;
}

// notify the moderator about the changes
FCP_FormsTariffMail::get_data( $postID ); // cache the meta data before saving
add_action( 'save_post', function() use ( $postID ) {
    FCP_FormsTariffMail::to_moderator( 'entity_updated', $postID ); // entity_update sends only if there are changes
}, 20 );