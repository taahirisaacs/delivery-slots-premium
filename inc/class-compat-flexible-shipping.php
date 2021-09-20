<?php
/**
 * Compatiblity with Flexible Shipping plugin.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Compatiblity with Flexible Shipping plugin.
 * https://wordpress.org/plugins/flexible-shipping/
 *
 * @version  1.0.0
 */
class Iconic_WDS_Compat_Flexible_Shipping {
	/**
	 * Run.
	 */
	public static function run() {
		add_filter( 'iconic_wds_zone_based_shipping_method', array( __CLASS__, 'shipping_method_options' ), 10, 3 );
	}

	/**
	 * Add shipping method options.
	 *
	 * @param array            $shipping_method_options Shipping method options.
	 * @param object           $method                  Shipping method.
	 * @param WC_Shipping_Zone $shipping_zone           Shipping zone.
	 *
	 * @return array
	 */
	public static function shipping_method_options( $shipping_method_options, $method, $shipping_zone ) {
		if ( ! function_exists( 'flexible_shipping_get_all_shipping_methods' ) ) {
			return $shipping_method_options;
		}

		$class = str_replace( 'wc_shipping_', '', strtolower( get_class( $method ) ) );

		if ( 'wpdesk_flexible_shipping' !== $class ) {
			return $shipping_method_options;
		}

		$flexible_methods = $method->get_all_rates();

		if ( empty( $flexible_methods ) ) {
			return $shipping_method_options;
		}

		foreach ( $flexible_methods as $method_id => $flexible_method ) {
			$shipping_method_options[ $method_id ] = esc_html( sprintf( '%s: %s - %s', $shipping_zone->get_zone_name(), $method->title, $flexible_method['method_title'] ) );
		}

		return $shipping_method_options;
	}
}
