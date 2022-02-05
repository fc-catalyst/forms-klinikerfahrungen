<?php

/*
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
//*/

class FCP_FormsTariffMail {

    public static $details = [],
                  $messages = [
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
            'cancel' => 'The client has not payed the Bill. You can now cancel the Bill, or contact the client directly.',
        ],
        'client' => [
            // entity title, id, link to entity
            'activated' => [ // payed
                'New tariff is activated',
                'Your new tariff is activated.',
            ],
            'prolonged' => [ // payed
                'Your tariff was prolonged',
                'Your tariff was prolonged successfully.',
            ],
            'ending' => [
                'Your tariff is about to end',
                'Your tariff is about to end. The prolongation option is available in your Klinikerfahrungen account. Ignore this message to continue with a Free Tariff',
            ],
            'ended' => [
                'Your tariff has just ended',
                'Your tariff has just ended. Free Tariff is activated.',
            ],
            'billed' => [
                'A bill reminder',
                'You\'ve been billed recently. Please, pay the bill or ignore the emails to continue with a Free Tariff',
            ],
        ]
    ];

    public static function details($a = []) {

        if ( empty( self::$details ) ) {
            $url = get_bloginfo( 'wpurl' );
            self::$details = [
                'domain' => $_SERVER['SERVER_NAME'],
                'url' => $url,
                'sending' => 'robot@'.$_SERVER['SERVER_NAME'],
                'sending_name' => get_bloginfo( 'name' ),
                'accountant' => 'finnish.ru@gmail.com', // 'buchhaltung@firmcatalyst.com',
                'admin' => 'vadim@firmcatalyst.com',
            ];
            // 'http'.( !empty( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off' ) ? 's' : '' ).'://'
        }

        if ( empty( $a ) ) {
            return self::$details;
        }

        self::$details = array_merge( self::$details, $a );

    }
    
    public static function by_id() {
    
    }

    // collect title && meta data && billing data by a post id or array of ids
    public static function get_data($ids = [], $nocached = false) { 
        static $combined = [];

        if ( empty( $ids ) ) { return $combined; }

        if ( is_numeric( $ids ) ) {
            $ids = [ $ids ];
        }

        $filter_result = function( $combined ) use ( $ids ) {
            $return = [];
            foreach ( $ids as $v ) {
                $return[ $v ] = $combined[ $v ];
            }
            return $return;
        };

        if ( empty( $combined ) ) { // cache the values from the structures
            self::get_structures( 'entity-add' );
            self::get_structures( 'billing-add' );
        }
        
        if ( !$nocached && !empty( $combined ) ) { // use cached values from $combined
            $ids_filtered = array_diff( $ids, array_keys( $combined ) );
            if ( empty( $ids_filtered ) ) {
                return $filter_result( $combined );
            }
            $ids = $ids_filtered;
        }

        global $wpdb;
        $data = [];
        
        // select titles
        $r = $wpdb->get_results( '
            SELECT `ID`, `post_title`
            FROM `'.$wpdb->posts.'`
            WHERE `ID` IN ('.implode(',',$ids).')
        ');
        $titles = [];
        foreach ( $r as $k => $v ) { $titles[ $v->ID ] = $v->post_title; }

        // select meta
        $r = $wpdb->get_results( '
            SELECT `post_id`, `meta_key`, `meta_value`
            FROM `'.$wpdb->postmeta.'`
            WHERE `post_id` IN (' . implode( ',', $ids ) . ') AND `meta_key` IN ( "entity-tariff", "entity-billing" )
        ');
        $metas = [];
        $bill_ids = [];
        foreach ( $r as $k => $v ) {
            $metas[ $v->post_id ][ $v->meta_key ] = $v->meta_value;
            if ( $v->meta_key !== 'entity-billing' ) { continue; }
            $bill_ids[] = $v->meta_value;
        }

        // select billing meta details
        $r = $wpdb->get_results( '
            SELECT `post_id`, `meta_key`, `meta_value`
            FROM `'.$wpdb->postmeta.'`
            WHERE `post_id` IN (' . implode( ',', $bill_ids ) . ')
        ');
        $billings = [];
        foreach ( $r as $k => $v ) {
            $billings[ $v->post_id ][ $v->meta_key ] = $v->meta_value;
        }

        // combine && save the results
        foreach ( $titles as $k => $v ) {
            $combined[ $k ] = [
                'title' => $v,
                'meta' => array_merge( $metas[ $k ], $billings[ $metas[ $k ]['entity-billing'] ] )//$metas[ $k ],
                //'billing' => $billings[ $metas[ $k ]['entity-billing'] ], // metas have unique names, so who cares
            ];
        }

        return $filter_result( $filter_result( $combined ) );
    }
    
    public static function get_structures($form = '') {
        static $titles = [];
    
        if ( !$form ) { return $titles; }
        
        $json = FCP_Forms::structure( $form );
        if ( $json === false ) { return; }
        $json = FCP_Forms::flatten( $json->fields );

        foreach ( $json as $v ) {
            $title = $v->title ? $v->title : $v->placeholder;
            if ( $v->name && $title ) {
                $titles['titles'][ $v->name ] = $title;
            }
            if ( $v->options ) {
                $titles['options'][ $v->name ] = $v->options;
            }
        }
        
        return $titles;
    }
/*
    public static function get_structures($form = '') { // this is version with dividing by forms
        static $titles = [];
    
        if ( !$form ) { return $titles; }
        if ( $titles[ $form ] ) { return $titles[ $form ]; }
        
        $json = FCP_Forms::structure( $form );
        if ( $json === false ) { return; }
        $json = FCP_Forms::flatten( $json->fields );

        foreach ( $json as $v ) {
            $title = $v->title ? $v->title : $v->placeholder;
            if ( $v->name && $title ) {
                $titles[ $form ]['titles'][ $v->name ] = $title;
            }
            if ( $v->options ) {
                $titles[ $form ]['options'][ $v->name ] = $v->options;
            }
        }
        
        return $titles[ $form ];
    }
//*/
    public static function message_list($ids, $structure) {
        $data = self::get_data( $ids );
        $structures = self::get_structures();
        $return = '';
        $i = $ids[0];
        foreach ( $structure as $v ) {
            $return .= '
                <li>
                '. ( $structures['titles'][ $v ] ? $structures['titles'][ $v ] : $v ) .':
                <strong>
                '.
                ( $data[ $i ]['meta'][ $v ] ?
                    ( $structures['options'][ $v ] ?
                        $structures['options'][ $v ]->{ $data[ $i ]['meta'][ $v ] }
                        : $data[ $i ]['meta'][ $v ]
                    )
                    : '-'
                )
                .'
                </strong>
                </li>
            ';
        }
        return [ $return ];
        //return [ $data, $structures ];
    }
/*
    public static function to_accountant( $topic, $ids ) {

        $title = self::$messages['accountant'][$topic][0];
        $message = self::$messages['accountant'][$topic][1];
        
        $data = self::get_data( $ids );
        $structures = self::get_structures();
        
        $message  = '
            <p>'.$message.'</p><br><br>
            <h2>'.$data['title'].'</h2>
        ';
        
        $message .= '
        <a href="'.$mailing['url'].'?p='.$id.'" target="_blank" rel="noopener noreferrer">'.__( 'View' ).'</a>
        |
        <a href="'.$mailing['url'].'wp-admin/post.php?post='.$id.'&action=edit" target="_blank" rel="noopener noreferrer">'.__( 'Edit' ).'</a><br>';
        $list = [
            'title',
            
        ]

//        $notice_datalist['from'] = $mailing['sending'];
//        $notice_datalist['from_name'] = $mailing['sending_name'];
//        $notice_datalist['to'] = $mailing['accountant'];
//        $notice_datalist['reply_to'] = $notice_datalist['billing-email'];
//        $notice_datalist['reply_to_name'] = $notice_datalist['billing-name'];

        //entity title, id, links to entity, billing-company, billing-address, billing-name, billing-email, billing-vat
        
    }
//*/
}

/*
[820] => Array
    (
        [title] => Someclinic
        [meta] => Array
            (
                [entity-billing] => 821
                [entity-tariff] => premiumeintrag
                [billing-address] => Adddress for billing1
                [billing-email] => aa@a.aa
                [_edit_lock] => 1643575074:1
                [_edit_last] => 20
            )

    )
[entity-add] => Array
    (
        [options] => Array
            (
                [entity-entity] => stdClass Object
                    (
                        [clinic] => Eintrag für Klinik
                        [doctor] => Eintrag für Arzt
                    )

                [entity-featured] => stdClass Object
                    (
                        [1] => Empfohlen
                    )
            )

        [titles] => Array
            (
                [entity-name] => Name der Klinik / Praxis
                [entity-phone] => Telefonnummer
*/



$fcp_forms_entity_tariff_mail_by_id = function($recipient, $msg, $id) use ($mailing) {

    $messages = [
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
            'cancel' => 'The client has not payed the Bill. You can now cancel the Bill, or contact the client directly.',
        ],
        'client' => [
            // entity title, id, link to entity
            'activated' => [ // payed
                'New tariff is activated',
                'Your new tariff is activated.',
            ],
            'prolonged' => [ // payed
                'Your tariff was prolonged',
                'Your tariff was prolonged successfully.',
            ],
            'ending' => [
                'Your tariff is about to end',
                'Your tariff is about to end. The prolongation option is available in your Klinikerfahrungen account. Ignore this message to continue with a Free Tariff',
            ],
            'ended' => [
                'Your tariff has just ended',
                'Your tariff has just ended. Free Tariff is activated.',
            ],
            'billed' => [
                'A bill reminder',
                'You\'ve been billed recently. Please, pay the bill or ignore the emails to continue with a Free Tariff',
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
    $notice_datalist['title'] = '<strong>'.$notice_datalist['title'].'</strong>
        <a href="'.$mailing['url'].'?p='.$id.'" target="_blank" rel="noopener noreferrer">'.__( 'View' ).'</a>
        |
        <a href="'.$mailing['url'].'wp-admin/post.php?post='.$id.'&action=edit" target="_blank" rel="noopener noreferrer">'.__( 'Edit' ).'</a><br>';

    // select the billing information
    if ( $to_accountant ) {
        $json = FCP_Forms::structure( 'billing-add' );
        if ( $json === false ) { return; }
        $json = FCP_Forms::flatten( $json->fields );

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
        foreach ( $metas as $k => $v ) {
            $metas[ $v->meta_key ] = $v->meta_value;
            unset( $metas[ $k ] );
        }
        
        foreach ( $json as $v ) {
            $v->title = $v->title ? $v->title : $v->placeholder;
            if ( !$v->meta_box || !$v->title || !$metas[ $v->name ] ) { continue; }
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

//*
    // sending (++schedule for sending later, when heavier load)
    // ++send only once feature
    fcp_forms_entity_tariff_email_send(
        $messages[ $recipient ][ $msg ][0],
        $messages[ $recipient ][ $msg ][1],
        '<a href="'.$mailing['url'].'" target="blank" rel="noopener noreferrer">'.$mailing['domain'].'</a>',
        $notice_datalist
    );
//*/
};

function fcp_forms_entity_tariff_email_send( $subject, $content, $footer, $m ) {

    if ( !$subject || !$content ) { return; }
    if ( !$m['from'] || !$m['to'] ) { return; }
    if ( !$footer ) { $footer = ''; }

    $email_template = @file_get_contents( __DIR__ . '/mail-template.html' );

    $vsprintf = function($a, $arr) {
        $a = str_replace( ['%', '|~~|s'], ['|~~|', '%s'], $a );
        $a = vsprintf( $a, $arr );
        $a = str_replace( '|~~|', '%', $a );
        return $a;
    };

    $preheader = substr( strip_tags( $content ), 0, 80 ) . '…';
   
    $exceptions = ['from', 'from_name', 'to', 'to_name', 'reply_to', 'reply_to_name'];
    $content .= '<br>';
    foreach ( $m as $k => $v ) {
        if ( in_array( $k, $exceptions ) ) { continue; }
        $content .= '<br>' . $v;
    }

    $email_content = [
        $subject, // title
        $preheader,
        $subject, // h1
        $content,
        $footer
    ];
    $email_body = $vsprintf( $email_template, $email_content );

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