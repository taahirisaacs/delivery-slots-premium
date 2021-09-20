<?php
/**
 * Compatiblity with bootstrap datepicker.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Compatiblity with bootstrap datepicker.
 *
 * @version  1.0.0
 */
class Iconic_WDS_Compat_Bootstrap_Date {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'wp_footer', array( __CLASS__, 'remove_bootstrap_datepicker' ), 1 );
	}

	/**
	 * Remove bootstrap datepicker on checkout page
	 * because the theme implementation overrides our
	 * jqueryui datepicker settings
	 */
	public static function remove_bootstrap_datepicker() {
		if ( is_checkout() ) {
			wp_deregister_script( 'bootstrap-datepicker' );
			wp_dequeue_script( 'bootstrap-datepicker' );
		}
	}
}
