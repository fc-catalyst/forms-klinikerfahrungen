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
                  $tmp_dir = 'fcp-forms-tmps',
                  $text_domain = 'fcp-forms',
                  $prefix = 'fcpf';
	
	private function plugin_setup() {

		$this->self_url  = plugins_url( '/', __FILE__ );
		$this->self_path = plugin_dir_path( __FILE__ );
		$this->self_path_file = __FILE__;

		$this->forms_url  = $this->self_url . 'forms/';
		$this->forms_path = $this->self_path . 'forms/';
		
		$this->assets = $this->self_url . 'assets/';

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

        // initial forms settings, which must have even without the form on the page
        $forms = array_diff( scandir( $this->forms_path ), [ '.', '..' ] );
        foreach ( $forms as $dir ) {
            @include_once $this->forms_path . $dir . '/' . 'index.php';
        }

        // allow js track the helpers' urls
        add_action( 'wp_head', function() {
            echo '<script>var fcp_forms_assets_url = "' . $this->assets .'";</script>'."\n";
        });
        
        // admin part
        add_action( 'admin_enqueue_scripts', [ $this, 'add_styles_scripts_admin' ] );
        
        // admin form allow uploading ++move to particular indexes
        add_action( 'post_edit_form_tag', function() {
            echo 'enctype="multipart/form-data"';
        });

    }
    
    public function install() {
    
        // create tmp dir for the files
        $dir = wp_get_upload_dir()['basedir'];
        mkdir( $dir . '/' . self::$tmp_dir );

        // create unique plugin id
        file_put_contents(
            self::plugin_unid_path(),
            '<?php return "' . md5( time() ) . '";'
        );
        
        flush_rewrite_rules();
    }
    
    public function uninstall() {
    
        // remove tmp dir
        include_once $this->self_path . 'classes/files.class.php';
        $dir = wp_get_upload_dir()['basedir'];
        FCP_Forms__Files::rm_dir( $dir . '/' . self::$tmp_dir );
        
        // remove unique plugin id
        unlink( self::plugin_unid_path() );
        
        flush_rewrite_rules();
    }
    
    public function process() { // when $_POST is passed by the client side

        if ( !$_POST['fcp-form-name'] ) { // handle only the fcp-forms
            return;
        }
        $form_name = $_POST['fcp-form-name'];
        $nonce = $_POST[ 'fcp-form--' . $form_name ];

        if ( isset( $_FILES ) ) {
            include_once $this->self_path . 'classes/files.class.php';
        }
        include_once $this->self_path . 'classes/validate.class.php';

        // only allowed symbols for the form name
        if ( FCP_Forms__Validate::name( true, $form_name ) ) {
            return;
        }

        // common wp nonce check for logged in users
        if (
            !isset( $nonce ) ||
            !wp_verify_nonce( $nonce, FCP_Forms::plugin_unid() )
        ) {
            return;
        }

        // if the form doesn't exist
        $json = self::structure( $form_name );
        if ( $json === false ) { return; }

        $warns = new FCP_Forms__Validate( $json, $_POST, $_FILES );
        
        // get the array of wrong filled fields' warnings
        if ( !empty( $warns->result ) ) {
            $warning = 'Some fields are not filled correctly:';
        }

        // prepare the list of files to process
        if ( isset( $_FILES ) ) {
            $uploads = new FCP_Forms__Files( $json, $_FILES, $warns->files_failed );
        }

        // main processing
        @include_once( $this->forms_path . $form_name . '/process.php' );

        // failure
        if ( $warning || !empty( $warns->result ) ) {
            $_POST['fcp-form--'.$form_name.'--warning'] = $warning; // passing to printing hook via globals
            $_POST['fcp-form--'.$form_name.'--warnings'] = $warns->result;
            return;
        }

        // success
        if ( $redirect ) {
            wp_redirect( $redirect );
            exit;
        }

        wp_redirect( $_POST['_wp_http_referer'] ? $_POST['_wp_http_referer'] : get_permalink() );
        exit;

	}
    
	public function add_shortcode( $atts = [] ) {

        $allowed = [
			'dir' => '',
			'ignore_hide_on_GET' => false
		];
		$atts = $this->fix_shortcode_atts( $allowed, $atts );
		$atts = shortcode_atts( $allowed, $atts );

		if ( !$atts['dir'] || !self::form_exists( $atts['dir'] ) ) {
			return '';
        }
        
        $this->add_styles_scripts( $atts['dir'] );

        return $this->generate_form( $atts );

	}
	private function fix_shortcode_atts($allowed, $atts) { // turns isset to = true and fixes the default lowercase
        foreach ( $allowed as $k => $v ) {
            $l = strtolower( $k );
            if ( $atts[ $l ] && !$atts[ $k ] ) {
                $atts[ $k ] = $atts[ $l ];
                unset( $atts[ $l ] );
                continue;
            }
            if ( in_array( $k, $atts ) ) {
                $m = array_search( $k, $atts );
                if ( is_numeric( $m ) ) {
                    $atts[ $k ] = true;
                    unset( $atts[ $m ] );
                }
            }
        }
        return $atts;
	}

	private function add_styles_scripts($dir) {

        wp_enqueue_style( 'fcp-forms-layout', $this->self_url . 'layout.css', [], $this->css_ver );
        wp_enqueue_style( 'fcp-forms-style', $this->self_url . 'style.css', ['fcp-forms-layout'], $this->css_ver );
        wp_enqueue_script( 'fcp-forms', $this->self_url . 'scripts.js', ['jquery'], $this->js_ver );

        // custom forms styling
        if ( is_file( $this->forms_path . $dir . '/style.css' ) ) {
            wp_enqueue_style(
                'fcp-forms-style-'.$dir,
                $this->forms_url . $dir . '/style.css',
                [ 'fcp-forms-layout', 'fcp-forms-style' ],
                $this->css_ver
            );
        }
        if ( is_file( $this->forms_path . $dir . '/scripts.js' ) ) {
            wp_enqueue_script(
                'fcp-forms-'.$dir,
                $this->forms_url . $dir . '/scripts.js',
                [ 'jquery', 'fcp-forms' ],
                $this->js_ver
            );
        }

	}
	
    public function add_styles_scripts_admin($hook) {

        if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( !isset( $screen ) || !is_object( $screen ) ) { // || $screen->post_type != 'clinic'
            return;
        }

        wp_enqueue_style( 'fcp-forms-layout', $this->self_url . 'layout.css', [], $this->css_ver );
        wp_enqueue_script( 'fcp-forms', $this->self_url . 'scripts.js', ['jquery'], $this->js_ver );

    }
	
	private function generate_form( $atts ) {

        $dir = $atts['dir'];
        $json = self::structure( $dir );
        if ( $json === false ) { return; }
//return isset( $_GET['add_billing'] ) . ' ' . print_r( $json->options->hide_on_GET, true ) . '***';
        // override (hide) if $_GET
        if ( isset( $json->options->hide_on_GET ) && !$atts['ignore_hide_on_GET'] ) {
            foreach ( (array) $json->options->hide_on_GET as $k => $v ) {

                if ( !is_array( $v ) ) {
                    $v = [ $v ];
                }

                // any $_GET element value --> hide
                if ( isset( $_GET[ $k ] ) && in_array( true, $v, true ) ) { return; }
                // no $_GET element with such key in the url --> hide
                if ( !isset( $_GET[ $k ] ) && in_array( false, $v, true ) ) { return; }
                // match the value to hide
                if ( !empty( $_GET[ $k ] ) && in_array( $_GET[ $k ], $v ) ) { return; }

            }
        }

        // custom handler ++ can try to place it before fetching json?
        @include_once( $this->forms_path . $dir . '/override.php' );
        if ( isset( $override ) ) {
            return $override;
        }

        if ( $json->options->print_method == 'client' ) { // ++ not ready yet
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
        static $unid;

        if ( !$unid ) {
            $unid = ( @include_once self::plugin_unid_path() );
        }

        return $unid;
    }
    public static function plugin_unid_path() {
        return plugin_dir_path( __FILE__ ) . 'fcp-forms-unid.php'; // wp_get_upload_dir()['basedir'] . '/'
    }

    public static function form_exists($dir = '') { // ++ can it be private?
        if ( !$dir ) { return false; }

        $path = plugin_dir_path( __FILE__ ) . 'forms/' . $dir . '/structure.json';
        if ( !is_file( $path ) ) { return false; }
        
        return true;
    }

    public static function structure($dir = '') {
        if ( !$dir ) { return false; }

        $path = plugin_dir_path( __FILE__ ) . 'forms/' . $dir . '/structure.json';
        if ( !is_file( $path ) ) { return false; }

        $cont = file_get_contents( $path );
        if ( $cont === false ) { return false; }
        
        $json = json_decode( $cont, false );
        if ( $json === null ) { return false; }
        
        $json->options->form_name = $dir;
        
        // ++ add prefixes here // self::$prefix.'_'
        
        return $json;
    }
    
    public static function add_options(&$json, $name, $options = [], $key = '', $value = '' ){

        if ( !$json->fields || !$name ) { return; }
        
        if ( $key !== '' && $value !== '' ) {
            foreach ( $options as $k => $v ) {
                $options[ $v['ID'] ] = $v['post_title'];
                unset( $options[ $k ] );
            }
        }

        foreach ( $json->fields as $v ) {
            if ( $v->gtype ) {
                self::add_options( $v, $name, $options, $key, $value );
                continue;
            }

            if ( !$v->type ) { continue; }
            if ( $v->name != $name ) { continue; }

            foreach ( $options as $l => $w ) {
                $v->options->{ $l } = $w;
            }
        }

    }

    // the following are used in different types of forms or fields

    public static function email_to_user($email) {
        $person = ['me', 'person', 'name'];
        $zone = substr( $email, strrpos( $email, '.' ) + 1 );
        
        if ( in_array( $zone, $person ) ) {
            $crop = substr( $email, 0, strrpos( $email, '.' ) );
            list( $n['first_name'], $n['last_name'] ) = explode( '@', $crop );
        } else {
            $crop = substr( $email, 0, strrpos( $email, '@' ) );
            list( $n['first_name'], $n['last_name'] ) = explode( '.', $crop );   
        }
        
        $n = array_map( 'ucfirst', $n );
        $n['display_name'] = $n['first_name'] . ' ' . $n['last_name'];
        $n['user_login'] = sanitize_user( $n['first_name'] . $n['last_name'], true );
        
        $n['user_email'] = $email;
        
        // check if user login exists && create a new one
        require_once ABSPATH . WPINC . '/user.php';
        $init_login = $n['user_login'];
        $counter = 2;
        while( username_exists( $n['user_login'] ) ) {
            $n['user_login'] = $init_login . $counter;
            $counter++;
        }
        
        return $n;

    }
    
    public static function check_role($role, $user = []) {
        if( empty( $user ) ) {
            $user = wp_get_current_user();
        }

        if( empty( $user ) || !in_array( $role, (array) $user->roles ) ) {
            return false;
        }

        return true;
    }
    
    public static function role_allow($a = []) {
        return !empty( array_intersect( self::roles_get(), $a ) );
    }
    private static function roles_get() {
        static $roles = [];
        if ( empty( $roles ) ) {
            $roles = get_userdata( get_current_user_id() )->roles;
        }
        return $roles;
    }
    
}

new FCP_Forms();

/*
    if the form is only for admin - unify the shortcode overrides
    FCP_Add_Meta_Boxes get the title from json
    refactor delegate register styles
    redirect to the editor works strange for admins - test for delegates
    filter multiple fields empty values, as schedule fills in too many rows
    add meta boxes automatically, if are mentioned in the structure (now in forms' index.php)
    add_styles_scripts_admin - a all mentioned post types
    exclude dirs, starting from -- - take from gutenberg
    add default checkboxes & radios checked
    !! check is_admin in saveMetaBoxes !!
    clinic meta boxes to proper ones
        just process the common process in that function, just silengly
        dunno about files - find out

        company-image
        if on edit post submit or if on just clinic add
        ADD THE FREE PASS TRIGGER IF VALIDATION IS DONE CORRECTLY NOT ON ADMIN SIDE
        front-end validation visible
        maybe learn how meta boxes gotta be added not semi-manually

    warnings are not attached to particular form as well as other fields, which names might repeat on other forms on the page
    files uploading add checkboxes to delete files on submit (as well as thumbnails)
    clinic meta boxes - change title and slug on meta title change.. or remove title from meta
    check if first screen css can be picked differently
    make the password validate simple test
    rename classes files and delete not used?
    saveMetaBoxes seem to work wrong for not logged in users??
    login form, styling refactor, all the website, registration+login form, refactor back-end, front-end, payment details, ratings
    new clinic - email immediately to review!!
    limit the number of multiple files
    the plugin unique value create
    remember about the worktime for the kliniks && location
    reorganize the css classes names
    recaptcha
    front-end validation
        autofill with front-end validation
    ++include the modify values file before the validator for converting numbers and resizing images, maybe, renaming files, adding smilies
    ++aria
    ++css grid variant
    ++multiple text and other fields
    use array_map instead of circles where can?
    aa_aa for public and aaAa for privates?
    fcp-form-a-nonce to some semi-random thing
        nonce goes only after init, and works only for logged in users
    delete the form file if empty or "delete" checkbox is clicked??
    ++if same files are uploaded via different fields - don't upload twice
    file default validation with no mentioning in json, notEmpry

        ++file not empty validation works wrong - gotta mention hiddens!!
        ++commaspace is not a good separator, as can be containd by a file

    uploading for meta boxes
    fcp-form-a-nonce to something unique
    approve panding article: https://wordpress.stackexchange.com/questions/229840/is-it-possible-to-change-an-existing-post-status-from-pending-to-publish-via
    use pending review, instead of private??
    replace ", " with just comma and trim (or trim is included in sanitize)
    on clear trash - remove the clinic logo dir
    img preview to admin (mk thumbnail??)
//*/
