<?php

class FCPAddMetaBoxes {

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
        $values = get_post_meta( $post->ID );
        $fields = FCP_Forms::flatten( $this->s->fields );
        $prefix = $p->prefix;

        foreach ( $fields as $v ) {
            if ( !$values[ $prefix . $v->name ] ) {
                continue;
            }
            $values[ $v->name ] = $values[ $prefix . $v->name ][0];
            unset( $values[ $prefix . $v->name ] );
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

        $fields = FCP_Forms::flatten( $this->s->fields );

        foreach ( $fields as $v ) {
            if ( empty( $_POST[ $v->name ] ) ) {
                delete_post_meta( $postID, $this->p->prefix . $v->name );
                continue;
            }
            if ( !$v->meta_box ) { // --not sure it is needed, as we have exact list of meta boxes by the same list
                continue;
            }
            update_post_meta( $postID, $this->p->prefix . $v->name, $_POST[ $v->name ] );
        }

	}

	
}
