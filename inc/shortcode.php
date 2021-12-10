<?php

if ( ! defined( 'WPINC' ) ):
    die;
endif;

if ( ! class_exists( 'WCFF_Shortcode' ) ):

    class WCFF_Shortcode implements WCFF_Interface_ShortcodeHook {
        private static $tag;
        private $default_atts;
        private $echo;

        public function __construct( $tag, $default_atts = array(), $echo = false ) {
            self::$tag = $tag;
            $this->setDefaultAtts( $default_atts );
            $this->echo = $echo;
        }

        public function render( $atts = array(), $content = null ) {
            if ( ! empty( $atts ) ) {
                $atts = shortcode_atts( $this->default_atts, $atts, $this->getTag() );
            }

            $content = do_shortcode( $this->prepareRender( $atts, $content ) );

            if ( ! $this->echo ) {
                return $content;
            }

            echo $content;
        }

        public function prepareRender( $atts, $content ) {
            return '';
        }

        public function setDefaultAtts( $default_atts ) {
            if ( ! is_array( $default_atts ) ) {
                $default_atts = array( $default_atts );
            }

            $this->default_atts = $default_atts;
        }

        public function getDefaultAtts() {
            return $this->default_atts;
        }

        public function getTag() {
            return self::$tag;
        }

        public function exist( $shortcodes = null ) {
            if ( is_null( $shortcodes ) ) {
                return shortcode_exists( $this->getTag() );
            }

            if ( ! is_array( $shortcodes ) ) {
                return shortcode_exists( $shortcodes );
            }

            $result = array();

            foreach ( $shortcodes as &$shortcode ) {
                $result[ $shortcode ] = shortcode_exists( $shortcode );
            }

            return $result;
        }

        public function remove( $shortcodes = null ) {
            if ( is_null( $shortcodes ) ) {
                $shortcodes = array( $this->getTag() );
            }

            if ( ! is_array( $shortcodes ) ) {
                $shortcodes = array( $shortcodes );
            }

            foreach ( $shortcodes as &$shortcode ) {
                remove_shortcode( $shortcode );
            }
        }

        public static function stripShortcodes( $content ) {
            return strip_shortcodes( $content );
        }

        public static function getShortcodes() {
            return array( self::$tag => 'render' );
        }
    }

endif;