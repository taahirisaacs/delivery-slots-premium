<?php
/**
 * Compatiblity with WooCommerce Lead Time (https://iconicwp.com/go/barn2-lead-time/).
 *
 * @package Iconic_WDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Iconic_WDS_Compat_Lead_Time class.
 *
 * @class    Iconic_WDS_Compat_Lead_Time
 */
class Iconic_WDS_Compat_Lead_Time {
	/**
	 * Run.
	 */
	public static function run() {
		add_filter( 'iconic_wds_min_delivery_date', array( __CLASS__, 'min_delivery_date' ) );
	}

	/**
	 * Modify min delivery date.
	 *
	 * @param array $data Array of "days to add data".
	 *
	 * @return array
	 */
	public static function min_delivery_date( $data = array() ) {
		if ( ! function_exists( 'Barn2\Plugin\WC_Lead_Time\wlt' ) ) {
			return $data;
		}

		$lead_time = self::get_longest_lead_time_from_cart();

		if ( empty( $lead_time ) ) {
			return $data;
		}

		$unit = self::get_lead_time_units();

		return array(
			'days_to_add' => 'days' === $unit ? $lead_time : $lead_time * 7,
			'timestamp'   => strtotime( '+' . $lead_time . ' ' . $unit, time() ),
		);
	}

	/**
	 * Get lead time units.
	 *
	 * @return mixed|string|void
	 */
	public static function get_lead_time_units() {
		$unit = get_option( 'wclt_units', 'default' );

		return 'weeks' !== $unit ? 'days' : $unit;
	}

	/**
	 * Get longest possible lead time from cart items.
	 *
	 * @return bool
	 */
	public static function get_longest_lead_time_from_cart() {
		$cart_items = WC()->cart->get_cart();

		if ( empty( $cart_items ) ) {
			return false;
		}

		$lead_time = 0;

		foreach ( $cart_items as $cart_item ) {
			$product_lead_time = self::get_product_lead_time( $cart_item['data'] );

			if ( $product_lead_time && $product_lead_time <= $lead_time ) {
				continue;
			}

			$lead_time = $product_lead_time;
		}

		return $lead_time;
	}

	/**
	 * Get product lead time.
	 *
	 * @param WC_Product $product Product.
	 *
	 * @return bool|int
	 */
	public static function get_product_lead_time( $product ) {
		$parent_product = $product->get_parent_id();

		if ( $parent_product ) {
			$product = wc_get_product( $parent_product );
		}

		// Get product lead time.
		$lead_time = $product->get_meta( '_wclt_lead_time' );

		// Check if individual lead time is empty and get global.
		if ( empty( $lead_time ) ) {
			$lead_time = get_option( 'wclt_global_time' );
		}

		return empty( $lead_time ) ? false : absint( $lead_time );
	}
}
