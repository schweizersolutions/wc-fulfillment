<?php
    /**
     * These functions can be replaced via plugins. If plugins do not redefine these
     * functions, then these will be used instead.
     */

    // If this file is called directly, abort.
    defined( 'WPINC' ) || die;

    if ( ! function_exists( 'wcff_is_admin' ) ):
        /**
         * Check if a user is admin.
         *
         * @param null|int $user_id The WordPress user ID, if null the current user_id will be used (Default: null)
         *
         * @return bool True if user is admin, otherwise false
         */
        function wcff_is_admin( $user_id = null ) {
            if ( empty( $user_id ) && ! ( $user_id = get_current_user_id() ) ) {
                return false;
            }

            return user_can( $user_id, 'delete_users' );
        }
    endif;

    if ( ! function_exists( 'wcff_get_time_dropdown' ) ):
        /**
         * Get a dropdown with times.
         *
         * @param string $name     The "name"-HTML attribute.
         * @param string $selected Selected time in the dropdown (Default: '00:00')
         * @param string $format   Format of the time. Check php date-function doc for the right format. (Default: 'h.i A')
         * @param int    $interval The distance in minutes between the times. (Default: 5)
         *
         * @return string HTML-Code with the dropdown.
         */
        function wcff_get_time_dropdown( $name, $selected = '00:00', $format = 'h.i A', $interval = 5 ) {
            $output = '';

            if ( empty( $interval ) || $interval < 0 ) {
                $interval = 5;
            }
            elseif ( $interval > 60 ) {
                $interval = 60;
            }

            $current  = strtotime( '00:00' );
            $end      = strtotime( '23:59' );
            $interval = "+{$interval} minutes";

            while ( $current <= $end ) {
                $time = date( 'H:i', $current );
                $sel  = ( $time == $selected ) ? ' selected' : '';

                $output  .= "<option value=\"{$time}\"{$sel}>" . date( $format, $current ) . '</option>';
                $current = strtotime( $interval, $current );
            }

            return '<select name="' . esc_attr( $name ) . '">' . $output . '</select>';
        }
    endif;

    if ( ! function_exists( 'wcff_do_action' ) ):
        /**
         * Execute functions hooked on a specific action hook.
         * With prepend prefix for this plugin. (e.g. wcff_$name)
         *
         * @since 1.0
         *
         * @param string $name    Name of the action.
         * @param mixed  $arg,... Optional. Additional arguments which are passed on to the
         *                        functions hooked to the action. Default empty.
         */
        function wcff_do_action( $name, $arg = '' ) {
            $args = array( WCFF_PLUGIN_NAME . '_' . $name, $arg );

            call_user_func_array( 'do_action', array_merge( $args, func_get_args() ) );
        }
    endif;

    if ( ! function_exists( 'wcff_decode_html' ) ):
        function wcff_decode_html( $html, $isEditor = false ) {
            return WCFF_Public_Init::decodeHTML( $html, $isEditor );
        }
    endif;

    if ( ! function_exists( 'wcff_apply_filters' ) ):
        /**
         * Call the functions added to a filter hook.
         * With prepend prefix for this plugin. (e.g. wcff_$name)
         *
         * @since 1.0
         *
         * @param string $name    Name of the action.
         * @param mixed  $value   The value on which the filters hooked to `$name` are applied on.
         * @param mixed  $var,... Additional variables passed to the functions hooked to `$name`.
         *
         * @return mixed The filtered value after all hooked functions are applied to it.
         */
        function wcff_apply_filters( $name, $value ) {
            $args = array( WCFF_PLUGIN_NAME . '_' . $name, $value );

            return call_user_func_array( 'apply_filters', array_merge( $args, func_get_args() ) );
        }
    endif;

    if ( ! function_exists( 'wcff_get_option' ) ):
        /**
         * Retrieves an option value based on an option name.
         *
         * If the option does not exist or does not have a value, then the return value
         * will be false. This is useful to check whether you need to install an option
         * and is commonly used during installation of plugin options and to test
         * whether upgrading is required.
         *
         * If the option was serialized then it will be unserialized when it is returned.
         *
         * Any scalar values will be returned as strings. You may coerce the return type of
         * a given option by registering an {@see 'option_$option'} filter callback.
         *
         * @since 1.0
         *
         * @global wpdb  $wpdb    WordPress database abstraction object.
         *
         * @param string $option  Name of option to retrieve. Expected to not be SQL-escaped.
         * @param mixed  $default Optional. Default value to return if the option does not exist.
         *
         * @return mixed Value set for the option.
         */
        function wcff_get_option( $option, $default = false ) {
            $option = trim( $option );

            if ( empty( $option ) ) {
                return false;
            }

            return get_option( WCFF_OPTIONS_PREFIX . $option, $default );
        }
    endif;

    if ( ! function_exists( 'wcff_add_option' ) ):
        /**
         * Add a new option.
         *
         * You do not need to serialize values. If the value needs to be serialized, then
         * it will be serialized before it is inserted into the database. Remember,
         * resources can not be serialized or added as an option.
         *
         * You can create options without values and then update the values later.
         * Existing options will not be updated and checks are performed to ensure that you
         * aren't adding a protected WordPress option. Care should be taken to not name
         * options the same as the ones which are protected.
         *
         * @since 1.0
         *
         * @global wpdb       $wpdb           WordPress database abstraction object.
         *
         * @param string      $option         Name of option to add. Expected to not be SQL-escaped.
         * @param mixed       $value          Optional. Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
         * @param string      $deprecated     Optional. Description. Not used anymore.
         * @param string|bool $autoload       Optional. Whether to load the option when WordPress starts up.
         *                                    Default is enabled. Accepts 'no' to disable for legacy reasons.
         *
         * @return bool False if option was not added and true if option was added.
         */
        function wcff_add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
            $option = trim( $option );

            if ( empty( $option ) ) {
                return false;
            }

            return add_option( WCFF_OPTIONS_PREFIX . $option, $value, $deprecated, $autoload );
        }
    endif;

    if ( ! function_exists( 'wcff_sanitize' ) ):
        /**
         * Sanitize the string.
         *
         * @param mixed  $string   The string to sanitize
         * @param string $function The function to sanitize. (Default: sanitize_text_field)
         */
        function wcff_sanitize( $string, $function = 'sanitize_text_field' ) {
            switch ( $function ) {
                case 'none':
                default:
                    return $string;
                case 'intval':
                    return intval( $string );
                case 'absint':
                    return absint( $string );
                case 'wp_kses_post':
                    return wp_kses_post( $string );
                case 'wp_kses_data':
                    return wp_kses_data( $string );
                case 'esc_url_raw':
                    return esc_url_raw( $string );
                case 'is_email':
                    return is_email( $string );
                case 'sanitize_title':
                    return sanitize_title( $string );
                case 'santitize_boolean':
                    return santitize_boolean( $string );
                case 'sanitize_text_field':
                    return sanitize_text_field( $string );
                case 'sanitize_textarea_field':
                    return sanitize_textarea_field( $string );
            }
        }
    endif;

    if ( ! function_exists( 'wcff_array_map_r' ) ):

        /**
         * Run recrusive trhough the array.
         */
        function wcff_array_map_r( $func, $meta, $sanitizer ) {
            $newMeta = array();
            $meta    = array_values( $meta );

            foreach ( $meta as $key => $array ) {
                if ( $array == '' ) {
                    continue;
                }

                /**
                 * some values are stored as array, we only want multidimensional ones
                 */
                if ( ! is_array( $array ) ) {
                    return array_map( $func, $meta, (array) $sanitizer );
                    break;
                }

                /**
                 * the sanitizer will have all of the fields, but the item may only
                 * have values for a few, remove the ones we don't have from the santizer
                 */
                $keys         = array_keys( $array );
                $newSanitizer = $sanitizer;
                if ( is_array( $sanitizer ) ) {
                    foreach ( $newSanitizer as $sanitizerKey => $value ) {
                        if ( ! in_array( $sanitizerKey, $keys ) ) {
                            unset( $newSanitizer[ $sanitizerKey ] );
                        }
                    }
                }
                /**
                 * run the function as deep as the array goes
                 */
                foreach ( $array as $arrayKey => $arrayValue ) {
                    if ( is_array( $arrayValue ) ) {
                        $array[ $arrayKey ] = wcff_array_map_r( $func, $arrayValue, $newSanitizer[ $arrayKey ] );
                    }
                }

                $array           = array_map( $func, $array, $newSanitizer );
                $newMeta[ $key ] = array_combine( $keys, array_values( $array ) );
            }

            return $newMeta;
        }
    endif;

    if ( ! function_exists( 'wcff_update_option' ) ):
        /**
         * Update the value of an option that was already added.
         *
         * You do not need to serialize values. If the value needs to be serialized, then
         * it will be serialized before it is inserted into the database. Remember,
         * resources can not be serialized or added as an option.
         *
         * If the option does not exist, then the option will be added with the option value,
         * with an `$autoload` value of 'yes'.
         *
         * @since 1.0
         *
         * @global wpdb       $wpdb     WordPress database abstraction object.
         *
         * @param string      $option   Option name. Expected to not be SQL-escaped.
         * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
         * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
         *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
         *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
         *                              the default value is 'yes'. Default null.
         *
         * @return bool False if value was not updated and true if value was updated.
         */
        function wcff_update_option( $option, $value, $autoload = null ) {
            $option = trim( $option );

            if ( empty( $option ) ) {
                return false;
            }

            return update_option( WCFF_OPTIONS_PREFIX . $option, $value, $autoload );
        }
    endif;

    if ( ! function_exists( 'wcff_version' ) ):
        /**
         * Get the installed and current version of this plugin.
         *
         * @return array Associative array with the versions.
         */
        function wcff_version() {
            return wcff_apply_filters( 'version', array(
                'installed' => wcff_get_option( 'version', '0' ),
                'current'   => WCFF_VERSION_NUM,
            ) );
        }
    endif;

    if ( ! function_exists( 'wcff_log' ) ):
        /**
         * Write directly into the log file. Logging will be activated and after write deactivated.
         *
         * @param mixed  $data Data to write into file. Can be a array, object, string etc.
         * @param string $id   The ID of this entry. Usefull to distinguish the entries.
         *
         * @return bool True if is written into log, otherwise false.
         */
        function wcff_log( $data, $id = '' ) {
            $logger = WCFF_Logger::getInstance();

            $logger->activate();
            $isSuccessfull = $logger->write( $data, $id );
            $logger->deactivate();

            return $isSuccessfull;
        }
    endif;

    if ( ! function_exists( 'wcff_array_column_recursive' ) ):
        /**
         * @param array  $haystack A multi-dimensional array or an array of objects from which to pull a column of values from.
         * @param string $needle   Needle to search in the haystack.
         * @param bool   $unique   The found values should only unique in the result array.
         *
         * @return null|array Returns an array of values representing a single column from the input array.
         */
        function wcff_array_column_recursive( $haystack, $needle, $unique = true ) {
            $found = array();

            if ( ! is_array( $haystack ) ) {
                return $found;
            }

            array_walk_recursive( $haystack, function ( $value, $key ) use ( &$found, $needle, $unique ) {
                if ( $key == $needle ) {
                    if ( $unique === true && in_array( $value, $found ) ) {
                        return;
                    }

                    $found[] = $value;
                }
            } );

            return $found;
        }
    endif;

    if ( ! function_exists( 'wcff_is_domain' ) ):
        /**
         * Check if a valid domain is in string.
         *
         * @param string $domain The domain.
         */
        function wcff_is_domain( $domain ) {
            $pattern = '/(http[s]?\:\/\/)?(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}/';

            return preg_match( $pattern, $domain );
        }
    endif;

    if ( ! function_exists( 'wcff_render_stars' ) ):

        /**
         * Render stars HTML.
         *
         * @param int  $selected_stars The selected stars. (Default: 0)
         * @param int  $max_stars      Max. number of stars. (Default: 5)
         * @param bool $echo           Return or echo HTML. (Default: true)
         *
         * @return string If $echo is false, HTML code will be returned.
         */
        function wcff_render_stars( $selected_stars = 0, $max_stars = 5, $echo = true ) {
            if ( empty( $max_stars ) ) {
                $max_stars = 5;
            }

            if ( empty( $selected_stars ) || $selected_stars < 0 ) {
                $selected_stars = 0;
            }

            if ( $selected_stars > $max_stars ) {
                $selected_stars = $max_stars;
            }

            $html = '<div class="sf-rate-stars">';

            for ( $i = 0; $i <= $max_stars; $i++ ) {
                $html .= '<span ' . ( $selected_stars > $i ? 'class="checked"' : '' ) . '></span>';
            }

            $html .= '</div>';

            if ( ! $echo ) {
                return $html;
            }

            echo $html;

        }
    endif;


    if ( ! function_exists( 'wcff_render_term_badge' ) ):

        /**
         * Render the taxonomy badges HTML.
         *
         * @param int|WP_Post $post     ID of the post or the object.
         * @param string      $taxonomy The taxonomy name.
         * @param bool        $echo     Return or echo HTML. (Default: true)
         *
         * @return null|string If $echo is false, HTML code will be returned. If error occurs, null will be returned. No matter what $echo is set to.
         */
        function wcff_render_term_badge( $post, $taxonomy, $echo = true ) {
            if ( empty( $post ) || empty( $taxonomy ) ) {
                return null;
            }

            $terms = get_the_terms( $post, $taxonomy );

            if ( empty( $terms ) ) {
                return null;
            }

            $html = '<div class="sf-badge-wrap">';

            foreach ( $terms as $term ) {
                $html .= sprintf( '<span class="sf-badge taxonomy-%s %s">%s</span>', esc_attr( $taxonomy ), esc_attr( $term->slug ), wp_strip_all_tags( $term->name ) );
            }

            $html .= '</div>';

            if ( ! $echo ) {
                return $html;
            }

            echo $html;
        }
    endif;

    if ( ! function_exists( 'wcff_get_post_meta' ) ):
        /**
         * Get the post meta field.
         *
         * @param int    $post_id Post ID.
         * @param string $key     The meta key to retrieve. The option prefix of this plugin will be automatically set as prefix.
         * @param bool   $single  Optional. If true, returns only the first value for the specified meta key.
         *                        This parameter has no effect if $key is not specified. Default false.
         *
         * @return mixed Will be an array if $single is false. Will be value of the meta field if $single is true.
         */
        function wcff_get_post_meta( $post_id, $key, $single = true ) {
            if ( empty( $post_id ) || empty( $key ) ) {
                return false;
            }

            return get_post_meta( $post_id, WCFF_OPTIONS_PREFIX . $key, $single );
        }
    endif;