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
        $this->p = $p;

        add_action( 'add_meta_boxes', [ $this, 'addMetaBoxes' ] );
        add_action( 'save_post', [ $this, 'saveMetaBoxes' ] );

    }

    public function addMetaBoxes() {

        $p = $this->p;
        $draw = new FCP_Forms__Draw( $this->s );

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
		if ( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], $this->p->name.'_nonce' ) ) {
            return;
        }
		if ( !current_user_can( 'edit_post', $postID ) ) {
            return;
        }
        //save
        //upload
            //flatten first?
        //style better?
        //remove the submit button
/*
        foreach ( $this->m->structure as $b ) {
            foreach ( $b->fields as $c ) {
                $name = $this->s->prefix . $c->name;
                update_post_meta( $postID, $name, $_POST[$name] );
            }
        }
//*/
	}

	
}
