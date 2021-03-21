<?php

class FCP_Forms__Draw {

    private $s; // s is for structure, json; v is for values; $w is for warnings
    public $result; // contains the final form html

    public function __construct($s, $v = []) {

        if ( !empty( $v ) ) {

            // $v contains $_POST values, including newly added warnings
            $w = $v['fcp-form-warnings'];
            $s->options->warning = $v['fcp-form-warning'];

            // add the values and warnings to the existing structure
            foreach ( $s->fields as &$f ) {
                $f->savedValue = $v[ $f->name ];
                if ( $w[ $f->name ] ) {
                    $f->warning = $w[ $f->name ];
                }
            }

        }

        $this->s = $s;        
        $this->result = $this->printFields();
    
    }

    private function field_notice($a) {
        ?>
        <p><?php echo $a->text ?></p>
        <?php
    }

    private function field_text($a) {
        ?>
        <input
            type="text"
            name="<?php echo $a->name ?>"
            id="<?php echo $a->name ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            placeholder="<?php echo $a->placeholder ?>"
            value="<?php echo esc_attr( $a->savedValue ? $a->savedValue : $a->value ) ?>"
        />
        <?php
    }
    
    private function field_password($a) {
        ?>
        <input
            type="password"
            name="<?php echo $a->name ?>"
            id="<?php echo $a->name ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            placeholder="<?php echo $a->placeholder ?>"
        />
        <?php
    }
    
    private function field_hidden($a) {
        ?>
        <input
            type="hidden"
            name="<?php echo $a->name ?>"
            id="<?php echo $a->name ?>"
            value="<?php echo esc_attr( $a->value ) ?>"
        />
        <?php
    }
    
    private function field_textarea($a) {
        ?>
        <textarea
            name="<?php echo $a->name ?>"
            id="<?php echo $a->name ?>"
            rows="10" cols="50"
            placeholder="<?php echo $a->placeholder ?>"
        ><?php echo esc_textarea( $a->savedValue ? $a->savedValue : $a->value ) ?></textarea>
        <?php
    }

    private function field_checkbox($a) {
        ?>
        
        <fieldset>
        
        <?php
        $single = count( (array) $a->options ) == 1 ? true : false;
        foreach ( $a->options as $k => $b ) :
        ?>
            <label>
                <input type="checkbox"
                    name="<?php echo $a->name ?><?php echo $single ? '' : '[]' ?>"
                    value="<?php echo $k ?>"
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
            name="<?php echo $a->name ?><?php echo $a->multiple ? '[]' : '' ?>"
            id="<?php echo $a->name ?>"
            <?php echo $a->multiple ? 'multiple' : '' ?>
        >
            <?php
                foreach ( $a->options as $k => $b ) :
            ?>
                <option
                    value="<?php echo $k ?>"
                    <?php echo in_array( $k, $a->savedValue ) ? 'selected' : '' ?>
                    >
                        <?php echo $b ?>
                    </option>
            <?php
                endforeach;
            ?>
        </select>
        <?php
    }
    
    private function field_file($a) {
        ?>
        <input
            type="file"
            name="<?php echo $a->name; echo $a->multiple ? '[]' : '' ?>"
            id="<?php echo $a->name ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            <?php echo $a->multiple ? 'multiple' : '' ?>
        />
        <?php
    }

    private function field_submit($a) {
        ?>
        <input
            type="submit"
            name="<?php echo $a->name ?>"
            id="<?php echo $a->name ?>"
            <?php echo $a->size ? 'size="'.$a->size.'" style="width:auto;"' : '' ?>
            value="<?php echo esc_attr( $a->value ) ?>"
        />
        <?php
    }
    
//--------_______----____---_________-----

    private function field__wrap($a, $method) {
        
        if ( $a->type == 'hidden' ) {
            $this->{ $method }( $a );
            return;
        }
        
        ?>
        <?php echo $a->before ?>
        <div class="fcp-form-field-w">
        <?php
        
        if ( $a->title ) {
            ?>
            <span class="fcp-form-field-h"><?php echo $a->title ?></span>
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
            <div class="fcp-form-field-w"><?php echo implode( '<br>', $a->warning ) ?></div>
            <?php
        }

        ?>
        </div>
        <?php echo $a->after ?>
        <?php
    }
    
    private function printFields() {
        ob_start();
        $o = $this->s->options;
        ?>

        
        <?php echo $o->before ?>
        <form
            class="fcp-form"
            method="<?php echo $o->method ? $o->method : 'post' ?>"
            <?php echo $o->enctype ? 'enctype="'.$o->enctype.'"' : '' ?>
        >
        
        <?php
        if ( $o->warning ) {
            ?>
            <div class="fcp-form-field-w"><?php echo $o->warning ?></div>
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
        wp_nonce_field( 'fcp-form-a-nonce', 'fcp-form-' . $o->form_name );
        ?>
        
        <input type="hidden" name="fcp-form-name" value="<?php echo $o->form_name ?>">
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
        $method = 'field_' . $f->type;
        if ( !method_exists( $this, $method ) ) {
            return;
        }
        $this->field__wrap( $f, $method );
    }
    
    private function printGroup($f) {
        echo $o->before;
        ?>
        <div class="fcp-form-group fcp-form-group-<?php echo $f->gtype ?><?php echo $f->cols ? ' fcp-form-cols-'.$f->cols : '' ?>">
        <?php
            if ( $f->title || $f->description ) {
                ?>
                <div class="fcp-form-group-h">
                <?php
                if ( $f->title ) {
                    ?>
                    <<?php echo $f->title_tag ? $f->title_tag : 'h2' ?>>
                        <?php echo $f->title ?>
                    </<?php echo $f->title_tag ? $f->title_tag : 'h2' ?>>
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
            <div class="fcp-form-group-w">
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
        </div>
        <?php
        
        echo $o->after;
    }

}
