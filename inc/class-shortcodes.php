<?php
/**
 * WDS Shortcode class.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WDS_Shortcodes.
 *
 * @class    Iconic_WDS_Shortcodes
 * @version  1.0.0
 */
class Iconic_WDS_Shortcodes {
	/**
	 * Run.
	 */
	public static function run() {
		add_shortcode( 'iconic-wds-next-delivery-date', array( __CLASS__, 'next_delivery_date' ) );
		add_shortcode( 'iconic-wds-get-order-date', array( __CLASS__, 'get_order_date' ) );
		add_shortcode( 'iconic-wds-get-order-time', array( __CLASS__, 'get_order_time' ) );
		add_shortcode( 'iconic-wds-get-order-date-time', array( __CLASS__, 'get_order_date_time' ) );
		add_shortcode( 'iconic-wds-reservation-table', array( __CLASS__, 'reservation_table' ) );
		add_shortcode( 'iconic-wds-reserved-slot', array( __CLASS__, 'reserved_slot' ) );
	}

	/**
	 * Output next available delivery date.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function next_delivery_date( $atts ) {
		global $jckwds;

		$atts = shortcode_atts(
			array(
				'format' => 'admin_formatted',
			),
			$atts,
			'iconic-wds-next-delivery-date'
		);

		$upcoming_bookable_dates = $jckwds->get_upcoming_bookable_dates();

		$date = isset( $upcoming_bookable_dates[0][ $atts['format'] ] ) ? $upcoming_bookable_dates[0][ $atts['format'] ] : '';

		return apply_filters( 'iconic_wds_next_delivery_date', $date, $upcoming_bookable_dates );
	}

	/**
	 * Get order date.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string|void
	 */
	public static function get_order_date( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => Iconic_WDS_Helpers::get_order_id(),
			),
			$atts,
			'iconic-wds-get-order-date'
		);

		if ( empty( $atts['id'] ) ) {
			return;
		}

		$date = get_post_meta( $atts['id'], 'jckwds_date', true );

		if ( empty( $date ) ) {
			return;
		}

		return apply_filters( 'iconic_wds_shortcode_get_order_date', $date, $atts );
	}

	/**
	 * Get order time.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string|void
	 */
	public static function get_order_time( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => Iconic_WDS_Helpers::get_order_id(),
			),
			$atts,
			'iconic-wds-get-order-time'
		);

		if ( empty( $atts['id'] ) ) {
			return;
		}

		$timeslot = get_post_meta( $atts['id'], 'jckwds_timeslot', true );

		if ( empty( $timeslot ) ) {
			return;
		}

		return apply_filters( 'iconic_wds_shortcode_get_order_time', $timeslot, $atts );
	}

	/**
	 * Get order time.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string|void
	 */
	public static function get_order_date_time( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => Iconic_WDS_Helpers::get_order_id(),
			),
			$atts,
			'iconic-wds-get-order-date-time'
		);

		if ( empty( $atts['id'] ) ) {
			return;
		}

		$date     = get_post_meta( $atts['id'], 'jckwds_date', true );
		$timeslot = get_post_meta( $atts['id'], 'jckwds_timeslot', true );

		if ( empty( $date ) ) {
			return;
		}

		$return = $date;

		if ( ! empty( $timeslot ) ) {
			$return .= sprintf( ' %s %s', __( 'at', 'iconic-wds' ), $timeslot );
		}

		return apply_filters( 'iconic_wds_shortcode_get_order_date_time', $return, $atts, $date, $timeslot );
	}

	/**
	 * Display reservation table.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string|void
	 */
	public static function reservation_table( $atts ) {
		global $jckwds;

		if ( empty( $jckwds->settings ) || empty( $jckwds->settings['timesettings_timesettings_setup_enable'] ) ) {
			return __( 'The reservation table only works when time slots are enabled.', 'iconic-wds' );
		}

		return $jckwds->generate_reservation_table();
	}

	/**
	 * Display current reserved slot.
	 *
	 * @return string|void
	 */
	public static function reserved_slot() {
		global $iconic_wds;

		$slot = $iconic_wds->get_reserved_slot();

		if ( empty( $slot ) ) {
			return;
		}

		$return = '';

		if ( $slot['date'] ) {
			$return .= esc_html( $slot['date']['formatted'] );
		}

		if ( $slot['time'] ) {
			$return .= sprintf( ' %s', esc_html( $slot['time']['formatted_with_fee'] ) );
		}

		return $return;
	}
}
