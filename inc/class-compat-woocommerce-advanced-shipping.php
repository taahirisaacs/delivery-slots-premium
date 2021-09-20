<?php
/**
 * Compatiblity with WooCommerce Advanced Shipping plugin.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Compatiblity with WooCommerce Advanced Shipping plugin.
 * https://codecanyon.net/item/woocommerce-advanced-shipping/8634573
 *
 * @class    Iconic_WDS_Compat_Woocommerce_Advanced_Shipping
 * @version  1.0.0
 */
class Iconic_WDS_Compat_Woocommerce_Advanced_Shipping {
	/**
	 * Run.
	 */
	public static function run() {
		add_filter( 'iconic_wds_shipping_method_options', array( __CLASS__, 'shipping_method_options' ), 10 );
	}

	/**
	 * Add shipping method options.
	 *
	 * @param array $shipping_method_options Shipping method options.
	 *
	 * @return array
	 */
	public static function shipping_method_options( $shipping_method_options ) {
		if ( ! class_exists( 'WooCommerce_Advanced_Shipping' ) ) {
			return $shipping_method_options;
		}

		$methods = get_posts(
			array(
				'posts_per_page' => '-1',
				'post_type'      => 'was',
				'post_status'    => array( 'draft', 'publish' ),
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		if ( empty( $methods ) ) {
			return $shipping_method_options;
		}

		foreach ( $methods as $method ) {
			$method_details = get_post_meta( $method->ID, '_was_shipping_method', true );

			$shipping_method_options[ strval( $method->ID ) ] = empty( $method_details['shipping_title'] ) ? $method->post_title : wp_kses_post( $method_details['shipping_title'] );
		}

		unset( $shipping_method_options['was_advanced_shipping_method'] );

		return $shipping_method_options;
	}
}
