<?php
    // If this file is called directly, abort.
    defined( 'WPINC' ) || die;

    // Include Class for Chrome Console Logger (https://craig.is/writing/chrome-logger)
    if ( ! class_exists( 'ChromePhp' ) ) {
        include WCFF_PLUGIN_DIR . '/lib/ChromePhp.php';
    }

    if ( ! class_exists( 'WCFF_Logger' ) ):
        /**
         * Class WCFF_Logger
         *
         * Hanlding the logging into the logfile.
         */
        class WCFF_Logger extends WCFF_Singleton {
            private $active;
            private $file;
            private $optionName;
            private $consoleActive;

            /**
             * Return the instance of this object. (Singleton)
             * If WP_DEBUG is set to true, the logging will be activated directly.
             *
             * @return bool|static False if something went wrong, otherwise the object.
             */
            public static function getInstance() {
                return parent::getInstance();
            }

            /**
             * WCFF_Logger constructor.
             *
             * Set defaults to the object.
             * Constructor is protected, so nobody can instance it. (Singleton)
             */
            protected function __construct() {
                parent::__construct();

                $this->optionName    = null;
                $this->file          = WP_PLUGIN_DIR . '/' . strtok( plugin_basename( __FILE__ ), '/' ) . '/log.txt';
                $this->consoleActive = ( wcff_is_admin() && class_exists( 'ChromePhp' ) );

                // OActivate logging, if WP Debug is enabled
                if ( WP_DEBUG === true ) {
                    $this->activate();
                }
            }

            /**
             * Toggle the action hooks for logging the options, post and user meta changes.
             *
             * @param bool $shouldAdd True if actions should be added, otherwise they will be removed. (Default: true)
             */
            private function toggleActionHooks( $shouldAdd = true ) {
                $actions = array(
                    'updated_option' => array( 'callback' => 'writeUpdatedOption', 'priority' => 10, 'args' => 3 ),
                    'added_option'   => array( 'callback' => 'writeAddedOption', 'priority' => 10, 'args' => 2 ),
                    'deleted_option' => array( 'callback' => 'writeDeletedOption', 'priority' => 10, 'args' => 1 ),

                    'added_user_meta'   => array( 'callback' => 'writeAddedUserMeta', 'priority' => 10, 'args' => 4 ),
                    'deleted_user_meta' => array( 'callback' => 'writeDeletedUserMeta', 'priority' => 10, 'args' => 3 ),
                    'updated_user_meta' => array( 'callback' => 'writeUpdatedUserMeta', 'priority' => 10, 'args' => 4 ),

                    'added_post_meta'   => array( 'callback' => 'writeAddedPostMeta', 'priority' => 10, 'args' => 4 ),
                    'deleted_post_meta' => array( 'callback' => 'writeDeletedPostMeta', 'priority' => 10, 'args' => 3 ),
                    'updated_post_meta' => array( 'callback' => 'writeUpdatedPostMeta', 'priority' => 10, 'args' => 4 ),
                );

                foreach ( $actions as $action => &$param ) {
                    if ( $shouldAdd ) {
                        add_action( $action, array( $this, $param[ 'callback' ] ), $param[ 'priority' ], $param[ 'args' ] );
                        continue;
                    }

                    remove_action( $action, array( $this, $param[ 'callback' ] ), $param[ 'priority' ], $param[ 'args' ] );
                }
            }

            /**
             * Writes into the logfile if a post meta was added.
             * This method is generally used by the action hook "added_post_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $post_id Post ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeAddedPostMeta( $id, $post_id, $key, $value ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Post meta (ID: %d) "%s" was ADDED for post (ID: %d) with following value: "%s"', $id, $key, $post_id, maybe_serialize( $value ) ), $key );
            }

            /**
             * Writes into the logfile if a post meta was deleted.
             * This method is generally used by the action hook "deleted_post_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $post_id Post ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeDeletedPostMeta( $id, $post_id, $key ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Post meta (ID: %d) "%s" was DELETED for post (ID: %d).', $id, $key, $post_id ), $key );
            }


            /**
             * Writes into the logfile if a post meta was updated.
             * This method is generally used by the action hook "updated_post_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $post_id Post ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeUpdatedPostMeta( $id, $post_id, $key, $value ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Post meta (ID: %d) "%s" was UPDATED for post (ID: %d) with following value: "%s"', $id, $key, $post_id, maybe_serialize( $value ) ), $key );
            }

            /**
             * Writes into the logfile if a user meta was added.
             * This method is generally used by the action hook "added_user_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $user_id User ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeAddedUserMeta( $id, $user_id, $key, $value ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'User meta (ID: %d) "%s" was ADDED for user (ID: %d) with following value: "%s"', $id, $key, $user_id, maybe_serialize( $value ) ), $key );
            }

            /**
             * Writes into the logfile if a user meta was deleted.
             * This method is generally used by the action hook "deleted_user_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $user_id User ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeDeletedUserMeta( $id, $user_id, $key ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'User meta (ID: %d) "%s" was DELETED for user (ID: %d)', $id, $key, $user_id ), $key );
            }

            /**
             * Writes into the logfile if a user meta was updated.
             * This method is generally used by the action hook "updated_user_meta".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param int    $id      The meta ID after successful update.
             * @param int    $user_id User ID.
             * @param string $key     Meta key.
             * @param mixed  $value   Meta value.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeUpdatedUserMeta( $id, $user_id, $key, $value ) {
                if ( $this->shouldLogOption( $key ) ) {
                    return false;
                }

                return $this->write( sprintf( 'User meta (ID: %d) "%s" was UPDATED for user (ID: %d) with following value: "%s"', $id, $key, $user_id, maybe_serialize( $value ) ), $key );
            }

            /**
             * Writes an option change (update_option) into the logfile.
             * This method is generally used by the action hook "updated_option".
             * IMPORTANT: Only logging, if options are set!
             *
             * @param $option    Name of the option.
             * @param $old_value Old value of the option.
             * @param $value     New value of the option.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeUpdatedOption( $option, $old_value, $value ) {
                if ( $this->shouldLogOption( $option ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Option "%s" UPDATED from "%s" to "%s".', $option, maybe_serialize( $old_value ), maybe_serialize( $value ) ), $option );
            }

            /**
             * Writes into the logfile if a option was added.
             * This method is generally used by the action hook "added_option".
             * IMPORTANT: Only logging, if the options are set!
             *
             * @param $option    Name of the option.
             * @param $value     New value of the option.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeAddedOption( $option, $value ) {
                if ( $this->shouldLogOption( $option ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Option "%s" was ADDED with following value: "%s"', $option, maybe_serialize( $value ) ), $option );
            }

            /**
             * Writes into the logfile if a option was deleted.
             * This method is generally used by the action hook "deleted_option".
             * IMPORTANT: Only logging, if the options are set!
             *
             * @param $option    Name of the option.
             *
             * @return bool True if successfull written into logfile, otherwise false.
             */
            public function writeDeletedOption( $option ) {
                if ( $this->shouldLogOption( $option ) ) {
                    return false;
                }

                return $this->write( sprintf( 'Option "%s" was DELETED.', $option ), $option );
            }

            /**
             * Write into the log file, if logging is activated.
             *
             * @param mixed  $data Data to write into file. Can be a array, object, string etc.
             * @param string $id   The ID of this entry. Usefull to distinguish the entries.
             *
             * @return bool
             */
            public function write( $data, $id = '' ) {
                if ( $this->active === false ) {
                    return false;
                }

                $content = "### {$id} - " . $this->getDatetime() . PHP_EOL . stripslashes( var_export( $data, true ) ) . PHP_EOL . PHP_EOL;

                if ( $this->consoleActive ) {
                    //ChromePhp::log( $content );
                }

                return ( false !== file_put_contents( $this->file, $content, FILE_APPEND ) );
            }

            /**
             * Set the path to the directory, where the logfile should be stored.
             * Default: The path to the plugin root directory.
             *
             * @param string $path Path to the directory.
             *
             * @return bool|string False if path not set, otherwise the stored path.
             */
            public function setFile( $path ) {
                if ( empty( $path ) || ! @is_dir( wp_normalize_path( $path ) ) || ! wp_is_writable( $path ) ) {
                    return false;
                }

                $this->file = wp_normalize_path( trailingslashit( $path ) . 'log.txt' );

                return $this->file;
            }

            /**
             * Set options that should be logged.
             *
             * @param string|array $optionName Name of the option, a option prefix or an array with option names that should be logged.
             *
             * @return bool True if set, otherwise false.
             */
            public function setOptionName( $optionName ) {
                if ( empty( $optionName ) || ( ! is_string( $optionName ) && ! is_array( $optionName ) ) ) {
                    return false;
                }

                $this->optionName = $optionName;

                return true;
            }

            /**
             * Returns the options that will be logged.
             *
             * @return null|array|string Name of stored option or a array with all option names, otherwise null.
             */
            public function getOptionName() {
                return $this->optionName;
            }

            /**
             * Activate the logging. The write method will write into the log file.
             */
            public function activate() {
                // Only run, if logging is not already activated.
                if ( $this->active !== true ) {
                    $this->toggleActionHooks( true );
                }

                $this->active = true;
            }

            /**
             * Deactivate the logging. The write method wont write into the log file.
             */
            public function deactivate() {
                // Only run, if logging is not already deactivated.
                if ( $this->active !== false ) {
                    $this->toggleActionHooks( false );
                }

                $this->active = false;
            }

            /**
             * Check if logging is activated or not.
             *
             * @return bool True if active, otherwise false.
             */
            public function isActive() {
                return $this->active;
            }

            /**
             * Get the datetime with microseconds.
             *
             * @param string $format Format for the output. See date().
             *
             * @return false|string a formatted date string. If a non-numeric value is used for timestamp, false is returned and an E_WARNING level error is emitted.
             */
            private function getDatetime( $format = 'd.m.Y H:i:s.u' ) {
                if ( empty( $format ) ) {
                    return false;
                }

                return DateTime::createFromFormat( 'U.u', microtime( true ) )->format( $format );
            }

            /**
             * Check if a option should be logged or not.
             *
             * @param $option Option name.
             *
             * @return bool True if should be logged, otherwise false.
             */
            private function shouldLogOption( $option ) {
                return $this->active === false || empty( $this->optionName ) || ( is_string( $this->optionName ) && 0 !== strpos( $option, $this->optionName ) ) || ( is_array( $this->optionName ) && ! in_array( $option, $this->optionName, true ) );
            }

        }

    endif;