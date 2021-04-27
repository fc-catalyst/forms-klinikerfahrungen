<?php

/*
Plugin Name: FCP Forms Engine
Description: JSON structured forms engine with pre-made forms examples.
Version: 1.0.0
Requires at least: 4.7
Requires PHP: 7.0.0
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fcp-forms
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

class FCP_Forms {

	public static $dev = true,
                  $tmp_dir = 'fcp-forms-tmp',
                  $text_domain = 'fcp-forms',
                  $prefix = 'fcpf_';
	
	private function plugin_setup() {

		$this->self_url  = plugins_url( '/', __FILE__ );
		$this->self_path = plugin_dir_path( __FILE__ );
		$this->self_path_file = __FILE__;
		
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
        register_deactivation_hook( __FILE__, [ $this, 'uninstall' ] );

        // init forms settings
        $files = array_diff( scandir( $this->forms_path ), [ '.', '..' ] );
        foreach ( $files as $file ) {
            $index = $this->forms_path . $file . '/' . 'index.php';
            if ( is_file( $index ) ) {
                include_once $index;
            }
        }

    }
    
    public function install() {
    
        // create tmp dir for the files uploading
        $dir = wp_get_upload_dir()['basedir'];
        mkdir( $dir . '/' . self::$tmp_dir );

        flush_rewrite_rules();

    }
    
    public function uninstall() {
    
        // remove tmp dir
        include_once $this->self_path . 'classes/files.class.php';
        $dir = wp_get_upload_dir()['basedir'];
        FCP_Forms__Files::rm_dir( $dir . '/' . self::$tmp_dir );
        
        flush_rewrite_rules();
    }
    
    public function process() {

        if ( !$_POST['fcp-form-name'] ) { // handle only the fcp-forms
            return;
        }

        if ( isset( $_FILES ) ) {
            include_once $this->self_path . 'classes/files.class.php';
        }
        include_once $this->self_path . 'classes/validate.class.php';

        // only correct symbols for the form name
        if ( FCP_Forms__Validate::name( true, $_POST['fcp-form-name'] ) ) {
            return;
        }

        // common wp nonce check for logged in users
        if (
            !isset( $_POST[ 'fcp-form--' . $_POST['fcp-form-name'] ] ) ||
            !wp_verify_nonce( $_POST[ 'fcp-form--' . $_POST['fcp-form-name'] ], FCP_Forms::plugin_unid() )
        ) {
            return;
        }
        
        if ( !is_file( $this->forms_path . $_POST['fcp-form-name'] . '/structure.json' ) ) {
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
        if ( isset( $_FILES ) ) {
            $uploads = new FCP_Forms__Files( $json, $_FILES, $warns->mFilesFailed );
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
        wp_enqueue_script( 'fcp-forms', $this->self_url . 'scripts.js', ['jquery'], $this->js_ver );
	
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

        // custom handler ++ can try to place it before fetching json?
        @include_once( $this->forms_path . $dir . '/override.php' );
        if ( $override ) {
            return $override;
        }
        // theme upload to git
        // check if first screen css can be picked differently
        // make the password validate simple test
        // rename classes files and delete not used?
        // saveMetaBoxes seem to work wrong for not logged in users??
        // login form, styling refactor, all the website, registration+login form, refactor back-end, front-end, payment details, ratings
        // new clinic - email immediately to review!!
        // limit the number of multiple files
        // !!get prefix not from dir name, but from some internal variable of the form - is it even used any more
        // the plugin unique value create
        // remember about the worktime for the kliniks && location
        // reorganize the css classes names
        // recaptcha
        // front-end validation
            // autofill with front-end validation
        // ++include the modify values file before the validator for converting numbers and resizing images, maybe, renaming files, adding smilies
        // ++aria
        // ++css grid variant
        // ++multiple text and other fields
        // use array_map instead of circles where can?
        // aa_aa for public and aaAa for privates?
        // fcp-form-a-nonce to some semi-random thing
            // nonce goes only after init, and works only for logged in users
        // delete the form file if empty or "delete" checkbox is clicked??
        // ++if same files are uploaded via different fields - don't upload twice
        // file default validation with no mentioning in json, notEmpry
        /*
            ++file not empty validation works wrong - gotta mention hiddens!!
            ++commaspace is not a good separator, as can be containd by a file
        */
        // prefix to static values?
        // uploading for meta boxes
        // use prefixes for meta boxes print - add this option to json modify
        // fcp-form-a-nonce to something unique
        // approve panding article: https://wordpress.stackexchange.com/questions/229840/is-it-possible-to-change-an-existing-post-status-from-pending-to-publish-via
        // use pending review, instead of private??
        // replace ", " with just comma and trim (or trim is included in sanitize)
        
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
    
    public static function plugin_unid() {
        // inlcude a file, created on the plugin install
        $tmp = '123';
        return $tmp;
    }

}

new FCP_Forms();
