<?php

    defined( 'WPINC' ) || die;

    if ( ! class_exists( 'WCFF_Metabox' ) ):

        class WCFF_Metabox {
            private $id;
            private $prefix;
            private $title;
            private $post_types;
            private $priority;
            private $context;
            private $fields;
            private $field_types;
            private $post_id;


            public function __construct( $id, $title, $post_types, $fields = array(), $prefix = null, $priority = 'high', $context = 'normal' ) {

                if ( ! is_array( $post_types ) ) {
                    $post_types = array( $post_types );
                }

                if ( ! is_array( $fields ) ) {
                    $this->fields = array();
                }

                $this->field_types = array();
                $this->post_types  = $post_types;
                $this->id          = $id;
                $this->title       = $title;
                $this->priority    = $priority;
                $this->context     = $context;
                $this->post_id     = null;

                if ( empty( $prefix ) ) {
                    $prefix = str_replace( array( ' ', '-' ), array( '_', '_' ), strtolower( esc_attr( $this->getId() ) ) );
                }

                $this->prefix = $prefix;
                $this->setField( $fields, true );

                WCFF_PluginManager::registerAction( $this, 'add_meta_boxes', 'add' );
                WCFF_PluginManager::registerAction( $this, 'admin_enqueue_scripts', 'enqueueAssets' );
                WCFF_PluginManager::registerAction( $this, 'save_post', 'initSave' );
            }

            public function render() {
                // Use nonce for verification
                $prefix = $this->getPrefix();
                $fields = $this->getFields();

                if ( empty( $fields ) ) {
                    return;
                }

                wp_nonce_field( $prefix . '_nonce_action', $prefix . '_nonce_field' );

                // Begin the field table and loop
                echo '<table class="form-table meta_box">';

                foreach ( $fields as $field ) {
                    if ( $field[ 'type' ] == 'section' ) {
                        echo '<tr><th colspan="2"><h2>' . $field[ 'label' ] . '</h2></th></tr>';
                    }
                    else {
                        echo '<tr><th style="width:20%"><label for="' . $field[ 'id' ] . '">' . $field[ 'label' ] . '</label></th><td>';

                        // Todo: beide zeilen anpassen
                        $meta = get_post_meta( get_the_ID(), $field[ 'id' ], true );
                        echo $this->renderFieldElement( $field, $meta );

                        echo '</td></tr>';
                    }
                }

                echo '</table>';
            }

            public function addPostType( $post_types ) {
                if ( empty( $post_types ) ) {
                    return false;
                }

                if ( is_array( $post_types ) ) {
                    $this->post_types = array_merge( $post_types, $this->getPostTypes() );
                }
                else {
                    $this->post_types[] = $post_types;
                }

                return $this;
            }

            public function add() {
                $post_types = $this->getPostTypes();

                if ( empty( $post_types ) ) {
                    return false;
                }

                foreach ( $post_types as $post_type ) {
                    add_meta_box( $this->getId(), $this->getTitle(), array( $this, 'render' ), $post_type, $this->getContext(), $this->getPriority() );
                }
            }

            /**
             * Set the form fields of the metabox.
             *
             * @param array $field     Form fields
             * @param bool  $overwrite True if fields should be completly overwritten. Otherwise it will be appended to the existing fields. (Default: false)
             *
             * @return WCFF_Metabox|bool False if something went wrong, otherwise this object.
             */
            public function setField( $field, $overwrite = false ) {
                if ( empty( $field ) || ! is_array( $field ) ) {
                    return false;
                }

                // Todo: Set field into a specific position in the array.

                if ( $overwrite ) {
                    $this->fields = $field;
                }
                else {
                    $this->fields[] = $field;
                }

                $this->field_types = wcff_array_column_recursive( $this->getFields(), 'type' );

                return $this;
            }

            public function getFields() {
                return $this->fields;
            }

            public function getPrefix() {
                return $this->prefix;
            }

            public function getPriority() {
                return $this->priority;
            }

            public function getContext() {
                return $this->context;
            }

            public function getTitle() {
                return $this->title;
            }

            public function getPostTypes() {
                return $this->post_types;
            }

            public function getId() {
                return $this->id;
            }

            public function getPostId() {
                return $this->post_id;
            }

            /**
             * Get all types of fields used in this metabox.
             *
             * @return array List of types.
             */
            public function getFieldTypes() {
                return $this->field_types;
            }

            public function initSave( $post_id ) {
                $this->post_id = $post_id;
                $prefix        = $this->getPrefix();

                // verify nonce
                if ( ! isset( $_POST[ $prefix . '_nonce_field' ] ) || ! ( in_array( get_post_type(), $this->page ) || wp_verify_nonce( $_POST[ $prefix . '_nonce_field' ], $prefix . '_nonce_action' ) ) ) {
                    return $post_id;
                }

                // check autosave
                if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                    return $post_id;
                }

                // check permissions
                if ( ! current_user_can( 'edit_page', $post_id ) ) {
                    return $post_id;
                }

                $this->save( $this->getPostId() );
            }

            public function save( $post_id ) {
                // loop through fields and save the data
                foreach ( $this->fields as $field ) {

                    // section has no data, continue.
                    if ( $field[ 'type' ] == 'section' ) {
                        $sanitizer = null;
                        continue;
                    }

                    // save taxonomies
                    if ( in_array( $field[ 'type' ], array( 'tax_select', 'tax_chosen', 'tax_checkboxes' ) ) && isset( $_POST[ $field[ 'id' ] ] ) ) {
                        $term = $_POST[ $field[ 'id' ] ];
                        wp_set_object_terms( $post_id, $term, $field[ 'id' ] );
                        continue;
                    }

                    // save the rest
                    $new = false;
                    $old = get_post_meta( $post_id, $field[ 'id' ], true );

                    if ( isset( $_POST[ $field[ 'id' ] ] ) ) {
                        $new = $_POST[ $field[ 'id' ] ];
                    }

                    if ( isset( $new ) && '' == $new && $old ) {
                        delete_post_meta( $post_id, $field[ 'id' ], $old );
                    }
                    elseif ( isset( $new ) && $new != $old ) {
                        $sanitizer = isset( $field[ 'sanitizer' ] ) ? $field[ 'sanitizer' ] : 'sanitize_text_field';

                        if ( is_array( $new ) ) {
                            $new = wcff_array_map_r( 'wcff_sanitize', $new, $sanitizer );
                        }
                        else {
                            $new = wcff_sanitize( $new, $sanitizer );
                        }

                        update_post_meta( $post_id, $field[ 'id' ], $new );
                    }
                } // end foreach
            }

            /**
             * Enqueue scripts that are manditory to run the metabox HTML elements.
             *
             * @since 1.0
             * @global string $pagenow Filename of current page.
             */
            public function enqueueAssets() {
                global $pagenow;

                if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) && ! in_array( get_post_type(), $this->getPostTypes() ) ) {
                    return;
                }

                $field_types = $this->getFieldTypes();

                // js
                $deps = array( 'jquery' );

                if ( in_array( 'date', $field_types ) ) {
                    $deps[] = 'jquery-ui-datepicker';
                }

                if ( in_array( 'slider', $field_types ) ) {
                    $deps[] = 'jquery-ui-slider';
                }

                if ( in_array( 'color', $field_types ) ) {
                    $deps[] = 'farbtastic';
                }

                if ( in_array( 'chosen', $field_types ) || in_array( 'post_chosen', $field_types ) || in_array( 'tax_chosen', $field_types ) ) {
                    $deps[] = 'chosen';

                    WCFF_Assets::registerScript( 'chosen', 'chosen.js', array( 'jquery' ) );
                    WCFF_Assets::enqueueStyle( 'chosen', 'chosen.css' );
                }

                if ( in_array( 'datetime', $field_types ) || in_array( 'time', $field_types ) ) {
                    $deps[] = 'jquery-ui-timepicker';

                    WCFF_Assets::registerScript( 'jquery-ui-timepicker', 'jquery-ui-timepicker-addon.js', array( 'jquery' ) );
                    WCFF_Assets::enqueueStyle( 'jquery-ui-timepicker', 'jquery-ui-timepicker-addon.css' );
                }

                if ( in_array( 'slider', $field_types ) || in_array( 'color', $field_types ) || in_array( 'chosen', $field_types ) || in_array( 'post_chosen', $field_types ) || in_array( 'repeatable', $field_types ) || in_array( 'image', $field_types ) || in_array( 'file', $field_types ) ) {
                    WCFF_Assets::enqueueScript( WCFF_TEXT_DOMAIN . '_admin_metabox', 'admin_metabox.js', $deps );
                }

                if ( in_array( 'image', $field_types ) || in_array( 'file', $field_types ) ) {
                    wp_localize_script( WCFF_TEXT_DOMAIN . '_admin_metabox', 'wcff_metabox_admin', array(
                        'txt' => array(
                            'imgFrameTitle'  => esc_html_x( 'Choose Image', 'Image Popup Title', WCFF_TEXT_DOMAIN ),
                            'imgFrameBtn'    => esc_html_x( 'Use This Image', 'Image Popup Button', WCFF_TEXT_DOMAIN ),
                            'fileFrameTitle' => esc_html_x( 'Choose File', 'File Popup Title', WCFF_TEXT_DOMAIN ),
                            'fileFrameBtn'   => esc_html_x( 'Use This File', 'File Popup Button', WCFF_TEXT_DOMAIN ),
                        ),
                    ) );
                }

                // css
                $deps = array();

                // Todo: Austauschen gegen native WP
                WCFF_Assets::registerStyle( 'jqueryui', 'jqueryui.css' );

                if ( in_array( 'date', $field_types ) || in_array( 'slider', $field_types ) ) {
                    $deps[] = 'jqueryui';
                }

                if ( in_array( 'color', $field_types ) ) {
                    $deps[] = 'farbtastic';
                }

                WCFF_Assets::enqueueStyle( WCFF_TEXT_DOMAIN . '_admin_metabox', 'admin_metabox.css', $deps );
            }

            /**
             * Recives data about a form field and echo the proper html
             *
             * @param    array                 $field      array with various bits of information about the field
             * @param    string|int|bool|array $meta       the saved data for this field
             * @param    array                 $repeatable if is this for a repeatable field, contains parant id and the current integar
             *
             * @return    string                     html for the field
             */
            private function renderFieldElement( $field, $meta = null, $repeatable = null ) {
                if ( ! ( $field || is_array( $field ) ) ) {
                    return;
                }

                // get field data
                $type              = isset( $field[ 'type' ] ) ? $field[ 'type' ] : null;
                $desc              = isset( $field[ 'desc' ] ) ? '<span class="description">' . $field[ 'desc' ] . '</span>' : null;
                $post_type         = isset( $field[ 'post_type' ] ) ? $field[ 'post_type' ] : null;
                $options           = isset( $field[ 'options' ] ) ? $field[ 'options' ] : null;
                $settings          = isset( $field[ 'settings' ] ) ? $field[ 'settings' ] : null;
                $multiple          = isset( $field[ 'multiple' ] ) ? $field[ 'multiple' ] : null;
                $repeatable_fields = isset( $field[ 'repeatable_fields' ] ) ? $field[ 'repeatable_fields' ] : null;
                $id                = isset( $field[ 'id' ] ) ? strtolower( $field[ 'id' ] ) : null;
                $name              = isset( $field[ 'name' ] ) ? strtolower( $field[ 'name' ] ) : $id;
                $readonly          = isset( $field[ 'readonly' ] ) ? $field[ 'readonly' ] : null;

                // the id and name for each field
                if ( $repeatable ) {
                    $name = $repeatable[ 0 ] . '[' . $repeatable[ 1 ] . '][' . $id . ']';
                    $id   = $repeatable[ 0 ] . '_' . $repeatable[ 1 ] . '_' . $id;
                }

                switch ( $type ) {
                    // basic
                    case 'text':
                    case 'tel':
                    case 'email':
                    case 'url':
                    case 'number':
                        echo '<input type="' . $type . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . esc_attr( $meta ) . '" class="regular-text" ' . ( $readonly ? 'readonly="readonly"' : '' ) . '/><br />' . $desc;
                        break;

                    // checkbox
                    case 'checkbox':
                        echo '<input type="checkbox" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" ' . checked( $meta, true, false ) . ' value="1" /><label for="' . esc_attr( $id ) . '">' . $desc . '</label>';
                        break;

                    // textarea
                    case 'textarea':
                        echo '<textarea name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" cols="60" rows="4">' . esc_textarea( $meta ) . '</textarea><br />' . $desc;
                        break;

                    // editor
                    case 'editor':
                        echo wp_editor( $meta, $id, $settings ) . '<br />' . $desc;
                        break;

                    // select, chosen
                    case 'select':
                    case 'chosen':
                        echo '<select data-placeholder="' . esc_html__( '— Please select —', WCFF_TEXT_DOMAIN ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '"' . ( $type == 'chosen' ? ' class="chosen"' : '' ) . ( $multiple ? ' multiple="multiple"' : '' ) . '>';

                        if ( ! $multiple ) {
                            echo '<option value="">' . esc_html__( '— Please select —', WCFF_TEXT_DOMAIN ) . '</option>';
                        }

                        foreach ( $options as $option ) {
                            echo '<option ' . selected( $meta, $option[ 'value' ], false ) . ' value="' . esc_attr( $option[ 'value' ] ) . '">' . esc_html( $option[ 'label' ] ) . '</option>';
                        }

                        echo '</select><br />' . $desc;
                        break;

                    // radio
                    case 'radio':
                        echo '<ul class="meta_box_items">';

                        foreach ( $options as $option ) {
                            echo '<li><input type="radio" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '-' . esc_attr( $option[ 'value' ] ) . '" value="' . $option[ 'value' ] . '" ' . checked( $meta, $option[ 'value' ], false ) . ' />
						<label for="' . esc_attr( $id ) . '-' . $option[ 'value' ] . '">' . $option[ 'label' ] . '</label></li>';
                        }

                        echo '</ul>' . $desc;
                        break;

                    // checkbox_group
                    case 'checkbox_group':
                        echo '<ul class="meta_box_items">';

                        foreach ( $options as $option ) {
                            echo '<li><input type="checkbox" value="' . $option[ 'value' ] . '" name="' . esc_attr( $name ) . '[]" id="' . esc_attr( $id ) . '-' . esc_attr( $option[ 'value' ] ) . '"' . ( is_array( $meta ) && in_array( $option[ 'value' ], $meta ) ? ' checked="checked"' : '' ) . ' /> 
						<label for="' . esc_attr( $id ) . '-' . $option[ 'value' ] . '">' . $option[ 'label' ] . '</label></li>';
                        }

                        echo '</ul>' . $desc;
                        break;

                    // color
                    case 'color':
                        $meta = $meta ? $meta : '#';
                        echo '<input type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . $meta . '" size="10" ' . ( $readonly ? 'readonly="readonly"' : '' ) . '/><br />' . $desc;
                        echo '<div id="colorpicker-' . esc_attr( $id ) . '"></div>
							<script type="text/javascript">jQuery(function(jQuery) {
							    var el_id = "' . esc_attr( $id ) . '", $colorpicker = jQuery("#colorpicker-" + el_id);
								$colorpicker.hide().farbtastic("#" + el_id);
								jQuery("#" + el_id).bind("blur", function() { $colorpicker.slideToggle(); } )
												   .bind("focus", function() { $colorpicker.slideToggle(); } );
							});</script>';
                        break;

                    // post_select, post_chosen
                    case 'post_select':
                    case 'post_chosen':
                        echo '<select data-placeholder="' . esc_html__( '— Please select —', WCFF_TEXT_DOMAIN ) . '" name="' . esc_attr( $name ) . '[]" id="' . esc_attr( $id ) . '"' . ( $type == 'post_chosen' ? ' class="chosen"' : '' ) . ( isset( $multiple ) && $multiple == true ? ' multiple="multiple"' : '' ) . '>
					<option value=""></option>'; // Select One

                        $posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'orderby' => 'name', 'order' => 'ASC' ) );
                        foreach ( $posts as $item ) {
                            echo '<option value="' . $item->ID . '"' . selected( is_array( $meta ) && in_array( $item->ID, $meta ), true, false ) . '>' . $item->post_title . '</option>';
                        }

                        $post_type_object = get_post_type_object( $post_type );

                        echo '</select> &nbsp;<span class="description"><a href="' . admin_url( 'edit.php?post_type=' . $post_type ) . '" target="_blank">' . sprintf( esc_html_x( 'Manage %s', 'Link to manage the post type' ), $post_type_object->label ) . '</a></span><br />' . $desc;
                        break;

                    // post_checkboxes
                    case 'post_checkboxes':
                        $posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'orderby' => 'name', 'order' => 'ASC' ) );

                        echo '<ul class="meta_box_items">';

                        foreach ( $posts as $item ) {
                            echo '<li><input type="checkbox" value="' . $item->ID . '" name="' . esc_attr( $name ) . '[]" id="' . esc_attr( $id ) . '-' . $item->ID . '"' . ( is_array( $meta ) && in_array( $item->ID, $meta ) ? ' checked="checked"' : '' ) . ' />
						<label for="' . esc_attr( $id ) . '-' . $item->ID . '">' . $item->post_title . '</label></li>';
                        }

                        $post_type_object = get_post_type_object( $post_type );

                        echo '</ul> ' . $desc, ' &nbsp;<span class="description"><a href="' . admin_url( 'edit.php?post_type=' . $post_type ) . '" target="_blank">' . sprintf( esc_html_x( 'Manage %s', 'Link to manage the post type' ), $post_type_object->label ) . '</a></span>';
                        break;

                    // tax_select
                    case 'tax_select':
                    case 'tax_chosen':
                        echo '<select data-placeholder="' . esc_html__( '— Please select —', WCFF_TEXT_DOMAIN ) . '" name="' . esc_attr( $name ) . '[]" id="' . esc_attr( $id ) . '"' . ( $type == 'tax_chosen' ? ' class="chosen"' : '' ) . ( isset( $multiple ) && $multiple == true ? ' multiple="multiple"' : '' ) . '>
							<option value=""></option>'; // Select One

                        $terms      = get_terms( $id, 'get=all' );
                        $post_terms = wp_get_object_terms( get_the_ID(), $id );
                        $taxonomy   = get_taxonomy( $id );
                        $selected   = $post_terms ? ( $taxonomy->hierarchical ? $post_terms[ 0 ]->term_id : $post_terms[ 0 ]->slug ) : null;

                        foreach ( $terms as $term ) {
                            $term_value = $taxonomy->hierarchical ? $term->term_id : $term->slug;
                            echo '<option value="' . $term_value . '"' . selected( $selected, $term_value, false ) . '>' . $term->name . '</option>';
                        }

                        echo '</select> &nbsp;<span class="description"><a href="' . admin_url( 'edit-tags.php?taxonomy=' . $id ) . '">' . sprintf( esc_html_x( 'Manage %s', 'Link to manage the post type' ), $taxonomy->label ) . '</a></span><br />' . $desc;
                        break;

                    // tax_checkboxes
                    case 'tax_checkboxes':
                        $terms      = get_terms( $id, 'get=all' );
                        $post_terms = wp_get_object_terms( get_the_ID(), $id );
                        $taxonomy   = get_taxonomy( $id );
                        $checked    = $post_terms ? $taxonomy->hierarchical ? $post_terms[ 0 ]->term_id : $post_terms[ 0 ]->slug : null;

                        echo '<ul class="meta_box_items">';

                        foreach ( $terms as $term ) {
                            $term_value = $taxonomy->hierarchical ? $term->term_id : $term->slug;
                            echo '<li><input type="checkbox" value="' . $term_value . '" name="' . $id . '[]" id="term-' . $term_value . '"' . checked( $checked, $term_value, false ) . ' /> <label for="term-' . $term_value . '">' . $term->name . '</label></li>';
                        }

                        echo '</ul>' . $desc . ' &nbsp;<span class="description"><a href="' . admin_url( 'edit-tags.php?taxonomy=' . $id ) . '">' . sprintf( esc_html_x( 'Manage %s', 'Link to manage the post type' ), $taxonomy->label ) . '</a></span><br />';
                        break;

                    // date
                    case 'date':
                        echo '<input type="text" class="datepicker" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . $meta . '" ' . ( $readonly ? 'readonly="readonly"' : '' ) . '/><br />' . $desc;

                        $datepicker = array();

                        if ( ! empty( $field[ 'options' ] ) ) {
                            $datepicker = $field[ 'options' ];
                        }

                        echo '<script type="text/javascript">jQuery(function($) { $("#' . esc_attr( $id ) . '").datepicker(' . wp_json_encode( $datepicker ) . '); });</script>';
                        break;

                    case 'datetime':
                    case 'time':
                        echo '<input type="text" class="datepicker" name="' . esc_attr( $name ) . '" id="' . esc_attr( $id ) . '" value="' . $meta . '" ' . ( $readonly ? 'readonly="readonly"' : '' ) . '/><br />' . $desc;

                        $timepicker = array();

                        if ( ! empty( $field[ 'options' ] ) ) {
                            $timepicker = $field[ 'options' ];
                        }

                        $js_method = 'datetimepicker';

                        if ( $type == 'time' ) {
                            $js_method = 'timepicker';
                        }

                        echo '<script type="text/javascript">jQuery(function($) { $("#' . esc_attr( $id ) . '").' . $js_method . '(' . wp_json_encode( $timepicker ) . '); } );</script> ';
                        break;

                    // image
                    case 'image':
                        $image = '';

                        if ( $meta ) {
                            $image = wp_get_attachment_image_src( intval( $meta ), 'medium' );
                            $image = $image[ 0 ];
                        }

                        echo '<div class="meta_box_image">
					      <input name="' . esc_attr( $name ) . '" type="hidden" class="meta_box_upload_image" value="' . intval( $meta ) . '" />
						  <img src="' . esc_attr( $image ) . '" class="meta_box_preview_image" />
						  <a href="#" class="meta_box_upload_image_button button" rel="' . get_the_ID() . '">' . esc_html_x( 'Choose Image', 'Metabox Button to choose image' ) . '</a>
						  <small>&nbsp;<a href="#" class="meta_box_clear_image_button">' . esc_html_x( 'Remove Image', 'Metabox Button to remove image' ) . '</a></small></div>
						  <br clear="all" />' . $desc;
                        break;

                    // file
                    case 'file':
                        $iconClass = 'meta_box_file';

                        if ( $meta ) {
                            $iconClass .= ' checked';
                        }

                        echo '<div class="meta_box_file_stuff" ><input name="' . esc_attr( $name ) . '" type="hidden" class="meta_box_upload_file" value="' . esc_url( $meta ) . '" />
						  <span class="' . $iconClass . '" ></span >
						  <span class="meta_box_filename" > ' . esc_url( $meta ) . '</span >
						  <a href="#" class="meta_box_upload_file_button button" rel="' . get_the_ID() . '" >' . esc_html_x( 'Choose File', 'Metabox Button to choose file' ) . '</a >
					      <small>&nbsp;<a href="#" class="meta_box_clear_file_button" >' . esc_html_x( 'Remove File', 'Metabox Button to remove file' ) . '</a></small></div>
						  <br clear ="all" />' . $desc;
                        break;

                    case 'repeatable':
                        echo '<table id="' . esc_attr( $id ) . '-repeatable" class="meta_box_repeatable" cellspacing="0">
                            <thead><tr>
                              <th><span class="sort_label"></span></th>
                              <th>' . esc_html_x( 'Fields', 'Table title for metabox repeatables' ) . '</th>
                              <th><a class="meta_box_repeatable_add" href="#"></a></th>
                            </tr></thead><tbody>';

                        $i = 0;

                        // create an empty array
                        if ( $meta == '' || $meta == array() ) {
                            $keys = wp_list_pluck( $repeatable_fields, 'id' );
                            $meta = array( array_fill_keys( $keys, null ) );
                        }

                        $meta = array_values( $meta );

                        foreach ( $meta as $row ) {
                            echo '<tr><td class="side_icons"><span class="sort hndle"></span></td><td><table>';

                            foreach ( $repeatable_fields as $repeatable_field ) {
                                if ( ! array_key_exists( $repeatable_field[ 'id' ], $meta[ $i ] ) ) {
                                    $meta[ $i ][ $repeatable_field[ 'id' ] ] = null;
                                }

                                echo '<tr><th><label>' . $repeatable_field[ 'label' ] . '</label></th><td>';

                                $this->renderFieldElement( $repeatable_field, $meta[ $i ][ $repeatable_field[ 'id' ] ], array( $id, $i ) );

                                echo '</td></tr>';
                            } // end each field

                            echo '</table></td><td class="side_icons"><a class="meta_box_repeatable_remove" href="#"></a></td></tr>';
                            $i++;

                        } // end each row

                        echo '</tbody><tfoot><tr>
                                    <th><span class="sort_label"></span></th>
                                    <th>' . esc_html_x( 'Fields', 'Table title for metabox repeatables' ) . '</th>
                                    <th><a class="meta_box_repeatable_add" href="#"></a></th>
                                </tr></tfoot></table>' . $desc;
                        break;

                    default:
                        break;
                } //end switch

            }

        }

    endif;