<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_Admin_Init' ) ):

        class WCFF_Admin_Init implements WCFF_Interface_ActionHook {

            public function init() {

            }


            /**
             * Register WP actions.
             *
             * @return array
             */
            public static function getActions() {
                return array(
                );
            }
        }

    endif;