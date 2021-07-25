<?php

class FCP_Forms__Draw {

    private $s; // structure
    public $result; // contains the final form html

    public function __construct($s, $v = [], $f = []) {

        $s->options->warning = $v['fcp-form--'.$s->options->form_name.'--warning'];
        
        $this->s = $s;
        $this->s->fields = $this->add_values( $s->fields, array_merge( $v, $f ) );
        $this->result = $this->printFields();

    }

    private function add_values(&$f, $v) {
        foreach ( $f as &$add ) {

            if ( $add->gtype ) {
                $this->add_values( $add->fields, $v );
                continue;
            }
        
            if ( $add->type ) {
                $add->warning = $v['fcp-form--'.$this->s->options->form_name.'--warnings'][ $add->name ];
                $add->savedValue = $v[ $add->name ];

                if ( $add->type === 'file' ) {
                    $add->uploaded_fields = $add->name.'--uploaded';
                    $add->uploaded_files = empty( $v[ $add->uploaded_fields ] ) ? [] : $v[ $add->uploaded_fields ];
                }
            }

        }
        return $f;
    }
    
    private function field_notice($a) {
        ?>
        <p><?php echo $a->text ?></p>
        <?php
    }

    private function field_text($a) {

        $value = $a->savedValue ? $a->savedValue : $a->value; //++ can unite the following to a function
        if ( !is_array( $value ) ) {
            $value = [ $value ];
        }

        foreach ( $value as $k => $v ) {

            if ( empty( $v ) && !$a->keep_empty && count( $value ) > 1 ) { // && !is_numeric( $k )
                continue;
            }

            $k = is_numeric( $k ) ? '' : $k;

        ?>
        <input
            type="text"
            name="<?php $this->e_field_name( $a->name ) ?><?php echo $a->multiple ? '['.$k.']' : '' ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            placeholder="<?php echo $a->placeholder ?><?php echo $a->placeholder && $a->validate->notEmpty ? '*' : '' ?>"
            value="<?php echo esc_attr( $v ) ?>"
            class="<?php echo $a->warning ? 'fcp-f-invalid' : '' ?>"
            <?php echo $a->autofill ? 'data-fcp-autofill="'.$a->autofill.'"' : '' ?>
        />
        <?php
        
        }
    }
    
    private function field_password($a) {
        ?>
        <input
            type="password"
            name="<?php $this->e_field_name( $a->name ) ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            placeholder="<?php echo $a->placeholder ?><?php echo $a->placeholder && $a->validate->notEmpty ? '*' : '' ?>"
            class="<?php echo $a->warning ? 'fcp-f-invalid' : '' ?>"
        />
        <?php
    }
    
    private function field_hidden($a) {
        ?>
        <input
            type="hidden"
            name="<?php $this->e_field_name( $a->name ) ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            value="<?php echo esc_attr( $a->value ) ?>"
            <?php echo $a->autofill ? 'data-fcp-autofill="'.$a->autofill.'"' : '' ?>
        />
        <?php
    }
    
    private function field_textarea($a) {
        ?>
        <textarea
            name="<?php $this->e_field_name( $a->name ) ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            rows="<?php echo $a->rows ? $a->rows : '10' ?>" cols="<?php echo $a->cols ? $a->cols : '50' ?>"
            placeholder="<?php echo $a->placeholder ?><?php echo $a->placeholder && $a->validate->notEmpty ? '*' : '' ?>"
            class="<?php echo $a->warning ? 'fcp-f-invalid' : '' ?>"
            <?php echo $a->autofill ? 'data-fcp-autofill="'.$a->autofill.'"' : '' ?>
        ><?php echo esc_textarea( $a->savedValue ? $a->savedValue : $a->value ) ?></textarea>
        <?php
    }

    private function field_checkbox($a) {
        ?>
        
        <fieldset
            id="<?php $this->e_field_id( $a->name ) ?>"
            class="
                <?php echo $a->cols ? 'fcp-form-cols-' . $a->cols : '' ?>
                <?php echo $a->warning ? 'fcp-f-invalid' : '' ?>
            "
        >
        
        <?php
        $single = count( (array) $a->options ) == 1 ? true : false;
        foreach ( $a->options as $k => $b ) :
        ?>
            <label>
                <input type="checkbox"
                    name="<?php $this->e_field_name( $a->name ) ?><?php echo $single ? '' : '[]' ?>"
                    value="<?php echo esc_attr( $k ) ?>"
                    <?php echo $single && $k == $a->savedValue || in_array( $k, $a->savedValue ) ? 'checked' : '' ?>
                >
                <span><?php echo $b ?></span>
            </label>

        <?php
        endforeach;

        ?>
        
        </fieldset>
        
        <?php
        
    }

    private function field_radio($a) {
        ?>
        
        <fieldset 
            id="<?php $this->e_field_id( $a->name ) ?>"
            class="
                <?php echo $a->cols ? 'fcp-form-cols-' . $a->cols : '' ?>
                <?php echo $a->warning ? 'fcp-f-invalid' : '' ?>
            "
        >
        
        <?php
        $single = count( (array) $a->options ) === 1 ? true : false;
        foreach ( $a->options as $k => $b ) :
            ?>
            <label>
                <input type="radio"
                    name="<?php $this->e_field_name( $a->name ) ?><?php echo $single ? '' : '[]' ?>"
                    value="<?php echo esc_attr( $k ) ?>"
                    <?php echo $single && $k == $a->savedValue || in_array( $k, $a->savedValue ) ? 'checked' : '' ?>
                >
                <span><?php echo $b ?></span>
            </label>
            <?php
        endforeach;

        ?>
        
        </fieldset>
        
        <?php
        
    }
    
    private function field_select($a) {
        ?>
        <select
            name="<?php $this->e_field_name( $a->name ) ?><?php echo $a->multiple ? '[]' : '' ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            class="<?php echo $a->warning ? 'fcp-f-invalid' : '' ?>"
            <?php echo $a->multiple ? 'multiple' : '' ?>
        >
            <?php
                if ( $a->placeholder ) {
                    ?>
                    <option value="">
                        <?php echo $a->placeholder ?><?php echo $a->validate->notEmpty ? '*' : '' ?>
                    </option>
                    <?php
                }


                foreach ( $a->options as $k => $b ) :
                    ?>
                    <option
                        value="<?php echo esc_attr( $k ) ?>"
                        <?php echo $a->multiple && in_array( $k, $a->savedValue ) || $k == $a->savedValue ? 'selected' : '' ?>
                    >
                            <?php echo $b ?>
                    </option>
                    <?php
                endforeach;
            ?>
        </select>
        <?php
    }

    private function field_datalist($a) {
        ?>
        <input
            type="text"
            name="<?php $this->e_field_name( $a->name ) ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            list="<?php $this->e_field_id( $a->name ) ?>-list"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            placeholder="<?php echo $a->placeholder ?><?php echo $a->placeholder && $a->validate->notEmpty ? '*' : '' ?>"
            value="<?php echo esc_attr( $a->savedValue ? $a->savedValue : $a->value ) ?>"
            class="<?php echo $a->warning ? 'fcp-f-invalid' : '' ?>"
        />
        <datalist id="<?php $this->e_field_id( $a->name ) ?>-list">
        <?php

            foreach ( $a->options as $b ) :
                ?>
                <option value="<?php echo esc_attr( $b ) ?>">
                <?php
            endforeach;
        ?>
        </datalist>
        <?php
    }

    private function field_file($a) {
        if ( !empty( $a->uploaded_files ) ) {
            $count = count( $a->uploaded_files );
            //$label = $count == 1 ? $a->uploaded_files[0] : $count . ' Files Uploaded';
            $label = ( $count == 1 ? '1 File' : $count . ' Files' ) . ' uploaded:';
        }
    
        ?>
        <input
            type="file"
            name="<?php $this->e_field_name( $a->name ) ?><?php echo $a->multiple ? '[]' : '' ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            class="
                <?php echo empty( $a->savedValue ) ? 'fcp-form-empty' : '' ?>
                <?php echo $a->warning ? 'fcp-f-invalid' : '' ?>
            "
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            <?php echo $a->multiple ? 'multiple' : '' ?>
            data-select-file="<?php _e( 'Select File' ) ?>"
            data-select-files="<?php _e( 'Select Files' ) ?>"
            data-files-selected="<?php _e( 'files selected' ) ?>"
        />
        <label for="<?php $this->e_field_id( $a->name ) ?>">
            <?php echo $label ? $label : 'Select File' . ( $a->multiple ? 's' : '' ) ?>
        </label>
        <?php

        if ( !empty( $a->uploaded_files ) ) {
        
            ?><fieldset><?php
            foreach ( $a->uploaded_files as $v ) :
            ?>
                <label>
                    <input type="checkbox" checked
                        name="<?php $this->e_field_name( $a->uploaded_fields ) ?>[]"
                        value="<?php echo esc_attr( $v ) ?>"
                    />
                    <span><?php echo $v ?></span>
                </label>

            <?php
            // ++ can print the images thumbnails somewhere here or inside the label
            endforeach;
            ?></fieldset><?php

        }
    }

    private function field_submit($a) {
        ?>
        <input
            type="submit"
            name="<?php $this->e_field_name( $a->name ) ?>"
            id="<?php $this->e_field_id( $a->name ) ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            value="<?php echo esc_attr( $a->value ) ?>"
        />
        <?php
    }
    
    private function field__wrap($a, $method) {
        
        $o = $this->s->options;
        
        if ( $a->type == 'hidden' ) {
            $this->{ $method }( $a );
            return;
        }
        
        ?>
        <div class="fcp-form-field-w">
        <?php echo $a->before ?>
        <?php
        
        if ( $a->title ) {
            if ( $o->reduce_font_after && is_numeric( $o->reduce_font_after ) && strlen( $a->title ) > $o->reduce_font_after ) {
                $smaller = true;
            }
            ?>
            <span class="fcp-form-field-h<?php echo $smaller ? ' fcp-form-small' : ''?>">
                <?php echo $a->title ?><?php
                    echo $a->validate->notEmpty ? ( $o->required_mark ? $o->required_mark : '*' ) : ''
                ?>
            </span>
            <?php
        }

        $this->{ $method }( $a );
        
        if ( $a->description ) {
            ?>
            <div class="fcp-form-field-d"><?php echo $a->description ?></div>
            <?php
        }

        if ( $a->warning ) {
            ?>
            <ul class="fcp-form-field-warn"><li><?php echo implode( "</li>\n<li>", $a->warning ) ?></ul>
            <?php
        }

        ?>
        <?php echo $a->after ?>
        </div>
        <?php
    }
    
    private function printFields() {
        ob_start();
        $o = $this->s->options;
        ?>

        
        <?php echo $o->before ?>
        <form
            class="fcp-form
                <?php echo $o->inherit_styles === false ? '' : 'fcp-form--main' ?>
                <?php echo 'fcp-form-' . $o->form_name ?>
                <?php echo $o->width ? 'fcp-form--' . $o->width : '' ?>
            "
            method="<?php echo $o->method ? $o->method : 'post' ?>"
            <?php echo $o->enctype ? 'enctype="'.$o->enctype.'"' : '' ?>
            <?php echo $o->autocomplete ? 'autocomplete="'.$o->autocomplete.'"' : '' ?>
        >
        
        <?php
        if ( $o->warning ) {
            ?>
            <div class="fcp-form-warning"><?php echo $o->warning ?></div>
            <?php
        }
        foreach ( $this->s->fields as $f ) {
            if ( $f->type ) { // common field print
                $this->printField( $f );
                continue;
            }
            if ( $f->gtype ) { // group of fields print
                $this->printGroup( $f );
            }
        }
        ?>

        <?php wp_nonce_field( FCP_Forms::plugin_unid(), 'fcp-form--' . $o->form_name ) ?>
        <input type="hidden" name="fcp-form-name" value="<?php echo $o->form_name ?>" />
        <input type="hidden" name="fcp-form--tmpdir"
            value="<?php echo $_POST['fcp-form--tmpdir'] ? $_POST['fcp-form--tmpdir'] : FCP_Forms::unique() ?>"
        />
        </form>
        <?php echo $o->after ?>


        <?php
        $content = ob_get_contents();
        ob_end_clean();

        $content = trim( $content );
        $content = preg_replace( '/\s+/', ' ', $content );
        $content = preg_replace( '/ </', "\n<", $content );
        return $content;
    }
    
    private function printField($f) {
        if ( is_admin() && !$f->meta_box ) { return; }
        $method = 'field_' . $f->type;
        if ( !method_exists( $this, $method ) ) { return; }
        $this->field__wrap( $f, $method );
    }
    
    private function printGroup($f) {
        ?>
        <div class="fcp-form-group
            fcp-form-group-<?php echo $f->gtype ?>
            <?php echo $f->cols ? ' fcp-form-cols-'.$f->cols : '' ?>
        ">
        <?php
            if ( $f->title || $f->description ) {
                ?>
                <div class="fcp-form-group-h">
                <?php
                if ( $f->title ) {
                    $h2 = $f->title_tag ? $f->title_tag : 'h2';
                    ?>
                    <<?php echo $h2 ?>><?php echo $f->title ?></<?php echo $h2 ?>>
                    <?php
                }

                if ( $f->description ) {
                    ?>
                    <div class="fcp-form-group-d"><?php echo $f->description ?></div>
                    <?php
                }
                ?>
                </div>
                <?php
            }
        ?>
        <?php
        if ( is_array( $f->fields ) ) {
            foreach ( $f->fields as $gf ) {
                if ( $gf->type ) { // common field print
                    $this->printField( $gf );
                    continue;
                }
                if ( $gf->gtype ) { // group of fields print
                    $this->printGroup( $gf );
                }
            }
        }
        ?>
        </div>
        <?php
    }

    private function e_field_id ($field_name) {
        echo $field_name . '_' . $this->s->options->form_name;
    }
    private function e_field_name ($field_name) {
        echo $field_name;
    }

    public function print_meta_boxes() {
        ob_start();
        $o = $this->s->options;
        
        ?><div class="fcp-form fcp-form--half"><?php
        
        foreach ( $this->s->fields as $f ) {
            if ( $f->type ) { // common field print
                $this->printField( $f );
                continue;
            }
            if ( $f->gtype ) { // group of fields print
                $this->printGroup( $f );
            }
        }
        
        wp_nonce_field( FCP_Forms::plugin_unid(), 'fcp-form--' . $o->form_name );

        ?></div><?php
        
        $content = ob_get_contents();
        ob_end_clean();

        $content = trim( $content );
        $content = preg_replace( '/\s+/', ' ', $content );
        $content = preg_replace( '/ </', "\n<", $content );
        echo $content;
    }
    
}
