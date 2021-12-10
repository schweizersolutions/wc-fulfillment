<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WCFF_Assets' ) ):

	class WCFF_Assets {
		/**
		 * Get URL or filepath to image file.
		 *
		 * @param string $filename Name of the file. If it is in a sub-dir, just type 'sub-dir1/sub-dir2/filename.png' as filename.
		 * @param bool $return_url Return URL or file path. (Default: true)
		 *
		 * @return bool|null|string If something went wrong, false. NULL if no file found. Otherwise URL or path to file.
		 */
		public static function getImg( $filename, $return_url = true ) {
			if ( empty( $filename ) ) {
				return false;
			}

			if ( ! file_exists( WCFF_PLUGIN_DIR . '/assets/img/' . $filename ) ) {
				return null;
			}

			return ( $return_url ? WCFF_PLUGIN_URL : WCFF_PLUGIN_DIR ) . '/assets/img/' . $filename;
		}

		/**
		 * Get <img> HTML-Code of the image.
		 *
		 * @param string $filename Name of the file. If it is in a sub-dir, just type 'sub-dir1/sub-dir2/filename.png' as filename.
		 * @param array $atts      Associative array for the HTML attributes.
		 *
		 * @return bool|string False if something went wrong, otherwise the HTML-Code.
		 */
		public static function getImgHTML( $filename, $atts = array() ) {
			if ( empty( $filename ) ) {
				return false;
			}

			$url = self::getImg( $filename );

			if ( empty( $url ) ) {
				return false;
			}

			$img = '<img src="' . $url . '"';

			// Convert associative array to HTML attributes
			if ( ! empty( $atts ) && is_array( $atts ) ) {
				foreach ( $atts as $name => $value ) {
					if ( is_bool( $value ) && $value ) {
						$img .= ' ' . $name;
						continue;
					}

					$img .= ' ' . $name . '="' . $value . '"';
				}
			}

			return $img . '>';
		}

		/**
		 * Enqueue a script of this plugin which is located in "/assets/js/".
		 *
		 * @since 1.0
		 * @see   wp_enqueue_script
		 *
		 * @param string $handle              Name of the script. Should be unique.
		 * @param string $src                 Full URL of the script, or path of the script relative to the Plugin root directory. (Default: empty string)
		 * @param array $deps                 Optional. An array of registered script handles this script depends on. (Default; empty array)
		 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
		 *                                    as a query string for cache busting purposes. If version is set to false, a version
		 *                                    number is automatically added equal to current installed WordPress version.
		 *                                    If set to null, no version is added.
		 * @param bool $in_footer             Optional. Whether to enqueue the script before </body> instead of in the <head>. (Default: false)
		 */
		public static function enqueueScript( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
			wp_enqueue_script( $handle, WCFF_PLUGIN_URL . '/assets/js/' . $src, $deps, $ver, $in_footer );
		}

		/**
		 * Enqueue a CSS style file of this plugin which is located in "/assets/css/".
		 *
		 * @since 1.0
		 * @see   wp_enqueue_style
		 *
		 * @param string $handle   Name of the stylesheet.
		 * @param string|bool $src Path to the stylesheet from the "/assets/css/" directory of Plugin. (Default: empty string)
		 * @param array $deps      Array of handles (names) of any stylesheet that this stylesheet depends on.
		 *                         (Stylesheets that must be loaded before this stylesheet.) Pass an empty array if there are no dependencies.
		 * @param string|bool $ver String specifying the stylesheet version number, if it has one. This parameter
		 *                         is used to ensure that the correct version is sent to the client regardless of caching, and so should be included
		 *                         if a version number is available and makes sense for the stylesheet.
		 * @param string $media    The media for which this stylesheet has been defined.
		 */
		public static function enqueueStyle( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
			wp_enqueue_style( $handle, WCFF_PLUGIN_URL . '/assets/css/' . $src, $deps, $ver, $media );
		}

		/**
		 * Register new JavaScript file of this plugin which is located in "/assets/js/".
		 *
		 * @since 1.0
		 * @see   wp_register_script
		 *
		 * @param string $handle   Script name
		 * @param string $src      Script url
		 * @param array $deps      (optional) Array of script names on which this script depends
		 * @param string|bool $ver (optional) Script version (used for cache busting), set to NULL to disable
		 * @param bool             (optional) Wether to enqueue the script before </head> or before </body>
		 */
		public static function registerScript( $handle, $src, $deps = array(), $ver = false, $in_footer = false ) {
			wp_register_script( $handle, WCFF_PLUGIN_URL . '/assets/js/' . $src, $deps, $ver, $in_footer );
		}

		/**
		 * Register a CSS stylesheet of this plugin which is located in "/assets/css/".
		 *
		 * @since 1.0
		 * @see   wp_register_style
		 *
		 * @param string $handle           Name of the stylesheet. Should be unique.
		 * @param string $src              Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
		 * @param array $deps              Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
		 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
		 *                                 as a query string for cache busting purposes. If version is set to false, a version
		 *                                 number is automatically added equal to current installed WordPress version.
		 *                                 If set to null, no version is added.
		 * @param string $media            Optional. The media for which this stylesheet has been defined.
		 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
		 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
		 *
		 * @return bool Whether the style has been registered. True on success, false on failure.
		 */
		public static function registerStyle( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
			return wp_register_style( $handle, WCFF_PLUGIN_URL . '/assets/css/' . $src, $deps, $ver, $media );
		}

		/**
		 * Remove a registered script.
		 *
		 * Note: there are intentional safeguards in place to prevent critical admin scripts,
		 * such as jQuery core, from being unregistered.
		 *
		 * @since 1.0
		 * @see   wp_deregister_script, wp_script_is
		 *
		 * @param string $handle Name of the script to be removed.
		 * @param string $list   Optional, defaults to 'queue'. Others values are 'registered', 'queue', 'done', 'to_do' (Default: registered)
		 *
		 * @return bool False if script is not registered or not in "$list". Otherwise true.
		 */
		public static function deregisterScript( $handle, $list = 'registered' ) {
			if ( ! wp_script_is( $handle, $list ) ) {
				return false;
			}

			wp_deregister_script( $handle );

			return true;
		}

		/**
		 * Remove a registered stylesheet.
		 *
		 * @since 1.0
		 * @see   wp_deregister_style,wp_style_is
		 *
		 * @param string $handle  Name of the stylesheet to be removed.
		 * @param string $list    Optional. Status of the stylesheet to check. (Default: 'registred')
		 *                        Accepts 'enqueued', 'registered', 'queue', 'to_do', and 'done'.
		 *
		 * @return bool False if script is not registered or not in "$list". Otherwise true.
		 */
		public static function deregisterStyle( $handle, $list = 'registered' ) {
			if ( ! wp_style_is( $handle, $list ) ) {
				return false;
			}

			wp_deregister_style( $handle );

			return true;
		}

	}

endif;