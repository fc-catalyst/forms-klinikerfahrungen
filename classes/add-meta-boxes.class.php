<?php

class FCP_Add_Meta_Boxes {

    private $s, $p; // structure, preferences

    public static function version() {
        return '2.0.0';
    }

    public function __construct($s, $p) {
    
        if ( !$s || !class_exists( 'FCP_Forms__Draw' ) ) { return; }

        $this->s = $s;
        $this->p = $p;
        $this->p->prefix = FCP_Forms::prefix( $s->options->form_name );

        add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
        add_action( 'save_post', [ $this, 'saveMetaBoxes' ] );
    }

    public function addMetaBoxes() {
        global $post;
        
        $p = $this->p;

        // get meta values
        $values0 = get_post_meta( $post->ID );
        // meta names to structure names
        $fields = FCP_Forms::flatten( $this->s->fields );
        foreach ( $fields as $f ) {
            $name = $p->prefix . $f->name;
            if ( !$values0[ $name ] ) { continue; }
            
            $values[ $f->name ] = $values0[ $name ][0];
            
            if ( $f->multiple || $f->options && $f->type != 'select' && count( (array) $f->options ) > 1 ) {
                $values[ $f->name ] = unserialize( $values[ $f->name ] );
            }
        }
        
        // add warnings
        if ( $_COOKIE['fcp-form--warnings'] ) {
            foreach ( $_COOKIE['fcp-form--warnings'] as $k => $v ) {
                $values['fcp-form--warnings'][$k] = json_decode( stripslashes( $v ) );
                $values['fcp-form--warnings'][$k][] = 'The Initial value is restored';
                setcookie( 'fcp-form--warnings['.$k.']', '', time()-3600, '/' );
            }
            unset( $_COOKIE['fcp-form--warnings'] );
            
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice error my-acf-notice is-dismissible" >
                    <p>
                <?php _e( 'Some fields were not filled correctly. Please, correct the values and submit again.' ) ?>
                    </p>
                    <style>#message{display:none;}</style>
                </div>
                <?php
                // ++ disable sending the email
            } );
        }

        // print meta fields
        $draw = new FCP_Forms__Draw( $this->s, $values ); // !!must always remove prefixes of values

		add_meta_box(
            $this->s->options->form_name,
            $p->title,
            [ $draw, 'print_meta_boxes' ],
            $p->post_types,
            $p->context,
            $p->priority
        );

    }

	public function saveMetaBoxes($postID) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if (
            !isset( $_POST[ 'fcp-form--' . $this->s->options->form_name ] ) ||
            !wp_verify_nonce( $_POST[ 'fcp-form--' . $this->s->options->form_name ], FCP_Forms::plugin_unid() )
        ) {
            return;
        }
		if ( !current_user_can( 'edit_post', $postID ) ) {
            return;
        }

        // don't save wrongly formatted fields
        if ( is_admin() ) {
            if ( isset( $_FILES ) && !class_exists( 'FCP_Forms__Files' ) ) {
                include_once plugin_dir_path( __FILE__ ) . 'files.class.php';
            }
            if ( !class_exists( 'FCP_Forms__Validate' ) ) {
                include_once plugin_dir_path( __FILE__ ) . 'validate.class.php';
            }
            $warns = new FCP_Forms__Validate( $this->s, $_POST );
        }

        // update_post_meta( $postID, 'is_admin', is_admin() ? 'ADMIN' : 'FRONTEND' );

        $fields = FCP_Forms::flatten( $this->s->fields );
        foreach ( $fields as $f ) {
            if ( !$f->meta_box ) { continue; }
            if ( $warns->result[ $f->name ] ) {
                setcookie( 'fcp-form--warnings['.$f->name.']',  json_encode( $warns->result[ $f->name ] ), 0, '/' );
                continue;
            }

            $name_meta = $this->p->prefix . $f->name;
            $name_post = is_admin() ? $name_meta : $f->name;
            
            if ( empty( $_POST[ $name_post ] ) ) {
                delete_post_meta( $postID, $name_meta );
                continue;
            }
            update_post_meta( $postID, $name_meta, $_POST[ $name_post ] );
        }

	}

	
}
