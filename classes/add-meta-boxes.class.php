<?php

class FCP_Add_Meta_Boxes {

    private $s, $p; // json structure, preferences

    public static function version() {
        return '2.0.0';
    }

    public function __construct($s, $p) {
    
        if ( !$s || !class_exists( 'FCP_Forms__Draw' ) ) {
            return;
        }

        $this->s = $s;
        $this->s->options->form_name = $p->name;
        $this->p = $p;

        add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
        add_action( 'save_post', [ $this, 'saveMetaBoxes' ] );
    }

    public function addMetaBoxes() {
        global $post;
        
        $p = $this->p;

        // get meta values
        $values0 = get_post_meta( $post->ID );
        $fields = FCP_Forms::flatten( $this->s->fields );

        foreach ( $fields as $v ) {
            $name = $p->prefix . $v->name;
            if ( !$values0[ $name ] ) {
                continue;
            }
            
            $values[ $v->name ] = $values0[ $name ][0];
            
            if ( $v->multiple || $v->options && $v->type != 'select' && count( (array) $v->options ) > 1 ) {
                $values[ $v->name ] = unserialize( $values[ $v->name ] );
            }
        }

        // print meta fields
        $draw = new FCP_Forms__Draw( $this->s, $values );

		add_meta_box(
            $p->name,
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
            !isset( $_POST[ 'fcp-form--' . $this->p->name ] ) ||
            !wp_verify_nonce( $_POST[ 'fcp-form--' . $this->p->name ], FCP_Forms::plugin_unid() )
        ) {
            return;
        }
		if ( !current_user_can( 'edit_post', $postID ) ) {
            return;
        }

        // saving from admin doesn't work
        // radio and select
        // uploads
                
            // ++ check for $_POST['fcp-form--warning'] && $_POST['fcp-form--warnings'] ??
        $is_admin = is_admin();
        $prefix = $is_admin ? '' : $this->p->prefix;
        $fields = FCP_Forms::flatten( $this->s->fields );

        
        
        // update_post_meta( $postID, 'is_admin', is_admin() ? 'ADMIN' : 'FRONTEND' );

        foreach ( $fields as $v ) {
            if ( !$v->meta_box ) { continue; }

            $name_meta = $this->p->prefix . $v->name;
            $name_post = $is_admin ? $name_meta : $v->name;

            if ( empty( $_POST[ $name_post ] ) ) {
                delete_post_meta( $postID, $name_meta );
                continue;
            }
            update_post_meta( $postID, $name_meta, $_POST[ $name_post ] );
        }

	}

	
}
