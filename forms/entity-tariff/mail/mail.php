<?php

function fcp_forms_entity_tariff_mail_by_id($recipient, $msg, $id) use ($mailing) {

    $messages[
        'accountant' => [
            // entity title, id, links to entity, billing-company, billing-address, billing-name, billing-email, billing-vat
            'request' => [
                'Paid tariff request',
                'A paid tariff is requested. Please, bill the client and mark the status as Billed. When the bill is payed, please remember to mark the status as Payed to activate the Tariff.',
            ],
            'prolong' => [
                'Prolongation request',
                'A Tariff prolongation is requested. Please bill the client and mark the entity prolongation status as Billed. When the bill is payed, please remember to mark the status as Payed to schedule or activate the Tariff.',
            ],
            'change' => [
                'Tariff Change request',
                'A Tariff change is requested in terms of prolongation. Please bill the client and mark the entity prolongation status as Billed. When the bill is payed, please remember to mark the status as Payed to schedule or activate the Tariff.',
            ],
            'cancel' => 'The client has not payed the Bill. You can cancel the Bill, or contact the client directly.',
        ],
        'client' => [
            // entity title, id, link to entity
            'activated' => [ // payed
                'New tariff is activated',
                'Your new tariff is activated.',
            ],
            'prolonged' => [ // payed
                'Your tariff was prolonged',
                'Your tariff was successfully prolonged.',
            ],
            'ending' => [
                'Your tariff is about to end',
                'Your tariff is about to end. The prolongation option is available in your Klinikerfahrungen account. Ignore this message to continue with a Free Tariff',
            ],
            'ended' => [
                'Your tariff has just ended',
                'Your tariff has just ended. Free Tariff was activated.',
            ],
            'billed' => [
                'A bill reminder',
                'You\'ve been billed recently. Please, pay the bill or ignore the emails to continue with the Free Tariff',
            ],
        ]
    ];
    
    if ( !$messages[ $recipient ][ $msg ] ) { return; }
    if ( !is_numeric( $id ) ) { return; }

    $to_accountant = $recipient === 'accountant';
    
    // datalist to send along with the message
    global $wpdb;
    $notice_datalist = [];

    // select the entity title && verify id
    $notice_datalist['title'] = $wpdb->get_var( 'SELECT `post_title` FROM `'.$wpdb->posts.'` WHERE `ID` = "'.$id.'"');
    if ( !$notice_datalist['title'] ) { return; }
    $notice_datalist['view'] = '<a href="'.$mailing['url'].'?p='.$id.'" target="_blank" rel="noopener noreferrer">'.__( 'View' ).'</a>';
    $notice_datalist['edit'] = '<a href="'.$mailing['url'].'wp-admin/post.php?post='.$id.'&action=edit" target="_blank" rel="noopener noreferrer">'.__( 'Edit' ).'</a>';

    // select the billing information
    if ( $to_accountant ) {
        $json = FCP_Forms::structure( 'billing-add' );
        if ( $json === false ) { return; }
        $json = FCP_Forms::flatten( $s->fields );
        
        $metas = $wpdb->get_results( '
            SELECT `meta_key`, `meta_value` FROM `'.$wpdb->postmeta.'`
            WHERE
                `post_id` = (
                    SELECT `meta_value`
                    FROM `'.$wpdb->postmeta.'`
                    WHERE `meta_key` = "entity-billing" AND `post_id` = "'.$id.'"
                    LIMIT 1
                )
        ');
        foreach ( $metas as $k => &$v ) {
            $metas[ $v['meta_key'] ] = $v['meta_value'];
            unset( $metas[ $k ] );
        }
        
        foreach ( $json as $v ) {
            if ( !$metas[ $v->name ] ) { continue; }
            $notice_datalist[ $v->name ] = $v->title . ': ' . $metas[ $v->name ];
        }
    }
    
    // spreading the emails
    if ( $to_accountant ) {
        $notice_datalist['from'] = $mailing['sending'];
        $notice_datalist['from_name'] = $mailing['sending_name'];
        $notice_datalist['to'] = $mailing['accountant'];
        $notice_datalist['reply_to'] = $notice_datalist['billing-email'];
        $notice_datalist['reply_to_name'] = $notice_datalist['billing-name'];
    } else {
        $notice_datalist['from'] = $mailing['sending'];
        $notice_datalist['from_name'] = $mailing['sending_name'];
        $notice_datalist['to'] = $notice_datalist['billing-email'];
        $notice_datalist['to_name'] = $notice_datalist['billing-name'];
        $notice_datalist['reply_to'] = $mailing['accountant'];
    }

    // sending (++schedule for sending later, when heavier load)
    fcp_forms_entity_tariff_email_send(
        $messages[ $recipient ][ $msg ][0],
        $messages[ $recipient ][ $msg ][1],
        '<a href="'.$mailing['url'].'" target="blank" rel="noopener noreferrer">'.$mailing['url'].'</a>',
        $notice_datalist
    );

}

function fcp_forms_entity_tariff_email_send(
    $subject,
    $content,
    $footer,
    $m
) {

    if ( !$subject || !$content ) { return; }
    if ( !$m['from'] || !$m['to'] ) { return; }
    if ( !$footer ) { $footer = ''; }

    $email_template = @file_get_contents( __DIR__ . '/mail-template.html' );

    $vsprintf = function($a, $arr) {
        $a = str_replace( ['%', '|~~|s'], ['|~~|', '%s'], $a );
        $a = vsprintf( $a, $arr );
        $a = str_replace( '|~~|', '%', $a );
        return $a;
    }

    $exceptions = ['from', 'from_name', 'to', 'to_name', 'reply_to', 'reply_to_name'];
    $content .= '<br>';
    foreach ( $m as $k => $v ) {
        if ( in_array( $k, $exceptions ) ) { continue; }
        $content .= '<br>' . $v;
    }

    $email_content = [
        $subject, // title
        $subject, // h1
        $content,
        $footer
    ];
    $email_body = $vsprintf( $email_template, $email_content );


    require( __DIR__ . '/PHPMailer/src/Exception.php' );
    require( __DIR__ . '/PHPMailer/src/PHPMailer.php' );
    use PHPMailer\PHPMailer\Exception;
    use PHPMailer\PHPMailer\PHPMailer;

    $mail = new PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->setFrom( $m['from'], $m['from_name'] );
    $mail->addAddress( $m['to'], $m['to_name'] );
    if ( $m['reply_to'] ) { $mail->addReplyTo( $m['reply_to'], $m['reply_to_name'] ); }
    $mail->Subject = $subject;
    $mail->msgHTML( $email_body );
    $mail->AddEmbeddedImage( __DIR__ . '/attachments/klinikerfahrungen-logo.png', 'klinikerfahrungen-logo');
    // $mail->addAttachment( __DIR__ . '/attachments/Fünf Tipps dein Wert deiner Amazon Marke zu erhöhen.pdf' );

    if ( $mail->send() ) { return true; }
}