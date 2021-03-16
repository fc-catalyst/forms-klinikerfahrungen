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

	public static $dev = true;
	
	private function plugin_setup() {

		$this->self_url = plugins_url( '/', __FILE__ );
		$this->self_path = plugin_dir_path( __FILE__ );
		
		$this->forms_path = __DIR__ . '/forms/';

		$this->css_ver = '1.0.7' . ( self::$dev ? '.'.time() : '' );
		$this->js_ver = '1.1.0' . ( self::$dev ? '.'.time() : '' );
		$this->css_adm_ver = '0.0.1' . ( self::$dev ? '.'.time() : '' );
		$this->js_adm_ver = '0.0.1' . ( self::$dev ? '.'.time() : '' );

	}

    public function __construct() {

        $this->plugin_setup();

        add_shortcode( 'fcp-form', [ $this, 'add_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'process' ] );

    }

    public function process() {
    
        if ( !$_POST['fcp-form-name'] ) { // handle only the fcp-forms
            return;
        }

        include_once $this->self_path . 'classes/validate.class.php';

        // only correct symbols for the form name
        if ( FCP_Forms__Validate::name( true, $_POST['fcp-form-name'] ) ) {
            return;
        }

        // common wp nonce check
        if ( !wp_verify_nonce( $_POST[ 'fcp-form-' . $_POST['fcp-form-name'] ], 'fcp-form-a-nonce' ) ){
            return;
        }

        $cont = file_get_contents( $this->forms_path . $_POST['fcp-form-name'] . '/structure.json' );
        $json = json_decode( $cont, false );
        
        // get the array of errors
        $warns = new FCP_Forms__Validate( $json, $_POST, $_FILES );
        if ( !empty( $warns->result ) ) {
            $_POST['fcp-form-warning'] = 'Some fields are not filled correctly:';
            $_POST['fcp-form-warnings'] = $warns->result;
            return;
        }

        // custom validation & processing
        @include_once( $this->forms_path . $_POST['fcp-form-name'] . '/process.php' );
        if ( $warning ) {
            $_POST['fcp-form-warning'] = $warning;
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

        $this->add_styles_scripts();
        return $this->generate_form( $atts['dir'] );

	}

	private function add_styles_scripts() {

		//wp_enqueue_style( 'fcp-forms', plugins_url( 'style.css', __FILE__ ), [], $this->css_ver );
		//wp_enqueue_script( 'fcp-forms', plugins_url( 'forms.js', __FILE__ ), [], $this->js_ver, 1 );

	}
	
	private function generate_form( $dir ) {

        // custom handler
        @include_once( $this->forms_path . $dir . '/override.php' );
        if ( $override ) {
            return $override;
        }
	
        $cont = file_get_contents( $this->forms_path . $dir . '/structure.json' );
        $json = json_decode( $cont, false );
        $json->options->form_name = $dir;

        // test what we have & git push
        // then expand the amount of possible fields OR make the rest of the simple forms: upload, autofill, map, recaptcha
        // complex form with login and uploading
        // front-end validation
        // ++include the modify values file before the validator for converting numbers and resizing images, maybe, renaming files, adding smilies
        
        if ( $json->options->print_method == 'client' ) {
            return '<form class="fcp-form" data-structure="'.$dir.'">Loading..</form>';
        }

        include_once $this->self_path . 'classes/draw-fields.class.php';
        $draw = new FCP_Forms__Draw( $json, $_POST );
        return $draw->result;

	}

}

new FCP_Forms();
