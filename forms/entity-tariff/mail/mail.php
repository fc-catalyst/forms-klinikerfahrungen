<?php

//*
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
//*/

class FCP_FormsTariffMail {

    private static $details = [],
                  $messages = [ // ++'user, 'admin'
        'accountant' => [
            'request' => [
                'Paid tariff request',
                'A paid tariff is requested. Please, bill the client and mark the status as Billed. When the bill is payed, please remember to mark the status as Payed to activate the Tariff.',
                ['entity-tariff', 'billinb-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'prolong' => [
                'Prolongation request',
                'A Tariff prolongation is requested. Please bill the client and mark the entity prolongation status as Billed. When the bill is payed, please remember to mark the status as Payed to schedule or activate the Tariff.',
                ['entity-tariff-next', 'billinb-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'change' => [
                'Tariff Change request',
                'A Tariff change is requested in terms of prolongation. Please bill the client and mark the entity prolongation status as Billed. When the bill is payed, please remember to mark the status as Payed to schedule or activate the Tariff.',
                ['entity-tariff-next', 'billinb-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'cancel' => [
                'Bill not payed',
                'The client has not payed the Bill. You can now cancel the Bill, or contact the client directly.',
                ['billinb-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
        ],

        'client' => [
            'activated' => [ // payed
                'New tariff is activated',
                'Your new tariff is activated.',
                ['entity-tariff'],
            ],
            'prolonged' => [ // payed
                'Your tariff is prolonged',
                'Your tariff is prolonged successfully.',
                ['entity-tariff'],
            ],
            'ending' => [
                'Your tariff is about to end',
                'Your tariff is about to end. The prolongation option is available in your Klinikerfahrungen account. Ignore this message to continue with a Free Tariff',
                ['entity-tariff'],
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

    private static function details($a = []) {

        if ( empty( self::$details ) ) {
            $url = get_bloginfo( 'wpurl' );
            self::$details = [
                'domain' => $_SERVER['SERVER_NAME'],
                'url' => $url,
                'sending' => 'robot@'.$_SERVER['SERVER_NAME'],
                'sending_name' => get_bloginfo( 'name' ),
                'accountant' => 'finnish.ru@gmail.com', // 'buchhaltung@firmcatalyst.com',
                'admin' => 'vadim@firmcatalyst.com',
                'footer' => '<a href="'.$url.'" target="blank" rel="noopener noreferrer">'.$_SERVER['SERVER_NAME'].'</a>'
            ];
            // 'http'.( !empty( $_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off' ) ? 's' : '' ).'://'
        }

        if ( empty( $a ) ) {
            return self::$details;
        }

        self::$details = array_merge( self::$details, $a );

    }

    // collect title && meta data && billing data by a post id or array of ids; also loads the jsons with titles
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
    
    private static function get_structures($form = '') { // no division by forms, as metas are unique anyways
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

    private static function message_datalist($id, $structure = []) {
        if ( empty( $structure ) ) { return; }

        $data = self::get_data( $id );
        $structures = self::get_structures();

        $return = '';

        foreach ( $structure as $v ) {
            $title = $structures['titles'][ $v ] ? $structures['titles'][ $v ] : $v;

            $value = $data[ $id ]['meta'][ $v ] ? $data[ $id ]['meta'][ $v ] : '–';
            $value = $data[ $id ]['meta'][ $v ] &&
                     $structures['options'][ $v ] &&
                     $structures['options'][ $v ]->{ $data[ $id ]['meta'][ $v ] } ?
                $structures['options'][ $v ]->{ $data[ $id ]['meta'][ $v ] } :
                $value;

            $return .= '<li>'.$title.': <strong>'.$value.'</strong></li>';
        }

        return '<ul>' . $return . '</ul>';
    }

    private static function message_content($recipient, $topic, $id = '') {

        $details = self::details();

        if ( !self::$messages[ $recipient ] || !self::$messages[ $recipient ][ $topic ] ) { return; }
        
        $subject = self::$messages[ $recipient ][ $topic ][0];
        $message = '<p>' . self::$messages[ $recipient ][ $topic ][1] . '</p>';
        $footer = '<a href="'.$details['url'].'" target="blank" rel="noopener noreferrer">'.$details['domain'].'</a>';

        if ( $id ) {
        
            $datalist = self::message_datalist( $id, self::$messages[ $recipient ][ $topic ][2] );

            $message  = '
                '.$message.'
                <br><br>

                <h2>'.self::get_data( $id )[$id]['title'].'</h2>
                <a href="'.$details['url'].'/?p='.$id.'" target="_blank" rel="noopener noreferrer">'.__( 'View' ).'</a>
                |
                <a href="'.$details['url'].'/wp-admin/post.php?post='.$id.'&action=edit" target="_blank" rel="noopener noreferrer">'.__( 'Edit' ).'</a>
                <br>
                '.$datalist.'
            ';
        }
        
        return [
            'subject' => $subject,
            'message' => $message,
            'footer' => $footer,
        ];
    }
    
    public static function to_accountant($topic, $id = '') { // it is best to run get_data first, if multiple ids

        $message = self::message_content( 'accountant', $topic, $id );
        if ( !$message ) { return; }

        $details = self::details();

        $message['from'] = $details['sending'];
        $message['from_name'] = $details['sending_name'];
        $message['to'] = $details['accountant'];
        
        if ( $id ) {
            $meta = self::get_data( $id )[$id]['meta'];

            if ( $meta['billing-email'] ) { $message['reply_to'] = $meta['billing-email']; }
            if ( $meta['billing-name'] ) { $message['reply_to_name'] = $meta['billing-name']; }

        }
        
        return self::send( $message );
    }
    
    public static function to_client($topic, $id) { // it is best to run get_data first, if multiple ids

        $message = self::message_content( 'client', $topic, $id );
        if ( !$message ) { return; }

        $details = self::details();

        $message['from'] = $details['sending'];
        $message['from_name'] = $details['sending_name'];

        $meta = self::get_data( $id )[$id]['meta'];
        if ( !$meta['billing-email'] ) { return; }

        $message['to'] = $meta['billing-email'];
        if ( $meta['billing-name'] ) { $message['to_name'] = $meta['billing-name']; }

        $message['reply_to'] = $details['accountant'];

        return self::send( $message );
    }
    
    public static function send($m) {

        if ( !empty( array_diff( ['subject', 'message', 'from', 'to'], array_keys( $m ) ) ) ) { return; }
        
        static $template = '';
        
        if ( !$template ) {
            $template = file_get_contents( __DIR__ . '/mail-template1.html' );
            $template = $template === false ? '<p hidden>%s %s</p><h1>%s</h1> %s <p>%s</p>' : $template;
        }

        $vsprintf = function($a, $arr) {
            $a = str_replace( ['%', '|~~|s'], ['|~~|', '%s'], $a );
            $a = vsprintf( $a, $arr );
            $a = str_replace( '|~~|', '%', $a );
            return $a;
        };

        $email_body = $vsprintf( $template, [
            $m['subject'], // title
            substr( strip_tags( $m['message'] ), 0, 80 ) . '…', // preview header
            $m['subject'], // h1
            $m['message'], // the content
            $m['footer'] ? $m['footer'] : self::details()['footer'] // footer
        ]);
        
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->setFrom( $m['from'], $m['from_name'] );
        $mail->addAddress( $m['to'], $m['to_name'] );
        if ( $m['reply_to'] ) { $mail->addReplyTo( $m['reply_to'], $m['reply_to_name'] ); }
        $mail->Subject = $m['subject'];
        $mail->msgHTML( $email_body );
        $mail->AddEmbeddedImage( __DIR__ . '/attachments/klinikerfahrungen-logo.png', 'klinikerfahrungen-logo');
        // $mail->addAttachment( __DIR__ . '/attachments/Fünf Tipps dein Wert deiner Amazon Marke zu erhöhen.pdf' );

        if ( $mail->send() ) { return true; }

    }

}