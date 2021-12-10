<?php

    // If uninstall not called from WordPress, then exit.
    defined( 'WP_UNINSTALL_PLUGIN' ) || die;

    if ( ! class_exists( 'WCFF_Uninstall' ) ):
        class WCFF_Uninstall {
            /**
             * Init the uninstall of this plugin, with multisite support.
             *
             * @since 1.0
             *
             * @global wpdb $wpdb WordPress database abstraction object.
             */
            public static function init() {
                global $wpdb;

                // Run the uninstall with multisite support.
                if ( is_multisite() ) {
                    $old_blog = $wpdb->blogid;
                    $blogids  = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

                    foreach ( $blogids as $blog_id ) {
                        switch_to_blog( $blog_id );

                        self::uninstall();
                    }

                    switch_to_blog( $old_blog );

                    return;
                }

                // Run uninstall on single site install.
                self::uninstall();
            }

            /**
             * Run the plugin uninstall.
             *
             * @since 1.0
             */
            private static function uninstall() {
                self::deleteDB();
            }

            /**
             * Delete the stored information in the database.
             *
             * @since 1.0
             *
             * @global wpdb $wpdb WordPress database abstraction object.
             */
            private static function deleteDB() {
                global $wpdb;
                // Use "DROP IF EXIST" etc.

                // Remove all options, postmeta & usermeta of this plugin
                $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '" . $wpdb->esc_like( WCFF_OPTIONS_PREFIX ) . "%'" );
                $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '" . $wpdb->esc_like( WCFF_OPTIONS_PREFIX ) . "%'" );
                $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '" . $wpdb->esc_like( WCFF_OPTIONS_PREFIX ) . "%'" );

                // Optimize DB
                $wpdb->query( "OPTIMIZE TABLE $wpdb->options" );
                $wpdb->query( "OPTIMIZE TABLE $wpdb->postmeta" );
                $wpdb->query( "OPTIMIZE TABLE $wpdb->usermeta" );
            }
        }

    endif;

    // Run the uninstall
    WCFF_Uninstall::init();