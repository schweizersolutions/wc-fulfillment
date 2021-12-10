<?php

    defined( 'WPINC' ) || die; // If this file is called directly, abort.

    /**
     * WCFF_Interface_ActionHook is used by an object that needs to subscribe to
     * WordPress action hooks.
     */
    interface WCFF_Interface_ActionHook {
        /**
         * Returns an array of actions that the object needs to be subscribed to.
         *
         * The array key is the name of the action hook. The value can be:
         *
         *  * The method name
         *  * An array with the method name and priority
         *  * An array with the method name, priority and number of accepted arguments
         *
         * For instance:
         *
         *  * array('action_name' => 'method_name')
         *  * array('action_name' => array('method_name', $priority))
         *  * array('action_name' => array('method_name', $priority, $accepted_args))
         *
         * @return array
         */
        public static function getActions();
    }