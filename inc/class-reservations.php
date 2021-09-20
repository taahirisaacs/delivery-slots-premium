<?php
/**
 * WDS Reservations class.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reservations Class
 *
 * All methods to do with reservations
 */
class Iconic_WDS_Reservations {
	/**
	 * Database version.
	 *
	 * @var string
	 */
	private static $db_version = '1.7';

	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'plugins_loaded', array( __CLASS__, 'update_db_check' ) );
	}

	/**
	 * Check if the DB needs updating.
	 */
	public static function update_db_check() {
		global $wpdb;

		$installed_ver = get_option( 'jckwds_db_version' );

		if ( $installed_ver !== self::$db_version ) {
			$sql = "CREATE TABLE {$wpdb->prefix}jckwds (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`datetimeid` text,
				`processed` tinyint(1) DEFAULT NULL,
				`order_id` mediumint(9) DEFAULT NULL,
				`user_id` text,
				`expires` text,
				`date` datetime DEFAULT NULL,
				`starttime` mediumint(4) unsigned zerofill DEFAULT NULL,
				`endtime` mediumint(4) unsigned zerofill DEFAULT NULL,
				`asap` tinyint(1) DEFAULT NULL,
				UNIQUE KEY `id` (`id`)
			);";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			update_option( 'jckwds_db_version', self::$db_version );
		}
	}

	/**
	 * Get reservations.
	 *
	 * @param int $processed 1 = yes, 0 = no.
	 *
	 * @return array
	 */
	public static function get_reservations( $processed = 1 ) {
		$return = array();

		if ( ! empty( $return[ $processed ] ) ) {
			return $return[ $processed ];
		}

		// Allow 3rd-parties to override the request and return their own results.
		$reservations = apply_filters( 'iconic_wds_reservations_pre_query', null, $processed );

		if ( is_null( $reservations ) ) {
			global $wpdb;

			$reservations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}jckwds WHERE date >= %s AND processed = %d ORDER BY date ASC, starttime ASC",
					current_time( 'Y-m-d' ),
					$processed
				),
				OBJECT
			);
		}

		$reservations = apply_filters( 'iconic_wds_reservations_query_result', $reservations );

		$today    = gmdate( 'Y-m-d 00:00:00', time() );
		$tomorrow = gmdate( 'Y-m-d 00:00:00', strtotime( '+1 day', time() ) );

		if ( ! empty( $reservations ) ) {
			foreach ( $reservations as $index => $reservation ) {
				$reservation->order         = wc_get_order( $reservation->order_id );
				$reservation->billing_name  = '&mdash;';
				$reservation->billing_email = '&mdash;';

				$start_time_formatted = Iconic_WDS_Helpers::get_time_formatted( $reservation->starttime );
				$end_time_formatted   = Iconic_WDS_Helpers::get_time_formatted( $reservation->endtime );

				$reservation->iconic_wds = array(
					'date_formatted'      => Iconic_WDS_Helpers::get_date_formatted( $reservation->date ),
					'starttime_formatted' => $start_time_formatted,
					'endtime_formatted'   => $end_time_formatted,
					'time_slot_formatted' => $start_time_formatted === $end_time_formatted ? $start_time_formatted : sprintf( '%s - %s', $start_time_formatted, $end_time_formatted ),
					'same_day'            => $reservation->date === $today,
					'next_day'            => $reservation->date === $tomorrow,
				);

				if ( ! empty( $reservation->order ) ) {
					$reservation->order_status       = $reservation->order->get_status();
					$reservation->order_status_badge = Iconic_WDS_Order::get_status_badge( $reservation->order_status );
					$reservation->order_edit         = Iconic_WDS_Order::get_edit_order_link_html( $reservation->order_id );
					$reservation->order_items        = Iconic_WDS_Order::get_order_items( $reservation->order );
					$reservation->shipping_method    = $reservation->order->get_shipping_method();
					$reservation->method_label       = Iconic_WDS_Helpers::get_label( 'details', $reservation->order );
					$reservation->address_link       = Iconic_WDS_Order::get_shipping_address_link_html( $reservation->order );
					$reservation->billing_name       = Iconic_WDS_Order::get_billing_full_name( $reservation->order );
					$reservation->billing_email      = Iconic_WDS_Order::get_billing_email_link_html( $reservation->order );
				}

				if ( is_numeric( $reservation->user_id ) && empty( $reservation->order ) ) {
					$customer = get_userdata( $reservation->user_id );

					$reservation->billing_name  = Iconic_WDS_Helpers::get_user_name( $customer );
					$reservation->billing_email = Iconic_WDS_Helpers::get_email_link_html( $customer->user_email );
				}
			}
		}

		$return[ $processed ] = array(
			'processed' => $processed,
			'results'   => $reservations,
		);

		return $return[ $processed ];
	}

	/**
	 * Get reservation for order.
	 *
	 * @param int $order_id ID of the order.
	 *
	 * @return array|null
	 */
	public static function get_reservation_for_order( $order_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}jckwds WHERE order_id = %d", $order_id )
		);
	}
}
