<?php
    /*
     * Plugin Name: WooCommerce Fulfillment by Bonum Logistik
     * Plugin URI: https://bonum-logistik.de/
     * Description: Extend your WooCommerce store with the great fulfillment service from Bonum Logistik.
     * Version: 0.1
     * Author: Schweizer Solutions GmbH
     * Author URI: https://www.schweizersolutions.com/
     * License: GPL-2.0+
     * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
     * Text Domain: wcff
     * Domain Path: /languages
     */

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    // Define global constants
    defined( 'WCFF_VERSION_NUM' ) || define( 'WCFF_VERSION_NUM', '0.1' );
    defined( 'WCFF_PLUGIN_NAME' ) || define( 'WCFF_PLUGIN_NAME', trim( dirname( plugin_basename( __FILE__ ) ), DIRECTORY_SEPARATOR ) );
    defined( 'WCFF_CLASS_PREFIX' ) || define( 'WCFF_CLASS_PREFIX', 'WCFF' );
    defined( 'WCFF_OPTIONS_PREFIX' ) || define( 'WCFF_OPTIONS_PREFIX', '_wcff_' );
    defined( 'WCFF_PLUGIN_DIR' ) || define( 'WCFF_PLUGIN_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . WCFF_PLUGIN_NAME );
    defined( 'WCFF_PLUGIN_URL' ) || define( 'WCFF_PLUGIN_URL', WP_PLUGIN_URL . DIRECTORY_SEPARATOR . WCFF_PLUGIN_NAME );
    defined( 'WCFF_TEXT_DOMAIN' ) || define( 'WCFF_TEXT_DOMAIN', 'wcff' );
    defined( 'WCFF_LOCALE' ) || define( 'WCFF_LOCALE', apply_filters( 'plugin_locale', get_locale(), WCFF_TEXT_DOMAIN ) );
    defined( 'WCFF_CAPABILITY' ) || define( 'WCFF_CAPABILITY', 'manage_options' );

    // Start autoloader
    require WCFF_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'pluggable.php';
    require WCFF_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'autloader.php';

    WCFF_Autoloader::register();

    // Register de-/activation hook
    register_activation_hook( __FILE__, array( WCFF_CLASS_PREFIX . '_Activator', 'init' ) );
    register_deactivation_hook( __FILE__, array( WCFF_CLASS_PREFIX . '_Deactivator', 'init' ) );

    if ( ! class_exists( 'WCFF' ) ):

        /**
         * Class WCFF
         */
        class WCFF {
            /**
             * @var WCFF_PluginManager Hold the plugin manager object.
             */
            private $plugin_manager;

            /**
             * Init the plugin: Load languages, register actions and filters etc.
             */
            public function init() {
                // Set option name to logger
                WCFF_Logger::getInstance()->setOptionName( WCFF_OPTIONS_PREFIX );

                $this->plugin_manager = new WCFF_PluginManager();

                $this->loadLanguages();

                // Register actions & filters
                $this->register();

                if ( ! is_admin() ) {
                    $this->registerPublic();
                }
                else {
                    $this->registerAdmin();
                }
            }

            /**
             * Load the language files for the current language.
             */
            private function loadLanguages() {
                load_textdomain( WCFF_PLUGIN_NAME, WP_LANG_DIR . '/' . WCFF_TEXT_DOMAIN . '-' . WCFF_LOCALE . '.mo' );
                load_plugin_textdomain( WCFF_TEXT_DOMAIN, false, WCFF_PLUGIN_NAME . DIRECTORY_SEPARATOR . 'languages' . DIRECTORY_SEPARATOR );
            }

            /**
             * Register actions and filters for front- & backend (public / admin)
             */
            public function register() {
                $this->plugin_manager->register( new WCFF_Enqueue() );
            }

            /**
             * Register actions and filters for the frontend (public)
             */
            public function registerPublic() {
                //$this->plugin_manager->register( new WCFF_Public_Init() );
            }

            /**
             * Register actions and filters for the backend (admin)
             */
            public function registerAdmin() {
                $this->plugin_manager->register( new WCFF_Admin_Init );
                $this->plugin_manager->register( new WCFF_Admin_Product );
                
            }
        }

    endif;

    // Run plugin code
    add_action( 'plugins_loaded', array( new WCFF, 'init' ), 9999 );