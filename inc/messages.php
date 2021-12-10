<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WCFF_Messages' ) ):

	class WCFF_Messages extends WCFF_Singleton {
		private $message_container;
		private $message_item_container;

		/**
		 * BestLMS_Flash_Message constructor.
		 */
		protected function __construct() {
			$this->setMessageContainer( '<div class="%1$s"><ol>%2$s</ol></div>' );
			$this->setItemContainer( '<li>%1$s</li>' );
		}

		/**
		 * Adds a new message.
		 *
		 * @param string $type          Optional. Type of this message.
		 * @param string|array $message Array with messages or a message as string.
		 *
		 * @return bool If message stored true, otherwise false.
		 */
		private function add( $message, $type ) {
			if ( empty( $type ) || empty( $message ) ) {
				return false;
			}

			// Add the type to array, if key not exists
			if ( ! $this->hasType( $type ) ) {
				$_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ] = array();
			}

			if ( is_array( $message ) ) {
				$_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ] = array_merge( $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ], $message );
			} else {
				$_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ][] = $message;
			}

			return true;
		}

		/**
		 * Add a success message.
		 *
		 * @param string $message Text to show.
		 *
		 * @return bool True if stored, otherwise false.
		 */
		public function addSuccess( $message ) {
			return $this->add( $message, 'success' );
		}

		/**
		 * Add a success error.
		 *
		 * @param string $message Text to show.
		 *
		 * @return bool True if stored, otherwise false.
		 */
		public function addError( $message ) {
			return $this->add( $message, 'error' );
		}

		/**
		 * Add a information message.
		 *
		 * @param string $message Text to show.
		 *
		 * @return bool True if stored, otherwise false.
		 */
		public function addInfo( $message ) {
			return $this->add( $message, 'info' );
		}

		/**
		 * Add a warning message.
		 *
		 * @param string $message Text to show.
		 *
		 * @return bool True if stored, otherwise false.
		 */
		public function addWarning( $message ) {
			return $this->add( $message, 'warning' );
		}


		/**
		 * Check if a specific type is in the flash messages.
		 *
		 * @param string $type The flash message type
		 *
		 * @return bool True if type is set, otherwise false. Null if the type is wrong.
		 */
		private function hasType( $type = null ) {
			if ( empty( $type ) ) {
				return ! empty( $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'] );
			}

			return ! empty( $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ] );
		}

		/**
		 * Check if success messages stored in queue.
		 *
		 * @return bool True at least one message stored, otherwise false.
		 */
		public function hasSuccess() {
			return $this->hasType( 'success' );
		}

		/**
		 * Check if error messages stored in queue.
		 *
		 * @return bool True at least one message stored, otherwise false.
		 */
		public function hasError() {
			return $this->hasType( 'error' );
		}

		/**
		 * Check if information messages stored in queue.
		 *
		 * @return bool True at least one message stored, otherwise false.
		 */
		public function hasInfo() {
			return $this->hasType( 'info' );
		}

		/**
		 * Check if warnung messages stored in queue.
		 *
		 * @return bool True at least one message stored, otherwise false.
		 */
		public function hasWarning() {
			return $this->hasType( 'warning' );
		}

		/**
		 * Check if any message is set.
		 *
		 * @return bool true if messages available, otherwise false.
		 */
		public function hasMessages() {
			return $this->hasType();
		}

		/**
		 * Returns the stored messages.
		 *
		 * @return array|null Null if nothing found. Otherwise a array with all messages.
		 */
		public function getMessages() {
			return $this->getMessage();
		}

		/**
		 * Get all messages or all messages of a specific type.
		 *
		 * @param string|null $type Type of message. If empty, all messages. (Default: null)
		 *
		 * @return array Array with all messages or all of a specific type. Empty array if no messages stored.
		 */
		private function getMessage( $type = null ) {
			if ( ! $this->hasMessages() ) {
				return array();
			}

			if ( empty( $type ) ) {
				return $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'];
			}

			return $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ];
		}

		/**
		 * Get all success messages.
		 *
		 * @return array Array with all messages. Empty array if no messages stored.
		 */
		public function getSuccess() {
			return $this->getMessage( 'success' );
		}

		/**
		 * Get all error messages.
		 *
		 * @return array Array with all messages. Empty array if no messages stored.
		 */
		public function getError() {
			return $this->getMessage( 'error' );
		}

		/**
		 * Get all information messages.
		 *
		 * @return array Array with all messages. Empty array if no messages stored.
		 */
		public function getInfo() {
			return $this->getMessage( 'info' );
		}

		/**
		 * Get all warning messages.
		 *
		 * @return array Array with all messages. Empty array if no messages stored.
		 */
		public function getWarning() {
			return $this->getMessage( 'warning' );
		}

		/**
		 * Clears all messages or just messages according to the type.
		 *
		 * @return bool True on success, otherwise false.
		 */
		public function clear() {
			return $this->clearType();
		}

		/**
		 * Clear all messages or all messages of a specific type.
		 *
		 * @param string|null $type Type of message. If empty, all messages. (Default: null)
		 *
		 * @return bool True if cleared, otherwise false.
		 */
		private function clearType( $type = null ) {
			if ( empty( $type ) ) {
				unset( $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'] );

				return true;
			}

			unset( $_SESSION[ WCFF_OPTIONS_PREFIX ]['messages'][ $type ] );

			return true;
		}

		/**
		 * Clear all success messages.
		 *
		 * @return bool True if cleared, otherwise false.
		 */
		public function clearSuccess() {
			return $this->clearType( 'success' );
		}

		/**
		 * Clear all error messages.
		 *
		 * @return bool True if cleared, otherwise false.
		 */
		public function clearError() {
			return $this->clearType( 'error' );
		}

		/**
		 * Clear all warning messages.
		 *
		 * @return bool True if cleared, otherwise false.
		 */
		public function clearWarning() {
			return $this->clearType( 'warning' );
		}

		/**
		 * Clear all information messages.
		 *
		 * @return bool True if cleared, otherwise false.
		 */
		public function clearInfo() {
			return $this->clearType( 'info' );
		}

		/**
		 * Render all messages or a specific type of message.
		 *
		 * @param null|string $type Optional. If only messages from a certain type needed. Otherwise all types will be returned. (Default: null)
		 *
		 * @return string Return the HTML-Code if $echo set to false.
		 */
		private function renderType( $type = null ) {
			if ( ! $this->hasMessages() ) {
				return '';
			}

			if ( is_null( $type ) ) {
				// Render all messages
				$html = '';

				foreach ( $this->getMessages() as $type => &$messages ) {
					$html .= sprintf( $this->getMessageContainer(), 'ldforum-messages ldforum-message-' . $type, $this->renderItems( $messages ) );
				}
			} else {
				// Render only specific type
				$html = sprintf( $this->getMessageContainer(), 'ldforum-messages ldforum-message-' . $type, $this->renderItems( $this->getMessages( $type ) ) );
			}

			$this->clear();

			return $html;
		}

		/**
		 * Render all messages.
		 *
		 * @param bool $echo Return or echo the output. (Default: false)
		 *
		 * @return string HTML-Code if $echo is false.
		 */
		public function render( $echo = false ) {
			if ( empty( $echo ) ) {
				return $this->renderType();
			}

			echo $this->renderType();
		}

		/**
		 * Render all success messages.
		 *
		 * @param bool $echo Return or echo the output. (Default: false)
		 *
		 * @return string HTML-Code if $echo is false.
		 */
		public function renderSuccess( $echo = false ) {
			if ( empty( $echo ) ) {
				return $this->renderType( 'success' );
			}

			echo $this->renderType( 'success' );
		}

		/**
		 * Render all error messages.
		 *
		 * @param bool $echo Return or echo the output. (Default: false)
		 *
		 * @return string HTML-Code if $echo is false.
		 */
		public function renderError( $echo = false ) {
			if ( empty( $echo ) ) {
				return $this->renderType( 'error' );
			}

			echo $this->renderType( 'error' );
		}

		/**
		 * Render all information messages.
		 *
		 * @param bool $echo Return or echo the output. (Default: false)
		 *
		 * @return string HTML-Code if $echo is false.
		 */
		public function renderInfo( $echo = false ) {
			if ( empty( $echo ) ) {
				return $this->renderType( 'info' );
			}

			echo $this->renderType( 'info' );
		}

		/**
		 * Render all warning messages.
		 *
		 * @param bool $echo Return or echo the output. (Default: false)
		 *
		 * @return string HTML-Code if $echo is false.
		 */
		public function renderWarning( $echo = false ) {
			if ( empty( $echo ) ) {
				return $this->renderType( 'warning' );
			}

			echo $this->renderType( 'warning' );
		}

		/**
		 * Returns the HTML-Code for message container.
		 *
		 * @return string HTML-Code
		 */
		public function getMessageContainer() {
			return $this->message_container;
		}

		/**
		 * Set the HTML-Code for the message container.<br/>
		 * <strong>Example:</strong> &lt;div class="%1$s">&lt;ul>%2$s&lt;/ul>&lt;/div>
		 *
		 * @param string $html HTML-Code
		 */
		public function setMessageContainer( $html ) {
			$this->message_container = $html;
		}

		/**
		 * Returns the HTML-Code for the item, inside of the message container.
		 *
		 * @return string HTML-Code
		 */
		public function getItemContainer() {
			return $this->message_item_container;
		}

		/**
		 * Set the HTML-Code for the item, inside of the message container.<br/>
		 * <strong>Example:</strong> &lt;li>%1$s&lt;/li>
		 *
		 * @param string $html HTML-Code
		 */
		public function setItemContainer( $html ) {
			$this->message_item_container = $html;
		}

		/**
		 * Returns the generated HTML-Code as a string
		 *
		 * @return string HTML-Code
		 */
		public function __toString() {
			return $this->render();
		}

		/**
		 * Render the items of the flash message.
		 *
		 * @param array $messages Array with all messages
		 *
		 * @return string HTML-Code with rendered items.
		 */
		private function renderItems( &$messages ) {
			$html = '';

			foreach ( $messages as $message ) {
				$html .= sprintf( $this->getItemContainer(), $message );
			}

			return $html;
		}
	}

endif;