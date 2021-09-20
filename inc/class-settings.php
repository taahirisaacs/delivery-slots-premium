<?php
/**
 * WDS settings class.
 *
 * @package Iconic_WDS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Iconic_WDS_Settings.
 *
 * @class    Iconic_WDS_Settings
 * @version  1.0.0
 */
class Iconic_WDS_Settings {
	/**
	 * Run.
	 */
	public static function run() {
		add_action( 'init', array( __CLASS__, 'init_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_filter( 'jckwds_settings_validate', array( __CLASS__, 'validate_settings' ), 10, 1 );
	}

	/**
	 * Init settings class.
	 */
	public static function init_settings() {
		global $jckwds;
		$jckwds->set_settings( Iconic_WDS_Core_Settings::$settings );
	}

	/**
	 * Scripts and styles on settings page.
	 */
	public static function assets() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'woocommerce_page_jckwds-settings' !== $screen_id ) {
			return;
		}

		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'wc-enhanced-select' );
	}

	/**
	 * Get category options.
	 *
	 * @return array|null
	 */
	public static function get_category_options() {
		static $categories = null;

		if ( ! is_null( $categories ) ) {
			return $categories;
		}

		$categories       = array();
		$categories_query = get_terms(
			array(
				'taxonomy' => 'product_cat',
			)
		);

		if ( empty( $categories_query ) ) {
			return $categories;
		}

		foreach ( $categories_query as $category ) {
			$categories[ $category->term_id ] = $category->name;
		}

		return $categories;
	}

	/**
	 * Get exclude products custom field.
	 *
	 * @return string
	 */
	public static function get_exclude_products_field() {
		ob_start();

		$exclude_products = Iconic_WDS_Core_Settings::get_setting_from_db( 'general_setup', 'exclude_products' );

		?>
		<style>
			.select2-container .select2-search--inline .select2-search__field {
				padding: 0;
				min-height: 28px;
			}

			.select2-container .select2-selection--multiple {
				border: 1px solid #7F8993;
				box-shadow: none;
				border-radius: 3px;
				box-sizing: border-box;
				padding-left: 8px;
				padding-right: 8px;
			}

			.select2-container--default .select2-selection--multiple .select2-selection__rendered {
				padding: 0;
			}

			.select2-container--default .select2-selection--multiple .select2-selection__rendered li {
				height: 27px;
			}

			.select2-dropdown,
			.select2-container--open .select2-selection--multiple {
				border-color: #000;
			}
		</style>
		<select class="wc-product-search" multiple="multiple" style="width: 25em;" id="exclude_products" name="jckwds_settings[general_setup_exclude_products][]" data-sortable="true" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>" data-action="woocommerce_json_search_products">
			<?php
			$product_ids = ! empty( $exclude_products ) ? $exclude_products : array();

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( is_object( $product ) ) {
					echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>';
				}
			}
			?>
		</select>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get day fees fields for settings page.
	 *
	 * @return false|string
	 */
	public static function get_day_fees_fields() {
		if ( ! is_admin() ) {
			return;
		}

		ob_start();

		$day_fees = self::get_day_fees();
		$days     = self::get_days();
		?>
		<table class="iconic-wds-table" cellpadding="0" cellspacing="0">
			<tbody>
			<?php foreach ( $day_fees as $day => $fee ) { ?>
				<tr>
					<td class="iconic-wds-table__column">
						<input type="number" id="general_setup_shipping_methods_<?php echo esc_attr( $day ); ?>" name="jckwds_settings[datesettings_fees_days][<?php echo esc_attr( $day ); ?>]" value="<?php echo ! empty( $fee ) ? esc_attr( $fee ) : ''; ?>">
					</td>
					<td class="iconic-wds-table__column iconic-wds-table__column--label">
						<label for="general_setup_shipping_methods_<?php echo esc_attr( $day ); ?>"><?php echo esc_attr( $days[ $day ] ); ?></label>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get days of the week.
	 *
	 * @return array
	 */
	public static function get_days() {
		return array(
			0 => __( 'Sunday', 'jckwds' ),
			1 => __( 'Monday', 'jckwds' ),
			2 => __( 'Tuesday', 'jckwds' ),
			3 => __( 'Wednesday', 'jckwds' ),
			4 => __( 'Thursday', 'jckwds' ),
			5 => __( 'Friday', 'jckwds' ),
			6 => __( 'Saturday', 'jckwds' ),
		);
	}

	/**
	 * Get day fees.
	 *
	 * @return array
	 */
	public static function get_day_fees() {
		$day_fees_setting = Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_fees', 'days' );

		if ( empty( $day_fees_setting ) ) {
			$day_fees_setting = array(
				0 => 0,
				1 => 0,
				2 => 0,
				3 => 0,
				4 => 0,
				5 => 0,
				6 => 0,
			);
		}

		if ( ! is_admin() ) {
			$specific_dates = self::get_specific_delivery_dates();

			if ( ! empty( $specific_dates ) ) {
				foreach ( $specific_dates as $specific_date ) {
					$fee = floatval( $specific_date['fee'] );

					if ( $fee <= 0 ) {
						continue;
					}

					$day_fees_setting[ $specific_date['ymd'] ] = $fee;
				}
			}
		}

		return array_map( 'floatval', $day_fees_setting );
	}

	/**
	 * Get delivery days fields for settings page.
	 *
	 * @return string
	 */
	public static function get_delivery_days_fields() {
		if ( ! is_admin() ) {
			return;
		}

		ob_start();

		$delivery_days = self::get_delivery_days();
		$max_orders    = self::get_delivery_days_max_orders();
		$days          = self::get_days();
		?>
		<style>
			.iconic-wds-table {
				table-layout: fixed;
				max-width: 100%;
				border-collapse: collapse;
				border-radius: 4px;
				background: #F9F9F9;
				border: 1px solid #CCD0D4;
			}

			.iconic-wds-table tr,
			.iconic-wds-table thead {
				border-bottom: 1px solid #E5E5E5;
			}

			.iconic-wds-table tr:last-child {
				border: none;
			}

			.iconic-wds-table__column {
				padding: 8px 14px !important;
				vertical-align: middle !important;
				text-align: left;
				height: 30px;
				border-left: none;
			}

			.iconic-wds-table__column--checkbox {
				width: 20px !important;
				padding-right: 0 !important;
				border-right: none;
			}

			.iconic-wds-table__column--label {
				padding-left: 4px !important;
				max-width: 260px;
			}
		</style>
		<table class="iconic-wds-table" cellpadding="0" cellspacing="0">
			<thead>
			<tr>
				<th colspan="2" class="iconic-wds-table__column iconic-wds-table__column--label">&nbsp;</th>
				<th class="iconic-wds-table__column iconic-wds-table__column--input"><?php esc_attr_e( 'Maximum Orders', 'jckwds' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $days as $day_number => $day_name ) { ?>
				<?php $checked = in_array( $day_number, $delivery_days, true ); ?>
				<?php $max_order = isset( $max_orders[ $day_number ] ) && is_numeric( $max_orders[ $day_number ] ) ? $max_orders[ $day_number ] : ''; ?>
				<tr>
					<td class="iconic-wds-table__column iconic-wds-table__column--checkbox">
						<input type="checkbox" name="jckwds_settings[datesettings_datesettings_days][<?php echo esc_attr( $day_number ); ?>]" id="datesettings_datesettings_days_<?php echo esc_attr( $day_number ); ?>" value="<?php echo esc_attr( $day_number ); ?>" <?php checked( $checked ); ?>>
					</td>
					<td class="iconic-wds-table__column iconic-wds-table__column--label"><label for="datesettings_datesettings_days_<?php echo esc_attr( $day_number ); ?>"><?php echo esc_attr( $day_name ); ?></label></td>
					<td class="iconic-wds-table__column iconic-wds-table__column--input">
						<input type="number" name="jckwds_settings[datesettings_datesettings_max_orders][<?php echo esc_attr( $day_number ); ?>]" id="datesettings_datesettings_max_orders_<?php echo esc_attr( $day_number ); ?>" value="<?php echo esc_attr( $max_order ); ?>">
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get delivery days fields for settings page.
	 *
	 * @return string
	 */
	public static function get_field_labels_by_shipping_method() {
		if ( ! is_admin() ) {
			return;
		}

		global $jckwds;

		$shipping_methods                = $jckwds->get_shipping_method_options();
		$shipping_methods_count          = count( $shipping_methods );
		$selected_shipping_methods       = self::get_shipping_methods();
		$selected_shipping_method_labels = self::get_shipping_method_labels();

		ob_start();
		?>
		<table class="iconic-wds-table" cellpadding="0" cellspacing="0">
			<?php if ( $shipping_methods_count > 1 ) { ?>
				<thead>
				<tr>
					<th colspan="2" class="iconic-wds-table__column iconic-wds-table__column--label">&nbsp;</th>
					<th class="iconic-wds-table__column iconic-wds-table__column--input"><?php esc_attr_e( 'Labels', 'jckwds' ); ?></th>

				</tr>
				</thead>
			<?php } ?>
			<tbody>
			<?php foreach ( $shipping_methods as $shipping_method_value => $shipping_method_label ) { ?>
				<?php $checked = in_array( strval( $shipping_method_value ), $selected_shipping_methods, true ); ?>
				<?php $label = ! empty( $selected_shipping_method_labels[ $shipping_method_value ] ) ? $selected_shipping_method_labels[ $shipping_method_value ] : 'default'; ?>
				<tr>
					<td class="iconic-wds-table__column iconic-wds-table__column--checkbox">
						<input type="checkbox" name="jckwds_settings[general_setup_shipping_methods][]" id="general_setup_shipping_methods_<?php echo esc_attr( $shipping_method_value ); ?>" value="<?php echo esc_attr( $shipping_method_value ); ?>" <?php checked( $checked ); ?>>
					</td>
					<td class="iconic-wds-table__column iconic-wds-table__column--label">
						<label for="general_setup_shipping_methods_<?php echo esc_attr( $shipping_method_value ); ?>"><?php echo esc_attr( $shipping_method_label ); ?></label>
					</td>
					<?php if ( $shipping_methods_count > 1 ) { ?>
						<td class="iconic-wds-table__column iconic-wds-table__column--input">
							<?php if ( 'any' !== $shipping_method_value ) { ?>
								<select name="jckwds_settings[general_setup_shipping_method_labels][<?php echo esc_attr( $shipping_method_value ); ?>]">
									<option value="default" <?php selected( $label, 'default' ); ?>><?php esc_attr_e( 'Default', 'jckwds' ); ?></option>
									<option value="delivery" <?php selected( $label, 'delivery' ); ?>><?php esc_attr_e( 'Delivery', 'jckwds' ); ?></option>
									<option value="collection" <?php selected( $label, 'collection' ); ?>><?php esc_attr_e( 'Collection', 'jckwds' ); ?></option>
								</select>
							<?php } ?>
						</td>
					<?php } ?>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get day choices for time slots.
	 *
	 * @return array
	 */
	public static function get_time_slot_day_choices() {
		if ( ! is_admin() ) {
			return array();
		}

		$days = array(
			'0' => __( 'Sunday', 'jckwds' ),
			'1' => __( 'Monday', 'jckwds' ),
			'2' => __( 'Tuesday', 'jckwds' ),
			'3' => __( 'Wednesday', 'jckwds' ),
			'4' => __( 'Thursday', 'jckwds' ),
			'5' => __( 'Friday', 'jckwds' ),
			'6' => __( 'Saturday', 'jckwds' ),
		);

		$specific_dates = (array) Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_datesettings', 'specific_days' );

		if ( empty( $specific_dates ) ) {
			return $days;
		}

		foreach ( $specific_dates as $index => $specific_date ) {
			if ( empty( $specific_date['date'] ) || empty( $specific_date['alt_date'] ) ) {
				continue;
			}

			$timestamp = strtotime( $specific_date['alt_date'] );
			$format    = ! empty( $specific_date['repeat_yearly'] ) ? 'M j' : 'M j, Y';
			$row_id    = self::get_row_id( $specific_date );

			$days[ $row_id ] = date_i18n( $format, $timestamp );
		}

		return $days;
	}

	/**
	 * Get delivery days.
	 *
	 * @return array
	 */
	public static function get_delivery_days() {
		$delivery_days_setting = Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_datesettings', 'days', array( 1, 2, 3, 4, 5 ) );

		if ( empty( $delivery_days_setting ) ) {
			$delivery_days_setting = array();
		} else {
			$delivery_days_setting = array_map( 'absint', $delivery_days_setting );
		}

		return apply_filters( 'iconic_wds_delivery_days', $delivery_days_setting );
	}

	/**
	 * Get delivery days max orders.
	 *
	 * @param int $day_of_the_week The specific day of the week, ranging from 0 (Sunday) to 6 (Saturday).
	 *
	 * @return array|int|bool
	 */
	public static function get_delivery_days_max_orders( $day_of_the_week = false ) {
		$max_orders = Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_datesettings', 'max_orders' );

		if ( empty( $max_orders ) ) {
			$max_orders = array_fill( 0, 7, true );
		}

		foreach ( $max_orders as $key => $max_order ) {
			$count              = is_numeric( $max_order ) ? absint( $max_order ) : true;
			$max_orders[ $key ] = $count <= 0 ? false : $count;
		}

		$max_orders = apply_filters( 'iconic_wds_delivery_days_max_orders', $max_orders, $day_of_the_week );

		if ( false !== $day_of_the_week ) {
			// Return setting for specific day, or false if none set.
			return isset( $max_orders[ $day_of_the_week ] ) ? $max_orders[ $day_of_the_week ] : false;
		}

		return $max_orders;
	}

	/**
	 * Get shipping methods.
	 *
	 * @return array
	 */
	public static function get_shipping_methods() {
		$shipping_methods = Iconic_WDS_Core_Settings::get_setting_from_db( 'general_setup', 'shipping_methods' );

		if ( empty( $shipping_methods ) ) {
			$shipping_methods = array( 'any' );
		}

		$shipping_methods = array_map( 'strval', $shipping_methods );

		return apply_filters( 'iconic_wds_shipping_methods', $shipping_methods );
	}

	/**
	 * Get shipping method labels.
	 *
	 * @return array
	 */
	public static function get_shipping_method_labels() {
		$shipping_method_labels = Iconic_WDS_Core_Settings::get_setting_from_db( 'general_setup', 'shipping_method_labels' );

		if ( empty( $shipping_method_labels ) ) {
			$shipping_method_labels = array();
		}

		return apply_filters( 'iconic_wds_shipping_method_labels', $shipping_method_labels );
	}

	/**
	 * Admin: Validate Settings
	 *
	 * @param array $settings Un-validated settings.
	 *
	 * @return array $validated_settings
	 */
	public static function validate_settings( $settings ) {
		global $jckwds;

		// Validate shipping methods.
		if ( empty( $settings['general_setup_shipping_methods'] ) ) {
			$settings['general_setup_shipping_methods'] = array( 'any' );

			$message = __( 'You need to select at least one shipping method in General Settings. "Any Method" has been selected for you.', 'jckwds' );
			add_settings_error( 'general_setup_shipping_methods', esc_attr( 'jckwds-error' ), $message, 'error' );
		}

		// validate cutoff.
		if ( isset( $settings['timesettings_timesettings_cutoff'] ) ) {
			if ( $settings['timesettings_timesettings_cutoff'] < 0 || ! is_numeric( $settings['timesettings_timesettings_cutoff'] ) ) {
				$settings['timesettings_timesettings_cutoff'] = 0;

				$message = __( '"Allow Bookings Up To (x) Minutes Before Slot" should be a positive integer. It has defaulted to 0.', 'jckwds' );

				add_settings_error( 'timesettings_timesettings_cutoff', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate min selectable date.
		if ( isset( $settings['datesettings_datesettings_minimum'] ) ) {
			if ( $settings['datesettings_datesettings_minimum'] < 0 || ! is_numeric( $settings['datesettings_datesettings_minimum'] ) ) {
				$settings['datesettings_datesettings_minimum'] = 0;

				$message = __( 'Minimum selectable date should be a positive integer. It has defaulted to 0.', 'jckwds' );

				add_settings_error( 'datesettings_datesettings_minimum', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate max selectable date.
		if ( isset( $settings['datesettings_datesettings_maximum'] ) ) {
			if ( $settings['datesettings_datesettings_maximum'] < 0 || ! is_numeric( $settings['datesettings_datesettings_maximum'] ) ) {
				$settings['datesettings_datesettings_maximum'] = 14;

				$message = __( 'Maximum selectable date should be a positive integer. It has defaulted to 14.', 'jckwds' );

				add_settings_error( 'datesettings_datesettings_maximum', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate min/max selectable date.
		if ( isset( $settings['datesettings_datesettings_minimum'] ) && isset( $settings['datesettings_datesettings_maximum'] ) ) {
			if ( $settings['datesettings_datesettings_minimum'] > $settings['datesettings_datesettings_maximum'] ) {
				$settings['datesettings_datesettings_minimum'] = $settings['datesettings_datesettings_maximum'];

				$message = __( 'Minimum selectable date should be less than or equal to the maximum selectable date.', 'jckwds' );

				add_settings_error( 'datesettings_datesettings_maximum', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate same day cutoff.
		if ( isset( $settings['datesettings_datesettings_sameday_cutoff'] ) ) {
			if ( '' !== $settings['datesettings_datesettings_sameday_cutoff'] && ! self::validate_time_format( $settings['datesettings_datesettings_sameday_cutoff'] ) ) {
				$settings['datesettings_datesettings_sameday_cutoff'] = '';

				$message = __( 'The Same Day cutoff should be a valid time format (00:00), try using the time picker instead.', 'jckwds' );

				add_settings_error( 'datesettings_datesettings_sameday_cutoff', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate next day cutoff.
		if ( isset( $settings['datesettings_datesettings_nextday_cutoff'] ) ) {
			if ( '' !== $settings['datesettings_datesettings_nextday_cutoff'] && ! self::validate_time_format( $settings['datesettings_datesettings_nextday_cutoff'] ) ) {
				$settings['datesettings_datesettings_nextday_cutoff'] = '';

				$message = __( 'The Next Day cutoff should be a valid time format (00:00), try using the time picker instead.', 'jckwds' );

				add_settings_error( 'datesettings_datesettings_nextday_cutoff', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// validate timeslots.
		if ( $settings['timesettings_timesettings_setup_enable'] ) {
			if ( is_array( $settings['timesettings_timesettings_timeslots'] ) ) {
				$default_cutoff = '';

				$cutoff_numeric         = true;
				$empty_shipping_methods = false;
				$empty_days             = false;
				$valid_time_format      = true;

				$i = 0;
				foreach ( $settings['timesettings_timesettings_timeslots'] as $timeslot ) {
					// validate shipping methods.

					if ( empty( $timeslot['shipping_methods'] ) ) {
						$settings['timesettings_timesettings_timeslots'][ $i ]['shipping_methods'] = array( 'any' );

						$empty_shipping_methods = true;
					}

					// validate cutoff.

					if ( isset( $timeslot['cutoff'] ) ) {
						if ( ! empty( $timeslot['cutoff'] ) && ( $timeslot['cutoff'] <= 0 || ! is_numeric( $timeslot['cutoff'] ) ) ) {
							$settings['timesettings_timesettings_timeslots'][ $i ]['cutoff'] = $default_cutoff;

							$cutoff_numeric = false;
						}
					}

					// validate days.
					if ( isset( $timeslot['days'] ) && empty( $timeslot['days'] ) ) {
						$settings['timesettings_timesettings_timeslots'][ $i ]['days'] = array( 0, 1, 2, 3, 4, 5, 6 );

						$empty_days = true;
					}

					// validate time formats.

					if ( isset( $timeslot['timefrom'] ) ) {
						$validated_time_format = self::validate_time_format( $timeslot['timefrom'] );

						if ( false === $validated_time_format ) {
							$settings['timesettings_timesettings_timeslots'][ $i ]['timefrom'] = '01:00';

							$valid_time_format = false;
						}
					}

					if ( isset( $timeslot['timeto'] ) ) {
						$validated_time_format = self::validate_time_format( $timeslot['timeto'] );

						if ( false === $validated_time_format ) {
							$settings['timesettings_timesettings_timeslots'][ $i ]['timeto'] = '23:00';

							$valid_time_format = false;
						}
					}

					$i ++;
				}

				// validate shipping methods.
				if ( $empty_shipping_methods ) {
					$message = __( 'Some of your time slots were not enabled for any shipping methods. "Any Method" has been selected for them.', 'jckwds' );
					add_settings_error( 'timesettings_timesettings_timeslots_shipping_methods', esc_attr( 'jckwds-error' ), $message, 'error' );
				}

				// validate cutoff.
				if ( ! $cutoff_numeric ) {
					$message = __( 'The "Allow Bookings Up To (x) Minutes Before Slot" time slot setting should be a positive integer. It has been removed.', 'jckwds' );
					add_settings_error( 'timesettings_timesettings_timeslots_cutoff', esc_attr( 'jckwds-error' ), $message, 'error' );
				}

				// validate days.
				if ( $empty_days ) {
					$message = __( 'You should select at least one active day for your time slot. All days have now been selected.', 'jckwds' );
					add_settings_error( 'timesettings_timesettings_timeslots_days', esc_attr( 'jckwds-error' ), $message, 'error' );
				}

				// validate time format.
				if ( ! $valid_time_format ) {
					$message = __( 'One of the time slots you entered had an invalid format. Try using the time picker instead. A default has been added in its place.', 'jckwds' );
					add_settings_error( 'timesettings_timesettings_timeslots_format', esc_attr( 'jckwds-error' ), $message, 'error' );
				}
			}
		}

		// Validate holidays.
		$holidays = array_filter( $settings['holidays_holidays_holidays'] );

		if ( ! empty( $holidays ) ) {
			$empty_holiday_shipping_methods = false;
			$original_format                = Iconic_WDS_Date_Helpers::date_format( $jckwds->settings['datesettings_datesettings_dateformat'] );
			$new_format                     = Iconic_WDS_Date_Helpers::date_format( $settings['datesettings_datesettings_dateformat'] );
			$utc_timezone                   = new DateTimeZone( 'UTC' );

			foreach ( $settings['holidays_holidays_holidays'] as $index => $holiday ) {
				$dates = array_filter(
					array(
						'date'    => ! empty( $holiday['date'] ) ? $holiday['date'] : '',
						'date_to' => ! empty( $holiday['date_to'] ) ? $holiday['date_to'] : '',
					)
				);

				if ( empty( $dates ) ) {
					continue;
				}

				foreach ( $dates as $date_key => $date ) {
					$date_object = DateTime::createFromFormat( $original_format . ' H:i:s', $date . ' 00:00:00', $utc_timezone );

					if ( ! $date_object ) {
						continue;
					}

					$settings['holidays_holidays_holidays'][ $index ][ $date_key ]          = $date_object->format( $new_format );
					$settings['holidays_holidays_holidays'][ $index ][ 'alt_' . $date_key ] = $date_object->format( 'd/m/Y' );
				}

				// validate shipping methods.

				if ( empty( $holiday['shipping_methods'] ) ) {
					$settings['holidays_holidays_holidays'][ $index ]['shipping_methods'] = array( 'any' );

					$empty_holiday_shipping_methods = true;
				}
			}

			// validate shipping methods.
			if ( $empty_holiday_shipping_methods ) {
				$message = __( 'Some of your holidays had no shipping methods assigned. "Any Method" has been selected for them.', 'jckwds' );
				add_settings_error( 'holidays_holidays_holidays', esc_attr( 'jckwds-error' ), $message, 'error' );
			}
		}

		// clear transients.
		delete_transient( $jckwds->timeslot_data_transient_name );

		return $settings;
	}

	/**
	 * Helper: Validate Time Format
	 *
	 * @param string $time time.
	 *
	 * @return bool
	 */
	public static function validate_time_format( $time ) {
		if ( false === $time || '' === $time ) {
			return false;
		}

		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		$match = preg_match( '/(2[0-3]|[01][0-9]):([0-5][0-9])/', $time );
		if ( 0 === $match ) {
			return false;
		}

		return true;
	}

	/**
	 * Get checkout field position choices.
	 *
	 * @return array
	 */
	public static function get_field_position_choices() {
		$choices = array(
			'woocommerce_checkout_before_customer_details' => __( 'Before Customer Details', 'jckwds' ),
			'woocommerce_checkout_billing'                 => __( 'Within Billing Fields', 'jckwds' ),
			'woocommerce_checkout_shipping'                => __( 'Within Shipping Fields', 'jckwds' ),
			'woocommerce_checkout_after_customer_details'  => __( 'After Customer Details', 'jckwds' ),
			'woocommerce_checkout_before_order_review'     => __( 'Before Order Review', 'jckwds' ),
			'woocommerce_checkout_order_review'            => __( 'Within Order Review', 'jckwds' ),
			'woocommerce_checkout_after_order_review'      => __( 'After Order Review', 'jckwds' ),
		);

		return apply_filters( 'iconic_wds_field_position_choices', $choices );
	}

	/**
	 * Get specific delivery dates.
	 *
	 * @return array
	 */
	public static function get_specific_delivery_dates() {
		global $iconic_wds;

		$specific_dates = array();

		if ( ! empty( $iconic_wds->settings ) ) {
			$specific_dates = (array) $iconic_wds->settings['datesettings_datesettings_specific_days'];

			if ( ! empty( $specific_dates ) ) {
				$min_date = Iconic_WDS_Date_Helpers::get_minmax_delivery_date( 'min' );
				$max_date = Iconic_WDS_Date_Helpers::get_minmax_delivery_date( 'max' );

				foreach ( $specific_dates as $index => $specific_date ) {
					if ( empty( $specific_date['date'] ) || empty( $specific_date['alt_date'] ) ) {
						unset( $specific_dates[ $index ] );
						continue;
					}

					$timestamp = strtotime( $specific_date['alt_date'] );

					// If we repeat this date yearly, and the date is in the past, get next year's date.
					if ( $specific_date['repeat_yearly'] && strtotime( gmdate( 'Y-m-d' ) ) > $timestamp ) {
						$timestamp = strtotime( '+1 years', $timestamp );
					}

					// Don't use this date if it's before the min selectable date, or
					// it's not set to bypass the maximum selectable date and is past it.
					if ( $timestamp < $min_date['timestamp'] || ( ! $specific_date['bypass_max'] && $timestamp >= $max_date['timestamp'] ) ) {
						unset( $specific_dates[ $index ] );
						continue;
					}

					$specific_dates[ $index ]['ymd']       = str_replace( '-', '', $specific_date['alt_date'] );
					$specific_dates[ $index ]['timestamp'] = $timestamp;
				}
			}
		}

		return apply_filters( 'iconic_wds_specific_delivery_dates', $specific_dates );
	}

	/**
	 * Is this date a specific delivery date?
	 *
	 * @param string $ymd Ymd.
	 *
	 * @return bool|array
	 */
	public static function is_specific_date( $ymd ) {
		$specific_dates = (array) Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_datesettings', 'specific_days' );

		if ( empty( $specific_dates ) ) {
			return false;
		}

		$md = substr( $ymd, 4 );

		foreach ( $specific_dates as $specific_date ) {
			$specific_date_ymd    = str_replace( '-', '', $specific_date['alt_date'] );
			$specific_date_format = ! $specific_date['repeat_yearly'] ? $specific_date_ymd : substr( $specific_date_ymd, 4 );

			if ( ( ! $specific_date['repeat_yearly'] && $specific_date_format === $ymd ) || ( $specific_date['repeat_yearly'] && $specific_date_format === $md ) ) {
				return $specific_date;
			}
		}

		return false;
	}

	/**
	 * Get min/max method setting.
	 *
	 * @return string|bool
	 */
	public static function get_minmax_method() {
		global $iconic_wds;

		$minmax_method = isset( $iconic_wds->settings['datesettings_datesettings_minmaxmethod'] ) ? $iconic_wds->settings['datesettings_datesettings_minmaxmethod'] : false;
		$allowed_days  = self::get_delivery_days();

		if ( 'allowed' === $minmax_method && empty( $allowed_days ) ) {
			$minmax_method = 'all';
		}

		return apply_filters( 'iconic_wds_minmax_method', $minmax_method );
	}
  
	/**
	 * Get row ID from repeatable settings field.
	 *
	 * Avoids returning 0-6 as these are reserved for days of the week.
	 *
	 * @param array $row Row.
	 *
	 * @return bool|int|string
	 */
	public static function get_row_id( $row ) {
		if ( ! isset( $row['row_id'] ) ) {
			return false;
		}

		return is_numeric( $row['row_id'] ) && absint( $row['row_id'] ) <= 6 ? sprintf( 'sd_%d', $row['row_id'] ) : $row['row_id'];
	}

	/**
	 * Get max order calculation method options.
	 *
	 * Do not return 'products' when WooCommerce version is less than 4.0.0
	 * Because wp_wc_order_stats table was introduced in 4.0.0 and we need
	 * this table to efficiently determine the number of products per order.
	 *
	 * @return array
	 */
	public static function get_max_order_calculation_methods() {
		global $iconic_wds;

		$methods = array(
			'orders' => __( 'Orders', 'jckwds' ),
		);

		// More than 4.0.0.
		if ( version_compare( $iconic_wds->get_woo_version_number(), '4.0.0', '>' ) ) {
			$methods['products'] = __( 'Products', 'jckwds' );
		}

		return $methods;
	}
}
