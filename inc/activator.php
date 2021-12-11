<?php

    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    if ( ! class_exists( 'WCFF_Activator' ) ):
        class WCFF_Activator implements WCFF_Interface_ActionHook {
            /**
             * Init the activation of this plugin, with multisite support.
             *
             * @since 1.0
             *
             * @global wpdb $wpdb         WordPress database abstraction object.
             *
             * @param bool  $network_wide Activation is network wide or not
             */
            public static function init( $network_wide ) {
                global $wpdb;

                // Checks if user has permission do activate plugin
                if ( ! current_user_can( 'activate_plugins' ) ) {
                    return;
                }

                self::checkDependencies();

                // Run the activation with multisite support.
                if ( is_multisite() && $network_wide ) {
                    $old_blog = $wpdb->blogid;
                    $blogids  = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

                    foreach ( $blogids as $blog_id ) {
                        switch_to_blog( $blog_id );
                        self::activate();
                    }

                    switch_to_blog( $old_blog );

                    return;
                }

                // Run activation on single site install.
                self::activate();
            }

            /**
             * Checks if all dependencies for this plugin.
             * If not matched, the plugin will not be activated.
             *
             * @since 1.0
             *
             * @global string $wp_version Current version of WordPress.
             * @global wpdb   $wpdb       WordPress database abstraction object.
             *
             * @param string  $phpv       Mandatory PHP version. (Default: 5.3)
             * @param string  $wpv        Mandatory WordPress version. (Default: 3.5)
             * @param string  $dbv        Mandatory Database version. (Default: 5.6)
             */
            public static function checkDependencies( $phpv = '5.4', $wpv = '3.5', $dbv = '5.6' ) {
                global $wp_version, $wpdb;

                // Check the installed PHP version.
                if ( version_compare( PHP_VERSION, $phpv, '<' ) ) {
                    deactivate_plugins( basename( __FILE__ ) );
                    wp_die( '<p>' . sprintf( esc_html__( 'This plugin can not be activated because it requires a PHP version greater than %1$s. Your PHP version can be updated by your hosting company.', WCFF_TEXT_DOMAIN ), $phpv ) . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . esc_html__( 'Go back', WCFF_TEXT_DOMAIN ) . '</a>' );
                }

                // Check the installed WordPress version.
                if ( version_compare( $wp_version, $wpv, '<' ) ) {
                    deactivate_plugins( basename( __FILE__ ) );
                    wp_die( '<p>' . sprintf( esc_html__( 'This plugin can not be activated because it requires a WordPress version greater than %1$s. Please go to Dashboard &#9656; Updates to gran the latest version of WordPress.', WCFF_TEXT_DOMAIN ), $wpv ) . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . esc_html__( 'Go back', WCFF_TEXT_DOMAIN ) . '</a>' );
                }

                // Check the installed Database version.
                $mysql_version = $wpdb->get_var( "SELECT @@version;" );

                if ( ! is_null( $mysql_version ) && version_compare( strtok( $mysql_version, '-' ), $dbv, '<' ) ) {
                    deactivate_plugins( basename( __FILE__ ) );
                    wp_die( '<p>' . sprintf( esc_html__( 'This plugin can not be activated because it requires a Database version greater than %1$s. Your Database version can be updated by your hosting company.', WCFF_TEXT_DOMAIN ), $dbv ) . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . esc_html__( 'Go back', WCFF_TEXT_DOMAIN ) . '</a>' );
                }

                // Check if WooCommerce is activated
                if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                    deactivate_plugins( basename( __FILE__ ) );
                    wp_die( '<p>' . esc_html__( 'WooCommerce have to be installed and activated for this plugin. Please install or activate it.', WCFF_TEXT_DOMAIN ) . '</p> <a href="' . admin_url( 'plugins.php' ) . '">' . esc_html__( 'Go back', WCFF_TEXT_DOMAIN ) . '</a>' );
                }
            }

            /**
             * Run the plugin activation.
             *
             * @global WP_Rewrite $wp_rewrite
             *
             * @since 1.0
             */
            public static function activate() {
                $version = wcff_version();

                self::executeSQL();

                // Store installed version.
                wcff_update_option( 'version', $version[ 'current' ] );
            }

            /**
             * Run the activation if a new blog created on multisite.
             *
             * @since 1.0
             *
             * @param int $blog_id ID of the blog.
             */
            public function newBlog( $blog_id ) {
                // Run only if plugin is activated network-wide
                if ( ! is_plugin_active_for_network( plugin_basename( dirname( __DIR__ ) ) . '/' . WCFF_PLUGIN_NAME . '.php' ) ) {
                    return;
                }

                $old_blog = get_current_blog_id();

                switch_to_blog( $blog_id );
                self::activate();
                switch_to_blog( $old_blog );
            }

            /**
             * Execute all SQL queries for the right version of the plugin.
             *
             * @since 1.0
             *
             * @global wpdb $wpdb WordPress database abstraction object.
             */
            private static function executeSQL() {
                global $wpdb;

                $path  = WCFF_PLUGIN_DIR . '/sql/';
                $files = scandir( $path );

                // No files available, stop.
                if ( empty( $files ) ) {
                    return;
                }

                $version = wcff_version();

                foreach ( $files as $filename ) {
                    // read only .sql files
                    if ( strpos( $filename, '.sql' ) === false ) {
                        continue;
                    }

                    $sql_version = str_replace( '.sql', '', $filename );

                    // Compare installed with sql version.
                    if ( $version[ 'installed' ] != 0 && ( $version[ 'installed' ] == $sql_version || self::versionCompare( $sql_version, $version[ 'installed' ], '<' ) ) ) {
                        continue;
                    }

                    $sqls = file_get_contents( $path . $filename );

                    // File is empty, continue.
                    if ( empty( $sqls ) ) {
                        continue;
                    }

                    // Split each empty line to a aray value, because we can only run one query per call.
                    $sqls = preg_split( "#\n\s*\n#Uis", str_replace( array( '%%prefix%%', '%%charset_collate%%' ), array( $wpdb->prefix, $wpdb->get_charset_collate() ), $sqls ) );

                    foreach ( $sqls as $sql ) {
                        $result = $wpdb->query( $sql );

                        if ( $result === false ) {
                            wcff_log( "ERROR: Could not run SQL for version {$sql_version}: " . PHP_EOL . $wpdb->last_error, 'activator' );
                        }
                    }

                }
            }

            /**
             * Compare to versions.
             *
             * @param string $ver1    1st version number to compare
             * @param string $ver2    2nd version number to compare
             * @param string $compare How to compare versions (Conditions: >, <, >=, <=, ==, >=)
             *
             * @return bool True if matched, otherwiese false.
             */
            public static function versionCompare( $ver1, $ver2, $compare = '<' ) {
                $ver1 = rtrim( str_replace( '.', '', $ver1 ), '0' );
                $ver2 = rtrim( str_replace( '.', '', $ver2 ), '0' );

                if ( $compare == '>' && $ver1 > $ver2 ) {
                    return true;
                }
                elseif ( $compare == '<' && $ver1 < $ver2 ) {
                    return true;
                }
                elseif ( $compare == '>=' && $ver1 >= $ver2 ) {
                    return true;
                }
                elseif ( $compare == '<=' && $ver1 <= $ver2 ) {
                    return true;
                }
                elseif ( $compare == '==' && $ver1 == $ver2 ) {
                    return true;
                }
                elseif ( $compare == '!=' && $ver1 != $ver2 ) {
                    return true;
                }
                else {
                    return false;
                }
            }

            /**
             * Returns an array of actions of this class.
             *
             * @since 1.0
             *
             * @return array Associative array with actions.
             */
            public static function getActions() {
                return array(
                    'wpmu_new_blog' => 'newBlog',
                );
            }
        }

    endif;