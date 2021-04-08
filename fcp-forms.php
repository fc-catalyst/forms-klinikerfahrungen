<?php

/*
Plugin Name: FCP Forms Engine
Description: JSON structured forms engine with pre-made forms examples.
Version: 1.0.0
Requires at least: 4.7
Requires PHP: 7.0.0
Author: Vadim Volkov, Firmcatalyst
Author URI: https://firmcatalyst.com
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fcp-forms
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

class FCP_Forms {

	public static $dev = true, $tmp_dir = 'fcp-forms-tmp';
	
	private function plugin_setup() {

		$this->self_url  = plugins_url( '/', __FILE__ );
		$this->self_path = plugin_dir_path( __FILE__ );
		
		$this->forms_url  = $this->self_url . 'forms/';
		$this->forms_path = $this->self_path . 'forms/';

		$this->css_ver = '1.0.7' . ( self::$dev ? '.'.time() : '' );
		$this->js_ver = '1.1.0' . ( self::$dev ? '.'.time() : '' );
		$this->css_adm_ver = '0.0.1' . ( self::$dev ? '.'.time() : '' );
		$this->js_adm_ver = '0.0.1' . ( self::$dev ? '.'.time() : '' );

	}

    public function __construct() {

        $this->plugin_setup();

        add_shortcode( 'fcp-form', [ $this, 'add_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'process' ] );
        
        register_activation_hook( __FILE__, [ $this, 'install' ] );
        register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

    }
    
    public function install() {
    
        // create tmp dir for the files uploading
        $dir = wp_get_upload_dir()['basedir'];
        mkdir( $dir . '/' . self::$tmp_dir );
        mkdir( $dir . '/' . 'clients' ); // ++ TEMPORARY MEASURE - move to the form

    }
    
    public function uninstall() {
    
        // remove tmp dir
        include_once $this->self_path . 'classes/files.class.php';
        $dir = wp_get_upload_dir()['basedir'];
        FCP_Forms__Files::rm_dir( $dir . '/' . self::$tmp_dir );
    }
    
    public function process() {

        if ( !$_POST['fcp-form-name'] ) { // handle only the fcp-forms
            return;
        }

        if ( !empty( $_FILES ) ) {
            include_once $this->self_path . 'classes/files.class.php';
        }
        include_once $this->self_path . 'classes/validate.class.php';

        // only correct symbols for the form name
        if ( FCP_Forms__Validate::name( true, $_POST['fcp-form-name'] ) ) {
            return;
        }

        // common wp nonce check for logged in users
        if ( !wp_verify_nonce( $_POST[ 'fcp-form-' . $_POST['fcp-form-name'] ], 'fcp-form-a-nonce' ) ){
            return;
        }

        $cont = file_get_contents( $this->forms_path . $_POST['fcp-form-name'] . '/structure.json' );
        $json = json_decode( $cont, false );
        
        // get the array of errors
        $warns = new FCP_Forms__Validate( $json, $_POST, $_FILES );
        if ( !empty( $warns->result ) ) {
            $warning = 'Some fields are not filled correctly:';
        }

        // files processing
        if ( !empty( $_FILES ) ) {

            // dir for temporary files of current form
            if ( !self::unique( $_POST['fcp-form--tmpdir'] ) ) {
                return;
            }
            FCP_Forms__Files::tmp_clean();

            $uploads = new FCP_Forms__Files( $json, $_FILES, $warns->mFilesFailed );

/*
            if ( !empty( $uploads->result ) ) {
                $_POST['fcp-form--uploads'] = $uploads->result;
            }
//*/
        }
        

        // main & custom validation & processing
        @include_once( $this->forms_path . $_POST['fcp-form-name'] . '/process.php' );

        if ( $warning || !empty( $warns->result ) ) {
            $_POST['fcp-form--warning'] = $warning; // passing to the printing hook via globals
            $_POST['fcp-form--warnings'] = $warns->result;
            return;
        }

        // custom redirect
        if ( $redirect ) {
            wp_redirect( $redirect );
            exit;
        }

        wp_redirect( $_POST['_wp_http_referer'] ? $_POST['_wp_http_referer'] : get_permalink() );
        exit;

	}
    
	public function add_shortcode( $atts = '' ) {
		$atts = shortcode_atts( [
			'dir' => ''
		], $atts );

		if ( !$atts['dir'] || !is_file( $this->forms_path . $atts['dir'] . '/structure.json' ) ) {
			return '';
        }
        
        $this->add_styles_scripts( $atts['dir'] );
        return $this->generate_form( $atts['dir'] );

	}

	private function add_styles_scripts($dir) {

        wp_enqueue_style( 'fcp-forms', $this->self_url . 'style.css', [], $this->css_ver );
        wp_enqueue_script( 'fcp-forms', $this->self_url . '/scripts.js', ['jquery'], $this->js_ver );
	
        if ( is_file( $this->forms_path . $dir . '/style.css' ) ) {
            wp_enqueue_style(
                'fcp-forms-'.$dir,
                $this->forms_url . $dir . '/style.css',
                ['fcp-forms'],
                $this->css_ver
            );
        }
        if ( is_file( $this->forms_path . $dir . '/scripts.js' ) ) {
            wp_enqueue_script(
                'fcp-forms-'.$dir,
                $this->forms_url . $dir . '/scripts.js',
                ['jquery', 'fcp-forms'],
                $this->js_ver
            );
        }

	}
	
	private function generate_form( $dir ) {

        $cont = file_get_contents( $this->forms_path . $dir . '/structure.json' );
        $json = json_decode( $cont, false );
        $json->options->form_name = $dir;

        // custom handler
        @include_once( $this->forms_path . $dir . '/override.php' );
        if ( $override ) {
            return $override;
        }

        // install-uninstall - for every single form (creating folders, for example) OR better make on the fly!!
        // prefix to static value?
        // complex form with login and uploading
            // new user https://wp-kama.ru/function/wp_insert_user
            // hidden field for uploaded images
            // file default validation with no mentioning in json, notEmpry
        // remember about the worktime for the kliniks
        // --use something else for global warnings passing, not _POST
        // reorganize the css classes names
        // autopick + maps + report
        // register, upload, autofill, map, recaptcha
        // front-end validation
            // autofill only if the value is correct
        // ++warns to array with the source of multiple uploads OR recheck in process.php
        // ++include the modify values file before the validator for converting numbers and resizing images, maybe, renaming files, adding smilies
        // ++aria
        // ++multiple text and other fields
        // use array_map instead of circles where can?
        // aa_aa for public and aaAa for privates?
        // fcp-form-a-nonce to some semi-random ting
        // nonce goes only after init, and works only for logged in users
        // delete the form file if empty or "delete" checkbox is clicked??
        // ++if same files are uploaded via different fields - don't upload twice
        /*
            ++file not empty validation works wrong - gotta mention hiddens!!
            ++commaspace is not a good separator, as can be containd by a file
        */
        
        if ( $json->options->print_method == 'client' ) {
            return '<form class="fcp-form" data-structure="'.$dir.'">Loading..</form>';
        }

        include_once $this->self_path . 'classes/draw-fields.class.php';
        $draw = new FCP_Forms__Draw( $json, $_POST, $_FILES );
        return $draw->result;

	}
	
// -----______---___---_____HELPING FUNCITONS______---____--___


    public static function flatten($f, &$return = []) {
        foreach ( $f as $add ) {

            if ( $add->type ) {
                $return[] = $add;
                continue;
            }

            if ( $add->gtype ) {
                self::flatten( $add->fields, $return );
            }

        }
        return $return;
    }
    
    public static function unique($match = '', $length = 9) {
        $rnds = [ md5( rand() ), uniqid() ];

        $crop = array_map( function($v) use ($length) {
            return substr( $v, 0 - ceil( $length / 2 ) );
        }, $rnds );
        
        if ( $match ) {
            return preg_match( '/^[0-9a-f]{'.$length.'}$/', $match );
        }
        return substr( implode( '', $crop ), 0 - $length );
    }

}

new FCP_Forms();
