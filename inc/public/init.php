<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_Public_Init' ) ):

        class WCFF_Public_Init implements WCFF_Interface_FilterHook {

            /**
             * Register WP filters
             *
             * @return array
             */
            public static function getFilters() {
                return array(
                    //'template_include' => 'filterTemplate',
                );
            }
        }

    endif;