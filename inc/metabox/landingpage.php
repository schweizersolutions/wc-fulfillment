<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_Metabox_Landingpage' ) ):

        class WCFF_Metabox_Landingpage extends WCFF_Metabox {
            public function __construct() {
                $post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

                $ratings = array(
                    array(
                        'label' => esc_html__( '1 - Very bad', WCFF_TEXT_DOMAIN ),
                        'value' => 1,
                    ),
                    array(
                        'label' => esc_html__( '2 - Bad', WCFF_TEXT_DOMAIN ),
                        'value' => 2,
                    ),
                    array(
                        'label' => esc_html__( '3 - Okay', WCFF_TEXT_DOMAIN ),
                        'value' => 3,
                    ),
                    array(
                        'label' => esc_html__( '4 - Good', WCFF_TEXT_DOMAIN ),
                        'value' => 4,
                    ),
                    array(
                        'label' => esc_html__( '5 - Very good', WCFF_TEXT_DOMAIN ),
                        'value' => 5,
                    ),
                );

                $domain = empty( $post_id ) ? null : get_post_meta( $post_id, WCFF_OPTIONS_PREFIX . 'url_domain', true );

                $fields = array(
                    array(
                        'label' => esc_html__( 'URL', WCFF_TEXT_DOMAIN ),
                        'id'    => WCFF_OPTIONS_PREFIX . 'url',
                        'type'  => 'text',
                        'desc'  => empty( $domain ) ? '' : esc_html( sprintf( __( 'Domain of this URL: %s', WCFF_TEXT_DOMAIN ), $domain ) ),
                    ),
                    array(
                        'label'     => esc_html__( 'Rating structure', WCFF_TEXT_DOMAIN ),
                        'id'        => WCFF_OPTIONS_PREFIX . 'rating_structure',
                        'type'      => 'select',
                        'options'   => $ratings,
                        'sanitizer' => 'intval',
                    ),
                    array(
                        'label'     => esc_html__( 'Rating design', WCFF_TEXT_DOMAIN ),
                        'id'        => WCFF_OPTIONS_PREFIX . 'rating_design',
                        'type'      => 'select',
                        'options'   => $ratings,
                        'sanitizer' => 'intval',
                    ),
                    array(
                        'label'     => esc_html__( 'Positive points', WCFF_TEXT_DOMAIN ),
                        'id'        => WCFF_OPTIONS_PREFIX . 'positive_points',
                        'type'      => 'textarea',
                        'desc'      => esc_html__( 'Please enter one bulletpoint per line.', WCFF_TEXT_DOMAIN ),
                        'sanitizer' => 'sanitize_textarea_field',
                    ),
                    array(
                        'label'     => esc_html__( 'Negative points', WCFF_TEXT_DOMAIN ),
                        'id'        => WCFF_OPTIONS_PREFIX . 'negative_points',
                        'type'      => 'textarea',
                        'desc'      => esc_html__( 'Please enter one bulletpoint per line.', WCFF_TEXT_DOMAIN ),
                        'sanitizer' => 'sanitize_textarea_field',
                    ),
                );

                parent::__construct( 'landingpage-settings', esc_html__( 'Features', WCFF_TEXT_DOMAIN ), 'landingpage', $fields, null, 'low' );
            }

            public function save( $post_id ) {
                $url = filter_input( INPUT_POST, WCFF_OPTIONS_PREFIX . 'url', FILTER_SANITIZE_URL );

                if ( ! empty( $url ) ) {
                    $host = parse_url( $url, PHP_URL_HOST );

                    if ( ! empty( $host ) ) {
                        $host      = explode( '.', $host );
                        $size_host = sizeof( $host );

                        update_post_meta( $post_id, WCFF_OPTIONS_PREFIX . 'url_domain', "{$host[$size_host-2]}.{$host[$size_host-1]}" );
                    }
                }

                parent::save( $post_id );
            }
        }

    endif;