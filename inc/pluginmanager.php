<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    if ( ! class_exists( 'WCFF_PluginManager' ) ):

        /**
         * WCFF_PluginManager handles registering actions and hooks with the
         * WordPress Plugin API.
         */

        class WCFF_PluginManager {
            /**
             * Registers an object with the WordPress Plugin API.
             *
             * @param mixed $object
             */
            public function register( $object ) {
                if ( $object instanceof WCFF_Interface_ActionHook ) {
                    $this->registerActions( $object );
                }
                elseif ( $object instanceof WCFF_Interface_FilterHook ) {
                    $this->registerFilters( $object );
                }
                elseif ( $object instanceof WCFF_Interface_ShortcodeHook ) {
                    $this->registerShortcodes( $object );
                }
            }

            /**
             * Register an object with a specific action hook.
             *
             * @param        $object
             * @param string $name
             * @param mixed  $parameters
             */
            public static function registerAction( $object, $name, $parameters ) {
                if ( is_string( $parameters ) ) {
                    add_action( $name, array( $object, $parameters ) );
                }
                elseif ( is_array( $parameters ) && isset( $parameters[ 0 ] ) ) {
                    add_action( $name, array( $object, $parameters[ 0 ] ), isset( $parameters[ 1 ] ) ? $parameters[ 1 ] : 10, isset( $parameters[ 2 ] ) ? $parameters[ 2 ] : 1 );
                }
            }

            /**
             * Regiters an object with all its action hooks.
             *
             * @param WCFF_Interface_ActionHook $object
             */
            private function registerActions( WCFF_Interface_ActionHook $object ) {
                foreach ( $object->getActions() as $name => &$parameters ) {
                    self::registerAction( $object, $name, $parameters );
                }
            }

            /**
             * Register an object with a specific filter hook.
             *
             * @param        $object
             * @param string $name
             * @param mixed  $parameters
             */
            public static function registerFilter( $object, $name, $parameters ) {
                if ( is_string( $parameters ) ) {
                    add_filter( $name, array( $object, $parameters ) );
                }
                elseif ( is_array( $parameters ) && isset( $parameters[ 0 ] ) ) {
                    add_filter( $name, array( $object, $parameters[ 0 ] ), isset( $parameters[ 1 ] ) ? $parameters[ 1 ] : 10, isset( $parameters[ 2 ] ) ? $parameters[ 2 ] : 1 );
                }
            }

            /**
             * Regiters an object with all its filter hooks.
             *
             * @param WCFF_Interface_FilterHook $object
             */
            private function registerFilters( WCFF_Interface_FilterHook $object ) {
                foreach ( $object->getFilters() as $name => &$parameters ) {
                    self::registerFilter( $object, $name, $parameters );
                }
            }

            public static function registerShortcode( $object, $name, $parameter ) {
                add_shortcode( $name, array( $object, $parameter ) );
            }

            /**
             * Regiters an object with all its shortcode hooks.
             *
             * @param WCFF_Interface_ShortcodeHook $object
             */
            private function registerShortcodes( WCFF_Interface_ShortcodeHook $object ) {
                foreach ( $object->getShortcodes() as $name => &$parameter ) {
                    self::registerShortcode( $object, $name, $parameter );
                }
            }

        }

    endif;