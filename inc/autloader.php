<?php

    /**
     * Autoloads classes using PSR-0 standard.
     */

    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    if ( ! class_exists( 'WCFF_Autoloader' ) ):

        class WCFF_Autoloader {
            /**
             * Registers autoloader as an SPL autoloader.
             *
             * @param boolean $prepend If true, spl_autoload_register() will prepend the autoloader on the autoload stack instead of appending it.
             */
            public static function register( $prepend = false ) {
                if ( version_compare( phpversion(), '5.3.0', '>=' ) ) {
                    return spl_autoload_register( array( new self, 'autoload' ), true, $prepend );
                }

                return spl_autoload_register( array( new self, 'autoload' ) );
            }

            /**
             * Handles autoloading classes.
             *
             * @param string $class
             */
            public static function autoload( $class ) {
                $class_prefix = WCFF_CLASS_PREFIX . '_';

                if ( 0 !== strpos( $class, $class_prefix ) ) {
                    return;
                }

                $search_replace = array( $class_prefix => '', '_' => DIRECTORY_SEPARATOR, "\0" => '' );
                $file           = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . strtolower( str_replace( array_keys( $search_replace ), $search_replace, $class ) . '.php' );

                if ( is_file( $file ) ) {
                    require_once $file;
                }
            }
        }

    endif;