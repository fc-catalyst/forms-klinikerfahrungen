<?php

class FCP_FormsTariffMail {

    private static function details($a = []) {

        if ( empty( self::$details ) ) {

            $url = get_bloginfo( 'wpurl' );

            self::$details = [
                'domain' => $_SERVER['SERVER_NAME'],
                'url' => $url,

                'sending' => 'kontakt@'.$_SERVER['SERVER_NAME'], // must be owned by smtp sender, if smtp
                'sending_name' => get_bloginfo( 'name' ),

                // ++add dynamic loading by role
                'accountant' => 'finnish.ru@gmail.com', // 'rechnungen@klinikerfahrungen.de',
                'accountant_locale' => 'de_DE',
                'moderator' => 'finnish.ru@gmail.com', // 'kontakt@klinikerfahrungen.de'
                'moderator_locale' => 'de_DE',
                'admin' => 'finnish.ru@gmail.com', // technical purposes
                'admin_locale' => 'en_US',
                'client_fake' => 'finnish.ru@gmail.com', // for testing purposes

                'footer' => '<a href="'.$url.'" target="blank" rel="noopener noreferrer">'.$_SERVER['SERVER_NAME'].'</a>',

                'issmtp' => true,
/*
                'smtp' => [
                    'Host' => '',
                    'Port' => '',
                    'SMTPSecure' => '',
                    'SMTPAuth' => true,
                    'Username' => '',
                    'Password' => '',
                    'SMTPDebug' => true,
                ],
//*/
                'WPMailSMTP' => true, // override settings with WP Mail SMTP
                'debug' => false,
            ];

            if ( self::$details['issmtp'] && self::$details['WPMailSMTP'] && $smtp_override = self::WPMailSMTP() ) {
                self::$details = array_merge( self::$details, $smtp_override );
            }

        }

        if ( empty( $a ) ) {
            return self::$details;
        }

        self::$details = array_merge( self::$details, $a );

    }

    private static $details = [],
                  $messages = [ // ++'user, 'admin'
        'accountant' => [
            'request' => [
                'Paid tariff request',
                'A paid tariff is requested. Please, bill the client and mark the status as "Billed". When the bill is payed, please remember to mark the status as "Payed" to activate the Tariff.',
                ['entity-tariff', 'billing-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'prolong' => [
                'Prolongation request',
                'A Tariff prolongation is requested. Please bill the client and mark the entity prolongation status as "Billed". When the bill is payed, please remember to mark the status as "Payed" to schedule or activate the Tariff.',
                ['entity-tariff-next', 'billing-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'change' => [
                'Tariff Change request',
                'A Tariff change is requested in terms of prolongation. Please bill the client and mark the entity prolongation status as "Billed". When the bill is payed, please remember to mark the status as "Payed" to schedule or activate the Tariff.',
                ['entity-tariff-next', 'billing-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
            'cancel' => [
                'Bill not payed',
                'The client has not payed the Bill in a set up period of time. You can now cancel the Bill, or contact the client directly.',
                ['billing-company', 'billing-address', 'billing-name', 'billing-email', 'billing-vat'],
            ],
        ],

        'client' => [
            'published' => [
                'Your entry is published',
                'Your entry has just been published.',
                ['entity-tariff'],
            ],
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
        ],
        'moderator' => [
            'entity_added' => [ // submitted for review
                'Clinic / doctor added',
                'A new clinic or doctor has just been added. Please check it and publish, if it is valid.',
                []
            ],
            'entity_updated' => [
                'Clinic / doctor changed',
                'A client has changed some information in an entry. Please check if it is still valid.',
            ],
        ]
    ];

    // collect title && meta data && billing data by a post id or array of ids; also loads the jsons with titles
    public static function get_data($ids = [], $nocached = false, $cache = false) { 
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
            self::get_structures( 'entity-tariff' );
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
            WHERE `post_id` IN (' . implode( ',', $ids ) . ')
        ');
        // AND `meta_key` IN ( "entity-tariff", "entity-tariff-next", "entity-billing" )
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
            if ( $cache && $combined[ $k ] ) { // for comparison puprose
                $combined[ $k ]['cached'] = $combined[ $k ];
            }
            $combined[ $k ]['title'] = $v;
            $combined[ $k ]['meta'] = array_merge( $metas[ $k ], $billings[ $metas[ $k ]['entity-billing'] ] );
            // metas have unique names, so who cares
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

        // the next-tariff has no options, it copies the tariff, so here is a crutch
        $titles['options'][ 'entity-tariff-next' ] = $titles['options'][ 'entity-tariff' ];

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

    // compare the __POST with older get_data and list the changed lines
    private static function message_datalist_moderator_changes($id) {

        $data = self::get_data( $id, true, true );
        
        if ( empty( $data[ $id ]['cached'] ) ) { return; }
        
        $structures = self::get_structures();
        
        $difference = [];

/*
        // this doesn't work, as the title is saved much earlier, so comparing with itself, really
        // ++can probably add an earlier number for FCP_Add_Meta_Boxes->saveMetaBoxes()
        if ( $data[ $id ]['title'] !== $data[ $id ]['cached']['title'] ) {
            $difference['entity-name'] = [
                'title' => $structures['titles']['entity-name'],
                'before' => $data[ $id ]['cached']['title'],
                'after' => $data[ $id ]['title'],
            ];
        }
//*/

        foreach ( $structures['titles'] as $k => $v ) {
            if ( $data[ $id ]['meta'][ $k ] === $data[ $id ]['cached']['meta'][ $k ] ) { continue; }
            $difference[ $k ] = [
                'title' => $v,
                'before' => $data[ $id ]['cached']['meta'][ $k ],
                'after' => $data[ $id ]['meta'][ $k ],
            ];
        }

        return $difference;

    }

    private static function message_content($recipient, $topic, $id = '') {

        $details = self::details();

        if ( !self::$messages[ $recipient ] || !self::$messages[ $recipient ][ $topic ] ) { return; }

        // translations
        $locale = $details[ $recipient . '_locale' ];
        switch_to_locale( $locale );
        load_textdomain( 'fcpfo--mail', __DIR__ . '/languages/fcpfo--mail-'.$locale.'.mo' );

        $subject = __( self::$messages[ $recipient ][ $topic ][0], 'fcpfo--mail' );
        $message = '<p>' . __( self::$messages[ $recipient ][ $topic ][1], 'fcpfo--mail' ) . '</p>';
        $footer = '<a href="'.$details['url'].'" target="blank" rel="noopener noreferrer">'.$details['domain'].'</a>';

        if ( $id ) {

            $message  = '
                '.$message.'
                <br>
                <h2>'.self::get_data( $id )[$id]['title'].'</h2>
                <a href="'.$details['url'].'/?p='.$id.'" target="_blank" rel="noopener noreferrer">'.__( 'View' ).'</a>
                |
                <a href="'.$details['url'].'/wp-admin/post.php?post='.$id.'&action=edit" target="_blank" rel="noopener noreferrer">'.__( 'Edit' ).'</a>
            ';
            
            $datalist = self::message_datalist( $id, self::$messages[ $recipient ][ $topic ][2] ); // ++translations
            $message .= $datalist ? '<br>'.$datalist : '';
        }
        
        restore_previous_locale();
        
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
            $data = self::get_data( $id )[$id];
            $meta = $data['meta'];

            if ( $meta['billing-email'] ) { $message['reply_to'] = $meta['billing-email']; }
            if ( $meta['billing-name'] ) { $message['reply_to_name'] = $meta['billing-name']; }
            
            $message['preheader'] = sprintf(
                __( 'For %s; From %s, %s.' ),
                $data['title'], $meta['billing-company'], $meta['billing-name']
            );

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

        $message['to'] = $details['client_fake'];// $meta['billing-email'];
        if ( $meta['billing-name'] ) { $message['to_name'] = $meta['billing-name']; }

        $message['reply_to'] = $details['accountant'];

        return self::send( $message );
    }
    
    public static function to_moderator($topic, $id = '') {

        if ( current_user_can( 'administrator' ) ) { return; }
    
        $difference = []; // exception to print the data changes - don't send if no changes
        if ( $topic === 'entity_updated' ) {
            $difference = self::message_datalist_moderator_changes( $id );
            if ( empty( $difference ) ) { return; }
        }

        $message = self::message_content( 'moderator', $topic, $id );
        if ( !$message ) { return; }

        $details = self::details();

        $message['from'] = $details['sending'];
        $message['from_name'] = $details['sending_name'];
        $message['to'] = $details['moderator'];
        $message['to_name'] = $details['moderator_name'];

        if ( !empty( $difference ) ) { // ++move to a separate function?
            $message['message'] .= '<h3>'.__( 'Difference' ).':</h3>';
            $message['message'] .= '<ul>';
            foreach ( $difference as $v ) {
                $message['message'] .= '<li>
                    <strong>'.$v['title'].':</strong><br>
                    '.$v['before'].' &ndash; <em>'.__( 'Before' ).'</em><br>
                    '.$v['after'].' &ndash; <em>'.__( 'After' ).'</em>
                </li>';
            }
            $message['message'] .= '</ul>';
        }

/*
        // ++add the user data to contact back just in case. will be needed later for trusted users
        //if ( $meta['billing-email'] ) { $message['reply_to'] = $meta['billing-email']; } // ++user email
        //if ( $meta['billing-name'] ) { $message['reply_to_name'] = $meta['billing-name']; } // ++user name

//*/

        return self::send( $message );
    }

    public static function to_moderator_custom($message) {

        $details = self::details();

        $message['subject'] = 'Message from ' . $message['name'];
        $message['footer'] = $details['footer'];

        $message['from'] = $details['sending'];
        $message['from_name'] = $message['name'];
        $message['to'] = $details['moderator'];
        $message['to_name'] = $details['moderator_name'];
        
        $message['reply_to'] = $message['email'];
        $message['reply_to_name'] = $message['name'];

        $message['message'] = wpautop( $message['message'] );

        return self::send( $message );
    }
    
    public static function send($m) {

        if ( !empty( array_diff( ['subject', 'message', 'from', 'to'], array_keys( $m ) ) ) ) { return; }

        static $template = '';
        
        if ( !$template ) {
            $template = file_get_contents( __DIR__ . '/mail-template.html' );
            $template = $template === false ? '<template hidden>%s %s</template><h1>%s</h1> %s <p>%s</p>' : $template;
        }

        $vsprintf = function($a, $arr) {
            $a = str_replace( ['%', '|~~|s'], ['|~~|', '%s'], $a );
            $a = vsprintf( $a, $arr );
            $a = str_replace( '|~~|', '%', $a );
            return $a;
        };

        $email_body = $vsprintf( $template, [
            $m['subject'], // title
            $m['preheader'] ? $m['preheader'] : substr( strip_tags( $m['message'] ), 0, 80 ) . '…', // preview header
            $m['subject'], // h1
            $m['message'], // the content
            $m['footer'] ? $m['footer'] : self::details()['footer'] // footer
        ]);


        if ( !class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) { // not sure it is needed and works
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        }
        if ( !class_exists( '\PHPMailer\PHPMailer\Exception', false ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->isHTML( true );
        $mail->CharSet = 'UTF-8';
        $mail->setFrom( $m['from'], $m['from_name'] );
        $mail->addAddress( $m['to'], $m['to_name'] );
        if ( $m['reply_to'] ) { $mail->addReplyTo( $m['reply_to'], $m['reply_to_name'] ); }
        $mail->Subject = $m['subject'];
        //$mail->msgHTML( $email_body );
        $mail->Body = $email_body;
        //$mail->AltBody = '';
        $mail->AddEmbeddedImage( __DIR__ . '/attachments/klinikerfahrungen-logo.png', 'klinikerfahrungen-logo');
        // $mail->addAttachment( __DIR__ . '/attachments/Fünf Tipps.pdf' );

        // a small debug
        if ( self::$details['debug'] ) {
            self::send_to_print( self::$details );
            self::send_to_print( $m );
        }
        
        // SMTP
        $details = self::details();
        if ( !$details['issmtp'] || empty( $details['smtp'] ) ) { return $mail->send(); }

        if ( !class_exists( '\PHPMailer\PHPMailer\SMTP', false ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        }

        $mail->isSMTP();
        foreach ( $details['smtp'] as $k => $v ) {
            $mail->{ $k } = $v;
        }

        if ( $details['smtp']['SMTPDebug'] ) {
            $mail->send();
            exit;
        }

        return $mail->send();

    }

    private static function WPMailSMTP() {

        $smtp = get_option( 'wp_mail_smtp' );

        if ( $smtp['mail']['mailer'] === 'smtp' ) {

            $details['smtp']['Host'] = $smtp['smtp']['host'];
            $details['smtp']['Port'] = $smtp['smtp']['port'];
            $details['smtp']['SMTPSecure'] = $smtp['smtp']['encryption'];

            if ( $smtp['smtp']['auth'] && class_exists( '\WPMailSMTP\Helpers\Crypto' ) ) {
                $details['smtp']['SMTPAuth'] = $smtp['smtp']['auth'];
                $details['smtp']['Username'] = $smtp['smtp']['user'];

                $decrypt =  new \WPMailSMTP\Helpers\Crypto;
                $details['smtp']['Password'] = $decrypt::decrypt( $smtp['smtp']['pass'] );
            }

            if ( $smtp['mail']['from_email_force'] ) {
                $details['sending'] = $smtp['mail']['from_email'];
            }

            if ( $smtp['mail']['from_name_force'] ) {
                $details['sending_name'] = $smtp['mail']['from_name'];
            }

            if ( self::$details['debug'] ) {
                $details['smtp']['SMTPDebug'] = true;
            }

            return $details;
        }

    }

    private static function send_to_print($m) { // a sort of debugging function
        if ( !current_user_can( 'administrator' ) ) { return false; }

        if ( !empty( $_POST ) ) {
            echo '<pre>';
            print_r( $m );
            echo '</pre>';
            //exit;
        }

        add_action( 'wp_footer', function() use ( $m ) {
            FCP_FormsTariffMail::console_log( $m );
        });
        add_action( 'admin_footer', function() use ( $m ) {
            FCP_FormsTariffMail::console_log( $m );
        });
        
        return true;
    }
    
    public static function console_log($m) {
    ?>
        <pre id="print_to_console" style="display:none">
        <?php print_r( $m ) ?>
        </pre>
        <script>
            console.log( jQuery( '#print_to_console' ).html() );
        </script>
    <?php
    }

}