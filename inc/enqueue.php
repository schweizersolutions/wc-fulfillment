<?php
    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_Enqueue' ) ):

        class WCFF_Enqueue implements WCFF_Interface_ActionHook {

            /**
             * Loading JavaScript files that are meant to appear on the login page.
             */
            public function enqueueLoginScripts() {

            }

            /**
             * Loading CSS files that are meant to appear on the login page.
             */
            public function enqueueLoginStyles() {

            }

            /**
             * Loading JavaScript files that are meant to appear on the front end.
             */
            public function enqueuePublicScripts() {

            }

            /**
             * Loading CSS files that are meant to appear on the front end.
             */
            public function enqueuePublicStyles() {

            }

            /**
             * Loading JavaScript files that are meant to appear on the backend end.
             *
             * @param string $hook Hook suffix of the current admin page.
             */
            public function enqueueAdminScripts( $hook ) {
                WCFF_Assets::enqueueScript( WCFF_TEXT_DOMAIN . '-admin', 'admin.js', array( 'jquery' ) );
            }

            /**
             * Loading CSS files that are meant to appear on the backend end.
             *
             * @param string $hook Hook suffix of the current admin page.
             */
            public function enqueueAdminStyles( $hook ) {
                WCFF_Assets::enqueueStyle( WCFF_TEXT_DOMAIN . '-admin', 'admin.css' );

                $this->enqueueAdminScripts( $hook );
            }

            /**
             * Returns an array of actions of this class.
             *
             * @return array Associative array with actions.
             */
            public static function getActions() {
                return array(
                    'admin_enqueue_scripts' => 'enqueueAdminStyles',
                    'wp_enqueue_scripts'    => 'enqueuePublicScripts',
                    'wp_enqueue_scripts'    => 'enqueuePublicStyles',
                    'login_enqueue_scripts' => 'enqueueLoginScripts',
                    'login_enqueue_scripts' => 'enqueueLoginStyles',
                );
            }
        }

    endif;