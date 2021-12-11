<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_Admin_Product' ) ):

        class WCFF_Admin_Product implements WCFF_Interface_ActionHook {
            private static $option_name_fulfillment = WCFF_OPTIONS_PREFIX . 'fulfillment';

            /**
             * Render fields to shipping tab of the product.
             */
            public function renderShippingFields() {
                global $post;

                echo '</div><div class="options_group">';

                woocommerce_wp_checkbox( array(
                    'id'            => 'fulfillment',
                    'label'         => esc_html__( 'Fulfillment', WCFF_TEXT_DOMAIN ),
                    'description'   => esc_html__( 'Send order data to fulfillment, if this product was purchased.', WCFF_TEXT_DOMAIN ),
                    'desc_tip'      => false,
                    'value'         => ( $this->isFulfillment( $post->ID ) ? 'yes' : 'no' ),
                    'wrapper_class' => 'show_if_simple show_if_variable',
                    'cbvalue'       => 'yes',
                ) );
            }

            /**
             * Store the product fields from the configuration form.
             *
             * @param int $product_id Product post id
             */
            public function saveProductFields( $product_id ) {
                if ( empty( $product_id ) ) {
                    return;
                }

                if ( empty( $_POST[ 'fulfillment' ] ) ) {
                    $this->deleteFulfillmentStatus( $product_id );

                    return;
                }

                $fulfillment = filter_input( INPUT_POST, 'fulfillment', FILTER_SANITIZE_STRING );

                if ( $fulfillment != 'yes' ) {
                    return;
                }

                $this->setFulfillmentStatus( $product_id, $fulfillment );
            }

            /**
             * Render variation fields to configuration form.
             *
             * @param int     $loop           Position in the loop.
             * @param array   $variation_data Variation data.
             * @param WP_Post $variation      Post data.
             */
            public function renderVariationFields( $loop, $variation_data, $variation ) {
                echo '<div class="options_group form-row form-row-first">';

                $fulfillment_value = ( empty( $variation_data[ self::$option_name_fulfillment ] ) || $variation_data[ self::$option_name_fulfillment ] == 'no' ? 'no' : 'yes' );

                woocommerce_wp_checkbox( array(
                    'id'            => 'fulfillment[' . $variation->ID . ']',
                    'label'         => '&nbsp;' . esc_html__( 'Send order data to fulfillment, if this product variation was purchased.', WCFF_TEXT_DOMAIN ),
                    'desc_tip'      => true,
                    'description'   => esc_html__( 'This will be ignored if fulfillment is enabled for this product in the shipping tab. If you want to configure it per variation, please disable it there and enable it per variation.', WCFF_TEXT_DOMAIN ),
                    'value'         => $fulfillment_value,
                    'wrapper_class' => 'hide_if_variation_virtual',
                    'cbvalue'       => 'yes',
                ) );

                echo '</div>';
            }

            /**
             * Store the variation fields from the configuration form.
             *
             * @param int $variation_id Variation post id
             */
            public function saveVariationFields( $variation_id ) {
                if ( empty( $variation_id ) || empty( $_POST[ 'fulfillment' ] ) || ! is_array( $_POST[ 'fulfillment' ] ) ) {
                    return;
                }

                // Delete all stored options for variations, because uncheckd fields are not send.
                $product_type = filter_input( INPUT_POST, 'product-type', FILTER_SANITIZE_STRING );

                if ( ! empty( $product_type ) && $product_type == 'variable' ) {
                    $product_id = filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT );

                    if ( ! empty( $product_id ) ) {
                        $product = wc_get_product( $product_id );

                        if ( ! empty( $product ) ) {

                            $variation_ids = $product->get_children();

                            if ( ! empty( $variation_ids ) ) {
                                foreach ( $variation_ids as $id ) {
                                    $this->deleteFulfillmentStatus( $id );
                                }
                            }
                        }
                    }
                }

                // Store checked fields
                $fulfillment = $_POST[ 'fulfillment' ];

                foreach ( $fulfillment as $id => $val ) {
                    if ( is_numeric( $id ) && $val == 'yes' ) {
                        $this->setFulfillmentStatus( $variation_id, $val );
                    }
                }

            }

            /**
             * Checking if variation or product has active fulfillment.
             *
             * @param int $post_id Post id of variation or product.
             *
             * @return bool
             */
            public function isFulfillment( $post_id ) {
                return ( 'yes' == get_post_meta( $post_id, self::$option_name_fulfillment, true ) );
            }

            /**
             * Delete the fulfillment status.
             *
             * @param int $post_id Post id of variation or product.
             *
             * @return bool True on success, false on failure.
             */
            public function deleteFulfillmentStatus( $post_id ) {
                return delete_post_meta( $post_id, self::$option_name_fulfillment );
            }

            /**
             * Set the fulfillment status.
             *
             * @param int    $post_id Post id of variation or product.
             * @param string $status  The status to set. Possible: yes or no. (Default: yes)
             *
             * @return int|bool Meta ID if the key didn't exist, true on successful update,
             *                  false on failure or if the value passed to the function
             *                  is the same as the one that is already in the database.
             */
            public function setFulfillmentStatus( $post_id, $status = 'yes' ) {
                if ( $status != 'yes' && $status != 'no' ) {
                    return false;
                }

                return update_post_meta( $post_id, self::$option_name_fulfillment, $status );
            }

            /**
             * Get the fulfillment status.
             *
             * @param int $post_id Post id of variation or product.
             *
             * @return bool|string The status 'yes' or 'no'. False on failure or if no status is set.
             */
            public function getFulfillmentStatus( $post_id ) {
                return get_post_meta( $post_id, self::$option_name_fulfillment, true );
            }

            /**
             * Register WP actions.
             *
             * @return array
             */
            public static function getActions() {
                return array(
                    'woocommerce_product_options_shipping'     => 'renderShippingFields',
                    'woocommerce_process_product_meta'         => 'saveProductFields',
                    'woocommerce_variation_options_dimensions' => array( 'renderVariationFields', 10, 3 ),
                    'woocommerce_save_product_variation'       => array( 'saveVariationFields', 10, 1 ),
                );
            }
        }

    endif;