<?php
/**
 * Compatiblity with Multi-Step Checkout for WooCommerce
 * by https://www.themehigh.com/
 *
 * @package Iconic_WDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compatiblity with Multi-Step Checkout for WooCommerce.
 */
class Iconic_WDS_Compat_Multistep_Checkout {
	/**
	 * Run.
	 */
	public static function run() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'update_checkout_fields' ), 11, 1 );
	}

	/**
	 * Set 'required' parameter false if iconic-wds-fields-hidden is true.
	 *
	 * @param array $fields Checkout fields.
	 *
	 * @return array
	 */
	public static function update_checkout_fields( $fields ) {
		if ( ! isset( $fields['jckwds'] ) ) {
			return $fields;
		}

		$posted = filter_input( INPUT_POST, 'posted', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $posted['iconic-wds-fields-hidden'] ) || '1' !== $posted['iconic-wds-fields-hidden'] ) {
			return $fields;
		}

		foreach ( $fields['jckwds'] as $key => &$field ) {
			$field['required'] = 0;
		}

		return $field;
	}
}
