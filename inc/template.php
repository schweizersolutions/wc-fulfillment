<?php

    // If this file is called directly, abort.
    if ( ! defined( 'WPINC' ) ) {
        die;
    }

    if ( ! class_exists( 'WCFF_Template' ) ):

        class WCFF_Template {
            private $template_theme_path;
            private $template_plugin_path;
            private $template_vars;
            private $template_name;
            private $template_partial;
            private $template_blacklist_vars;

            /**
             * WPAPlayer_Template constructor.
             * Initialize the class variables.
             *
             * @param string      $name    Name of the template. (Default: null)
             * @param string|null $partial Name of the partial. (Default: null)
             * @param array       $vars    Associative array with name of variable and the value.
             */
            public function __construct( $name = null, $partial = null, $vars = array() ) {
                $this->template_blacklist_vars = array( 'template_blacklist_vars', 'template_plugin_path', 'template_theme_path', 'template_vars', 'template_name', 'template_partial' );
                $this->template_theme_path     = ( is_child_theme() ? get_stylesheet_directory() : get_template_directory() ) . '/' . WCFF_PLUGIN_NAME . '/';
                $this->template_theme_path     = wcff_apply_filters( 'template_theme_path', $this->template_theme_path );

                $this->template_plugin_path = WCFF_PLUGIN_DIR . '/templates/';
                $this->template_plugin_path = wcff_apply_filters( 'template_plugin_path', $this->template_plugin_path );

                $this->setVars( $vars );

                $this->template_name    = ( empty( $name ) ? null : $name );
                $this->template_partial = ( empty( $partial ) ? null : $partial );
            }

            /**
             * Render the template or the template partial.
             * First it checks if the file exists in the theme folder
             * (wp-content/themes/<theme>/<plugin_name>/) and then in the plugin template folder.
             *
             * If the partial not exists, it will fallback to the template ($name).
             * If no name is given, it will use the name from constructor.
             *
             * @param string      $name    Name of the template. (Default: null)
             * @param string|null $partial Name of the partial. (Default: null)
             * @param array       $vars    Associative array with name of variable and the value.
             *
             * @return string|bool HTML-Code on success, otherwise false.
             */
            public function render( $name = null, $partial = null, $vars = array() ) {
                if ( empty( $name ) ) {
                    $name = $this->template_name;
                }

                if ( empty( $partial ) ) {
                    $partial = $this->template_partial;
                }

                if ( empty( $name ) ) {
                    return false;
                }

                if ( ! empty( $vars ) ) {
                    $this->setVars( $vars );
                }

                $template_names = array();

                if ( ! empty( $partial ) ) {
                    $template_names[] = "{$name}-{$partial}.php";
                }

                $template_names[] = "{$name}.php";
                $located          = $this->locateTemplate( $template_names );

                if ( empty( $located ) ) {
                    return false;
                }

                if ( ! is_array( $this->template_vars ) ) {
                    $this->template_vars = array();
                }

                wcff_do_action( 'template_render', $name, $partial );

                ob_start();
                extract( $this->template_vars, EXTR_SKIP );
                require( $located );
                $template = ob_get_contents();
                @ob_end_clean();

                return wcff_apply_filters( 'template_rendered', $template, $name, $partial );
            }

            /**
             * Render the template from the admin folder.
             * It wraps the render() method and prepend "admin" with directory separator to the $name.
             *
             * @param string      $name    Name of the template.
             * @param string|null $partial Name of the partial. (Default: null)
             * @param array       $vars    Associative array with name of variable and the value.
             *
             * @return string|bool HTML-Code on success, otherwise false.
             */
            public function renderAdmin( $name = null, $partial = null, $vars = array() ) {
                if ( empty( $name ) ) {
                    $name = $this->template_name;
                }

                return $this->render( 'admin/' . $name, $partial, $vars );
            }

            /**
             * Render the template from the public folder.
             * It wraps the render() method and prepend "public" with directory separator to the $name.
             *
             * @param string      $name    Name of the template.
             * @param string|null $partial Name of the partial. (Default: null)
             * @param array       $vars    Associative array with name of variable and the value.
             *
             * @return string|bool HTML-Code on success, otherwise false.
             */
            public function renderPublic( $name = null, $partial = null, $vars = array() ) {
                if ( empty( $name ) ) {
                    $name = $this->template_name;
                }

                return $this->render( 'public/' . $name, $partial, $vars );
            }

            /**
             * Get the location of the template.
             * It checks the array from to bottm. First it checks if the file exists in the theme folder
             * (wp-content/themes/<theme>/<plugin_name>/) and then in the plugin template folder.
             *
             * @param array $templates_names Array with template names, with file extension.
             *
             * @return null|string Full path to template if found. If not, then null.
             */
            public function locateTemplate( $templates_names ) {
                $located         = null;
                $templates_names = wcff_apply_filters( 'template_locate', $templates_names );

                foreach ( (array) $templates_names as $template_name ) {
                    if ( empty( $template_name ) ) {
                        continue;
                    }

                    // First check theme
                    if ( file_exists( $this->template_theme_path . $template_name ) ) {
                        $located = $this->template_theme_path . $template_name;
                        break;
                    }

                    // 2nd check plugin
                    if ( file_exists( $this->template_plugin_path . $template_name ) ) {
                        $located = $this->template_plugin_path . $template_name;
                        break;
                    }
                }

                return wcff_apply_filters( 'template_located', $located );
            }


            /**
             * Get the values of given variables.
             *
             * @param array $vars An array with variable names.
             *
             * @return array|null Associative array with the name and the value of the variables.
             */
            public function getVars( $vars ) {
                if ( ! is_array( $vars ) ) {
                    return null;
                }

                foreach ( $vars as $name ) {
                    $vars[ $name ] = $this->get( $name );
                }

                return $vars;
            }

            /**
             * Return the value of a variable.
             *
             * @param  string $name Name of the variable
             *
             * @return  mixed Return the value or NULL if variable does not exist.
             *
             */
            public function get( $name ) {
                if ( empty( $name ) ) {
                    return null;
                }

                return isset( $this->template_vars[ $name ] ) ? wcff_apply_filters( 'template_get_var_' . $name, $this->template_vars[ $name ] ) : null;
            }

            /**
             * Return the value of a variable.
             *
             * @param  string $name Name of the variable
             *
             * @return  mixed Return the value or NULL if variable does not exist
             *
             */
            public function __get( $name ) {
                return $this->get( $name );
            }

            /**
             * Populate the template variabales from a associative array.
             *
             * @param array $vars      Associative array with name of variable and the value.
             * @param bool  $overwrite Overwrite value if variable is set or not. (Default: true)
             *
             * @return bool True is set successfully, otherwise false.
             */
            public function setVars( $vars, $overwrite = true ) {
                if ( ! is_array( $vars ) ) {
                    return false;
                }

                foreach ( $vars as $name => $value ) {
                    if ( ! is_string( $name ) ) {
                        continue;
                    }

                    // Don't overwrite value if variable already set.
                    if ( ! $overwrite && $this->isset( $name ) ) {
                        continue;
                    }

                    $this->set( $name, $value );
                }

                return true;
            }

            /**
             * Set a variable to the template
             *
             * @param  string $name      Name of the variable
             * @param  mixed  $value     Value of the variable
             * @param bool    $overwrite Overwrite value if variable is set or not. (Default: true)
             *
             * @return bool True if set, otherwise false.
             */
            public function set( $name, $value, $overwrite = true ) {
                if ( empty( $name ) ) {
                    return false;
                }

                // Don't overwrite value if variable already set.
                if ( ! $overwrite && $this->isset( $name ) ) {
                    return true;
                }

                if ( ! in_array( $name, $this->template_blacklist_vars ) ) {
                    $this->template_vars[ $name ] = wcff_apply_filters( 'template_set_var_' . $name, $value );

                    return true;
                }

                return false;

            }

            /**
             * Set a variable to the template
             *
             * @param  string $name  Name of the variable
             * @param  mixed  $value Value of the variable
             */
            public function __set( $name, $value ) {
                $this->set( $name, $value );
            }

            /**
             * Unset given variables.
             *
             * @param array $vars An array with variable names.
             *
             * @return bool True on success, otherwise false.
             */
            public function unsetVars( $vars ) {
                if ( ! is_array( $vars ) ) {
                    return false;
                }

                foreach ( $vars as $name ) {
                    $this->unset( $name );
                }

                return true;
            }

            /**
             * Unset a variable to the template
             *
             * @param string $name Name of the variable
             *
             * @return bool True on success, otherwise false.
             */
            public function unset( $name ) {
                if ( empty( $name ) ) {
                    return false;
                }

                if ( ! in_array( $name, $this->template_blacklist_vars ) ) {
                    wcff_do_action( 'template_unset_var_' . $name, $this->template_vars[ $name ] );
                    unset( $this->template_vars[ $name ] );

                    return true;
                }

                return false;
            }

            /**
             * Unset a variable to the template
             *
             * @param string $name Name of the variable
             */
            public function __unset( $name ) {
                $this->unset( $name );
            }


            /**
             * Check if given variables are set or not.
             *
             * @param array $vars An array with variable names.
             *
             * @return array|bool Associative array with bools on success, otherwise false.
             */
            public function issetVars( $vars ) {
                if ( is_array( $vars ) ) {
                    return false;
                }

                $isset = array();

                foreach ( $vars as $name ) {
                    $isset[ $name ] = $this->isset( $name );
                }

                return $isset;
            }

            /**
             * Checks if a variable exists.
             *
             * @param string $name Name of the variable.
             *
             * @return bool true if value exist, otherwise false
             */
            public function isset( $name ) {
                if ( empty( $name ) ) {
                    return false;
                }

                return wcff_apply_filters( 'template_isset_var_' . $name, isset( $this->template_vars[ $name ] ) );
            }

            /**
             * Checks if a variable exists.
             *
             * @param string $name Name of the variable.
             *
             * @return bool true if value exist, otherwise false
             */
            public function __isset( $name ) {
                return $this->isset( $name );
            }

            /**
             * Convert the object to string and output the HTML-Code.
             * @return string HTML-Code or a empty string.
             */
            public function __toString() {
                $string = $this->render();

                return empty( $string ) ? '' : $string;
            }
        }

    endif;