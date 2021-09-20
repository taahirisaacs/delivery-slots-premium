<?php
/**
 * WDS Date Helper class.
 *
 * @package Iconic_WDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Iconic_WDS_Date_Helpers class.
 */
class Iconic_WDS_Date_Helpers {
	/**
	 * Get the first allowed timestamp starting from the given timestamp.
	 *
	 * @param bool|int $start_timestamp Start timestamp.
	 * @param int      $minimum_days    Minimum number of days to count.
	 * @param bool     $maximum_days    Maximum number of days to count.
	 *
	 * @return bool|array
	 */
	public static function get_next_allowed_timestamp( $start_timestamp = false, $minimum_days = 0, $maximum_days = false ) {
		if ( ! $start_timestamp ) {
			return false;
		}

		$count                  = 0;
		$timestamps             = new ArrayIterator( array( $start_timestamp ) );
		$last_allowed_timestamp = $start_timestamp;
		$last_day_of_the_week   = self::is_current_week_only() ? self::get_last_day_of_the_week() : false;

		foreach ( $timestamps as $timestamp ) {
			// If we're only getting dates from this week,
			// and this timestamp is past of the last day
			// of the week, return the last allowed timestamp.
			if ( $last_day_of_the_week && $timestamp > $last_day_of_the_week ) {
				$start_timestamp = $last_allowed_timestamp;
				break;
			}

			// If this is an actual delivery day, store it
			// in the memory.
			if ( self::is_delivery_day( $timestamp ) ) {
				$last_allowed_timestamp = $timestamp;
			}

			// Check if this is a delivery day according to the
			// min/max calculation method.
			$is_day_allowed = self::is_day_allowed( $timestamp, true );

			if ( ! $is_day_allowed ) {
				$timestamps->append( strtotime( '+1 day', $timestamp ) );
				continue;
			}

			// If we've counted the minimum number of days,
			// return the timestamp.
			if ( $count === $minimum_days ) {
				$start_timestamp = $timestamp;
				break;
			}

			// Otherwise, add to the count
			$count ++;

			// If we've reached the maximum days,
			// we're not going to find a suitable date.
			if ( $maximum_days && $count > $maximum_days ) {
				$start_timestamp = $timestamp;
				break;
			}

			// Then, add a new timestamp to check.
			// We're only counting allowed days.
			$timestamps->append( strtotime( '+1 day', $timestamp ) );
		}

		return array(
			'timestamp' => $start_timestamp,
			'count'     => $count,
		);
	}

	/**
	 * Is this day allowed for delivery?
	 *
	 * @param int  $timestamp   UTC timestamp of day to check.
	 * @param bool $calculation Is this to calculate min/max delivery days.
	 *
	 * @return bool
	 */
	public static function is_day_allowed( $timestamp, $calculation = false ) {
		global $jckwds;

		$allowed         = false;
		$is_same_day     = self::is_same_day( $timestamp );
		$is_delivery_day = self::is_delivery_day( $timestamp, $calculation );
		$skip_current    = (bool) $jckwds->settings['datesettings_datesettings_skip_current'];
		$is_holiday      = self::is_holiday( $timestamp );

		// If is today and is a delivery day and not a holiday.
		if ( $is_same_day && ( $is_delivery_day && ! $is_holiday ) ) {
			$allowed = true;
		}

		// If is today and is not a delivery and not skip.
		if ( $is_same_day && ( ! $is_delivery_day || $is_holiday ) && ! $skip_current ) {
			$allowed = true;
		}

		// If is not today and is a delivery day and not a holiday.
		if ( ! $is_same_day && ( $is_delivery_day && ! $is_holiday ) ) {
			$allowed = true;
		}

		return apply_filters( 'iconic_wds_is_day_allowed', $allowed, $timestamp );
	}

	/**
	 * Compare timestamp to current day.
	 *
	 * @param int $timestamp UTC timestamp of date to check.
	 *
	 * @return bool
	 */
	public static function is_same_day( $timestamp ) {
		$today   = current_time( 'Ymd', 1 );
		$compare = gmdate( 'Ymd', $timestamp );

		return $today === $compare;
	}

	/**
	 * Is this a delivery day.
	 *
	 * @param int  $timestamp   UTC timestamp of date to check.
	 * @param bool $calculation Is this for min/max calculations.
	 *
	 * @return bool
	 */
	public static function is_delivery_day( $timestamp, $calculation = false ) {
		global $iconic_wds;

		$specific_dates = (array) $iconic_wds->settings['datesettings_datesettings_specific_days'];

		if ( ! empty( $specific_dates ) ) {
			foreach ( $specific_dates as $specific_date ) {
				if ( empty( $specific_date['date'] ) || empty( $specific_date['alt_date'] ) ) {
					continue;
				}

				$specific_date_timestamp = strtotime( $specific_date['alt_date'] );
				$compare                 = $specific_date['repeat_yearly'] ? 'Ymd' : 'md';

				$specific_date_compare = gmdate( $compare, $specific_date_timestamp );
				$date_compare          = gmdate( $compare, $timestamp );

				if ( $specific_date_compare === $date_compare ) {
					return apply_filters( 'iconic_wds_is_delivery_day', true, $timestamp, $calculation );
				}
			}
		}

		$minmax_setting = Iconic_WDS_Settings::get_minmax_method();

		// If we're doing a calculation and minmax method is
		// all, return true. All days are delivery days.
		if ( $calculation && 'all' === $minmax_setting ) {
			return apply_filters( 'iconic_wds_is_delivery_day', true, $timestamp, $calculation );
		}

		// Get day in GMT timezone.
		$day = absint( gmdate( 'w', $timestamp ) );

		// If we're doing a calulcation and minmax method id
		// weekdays, check if the day we're checking is a weekday.
		// If so, return true.
		if ( $calculation && 'weekdays' === $minmax_setting ) {
			$is_delivery_day = in_array( $day, array( 1, 2, 3, 4, 5 ), true );

			return apply_filters( 'iconic_wds_is_delivery_day', $is_delivery_day, $timestamp, $calculation );
		}

		$allowed_days = self::get_allowed_delivery_days();

		$is_delivery_day = ! empty( $allowed_days[ $day ] );

		return apply_filters( 'iconic_wds_is_delivery_day', $is_delivery_day, $timestamp, $calculation );
	}

	/**
	 * Is day a holiday?
	 *
	 * @param int $timestamp UTC timestamp of date to check.
	 *
	 * @return bool
	 */
	public static function is_holiday( $timestamp ) {
		global $jckwds;

		$holidays = $jckwds->get_formatted_holidays();

		$ymd = date_i18n( 'Ymd', $timestamp );
		$md  = date_i18n( 'md', $timestamp );

		$is_holdiay = in_array( $ymd, $holidays, true ) || in_array( $md, $holidays, true );

		return apply_filters( 'iconic_wds_is_holiday', $is_holdiay, $timestamp, $holidays );
	}

	/**
	 * Is timestamp in the current week.
	 *
	 * @param int $timestamp UTC timestamp of date to check.
	 *
	 * @return bool
	 */
	public static function is_in_current_week( $timestamp ) {
		return $timestamp < self::get_last_day_of_the_week();
	}

	/**
	 * Is current week only set?
	 *
	 * @return bool
	 */
	public static function is_current_week_only() {
		global $jckwds;

		return ! empty( $jckwds->settings['datesettings_datesettings_week_limit'] );
	}

	/**
	 * Get last day of the week timestamp.
	 *
	 * @return false|int
	 */
	public static function get_last_day_of_the_week() {
		global $jckwds;

		$today            = strtolower( gmdate( 'l', time() ) );
		$last_day_of_week = $jckwds->settings['datesettings_datesettings_last_day_of_week'];

		return $today === $last_day_of_week ? strtotime( 'today 23:59:59' ) : strtotime( 'next ' . $last_day_of_week . ' 23:59:59' );
	}

	/**
	 * Difference between 2 timestamps in days.
	 *
	 * @param int      $then UTC timestamp.
	 * @param int|bool $now  If false, uses today.
	 *
	 * @return float|int
	 */
	public static function get_difference_in_days( $then, $now = false ) {
		$now = $now ? $now : time();

		return absint( floor( abs( $now - $then ) / 60 / 60 / 24 ) );
	}

	/**
	 * Create a timestamp range
	 *
	 * @param int $timestamp_from From timestamp.
	 * @param int $timestamp_to   To timestamp.
	 *
	 * @return array
	 */
	public static function create_timestamp_range( $timestamp_from, $timestamp_to ) {
		$range = array();

		if ( $timestamp_to >= $timestamp_from ) {
			if ( self::is_delivery_day( $timestamp_from ) ) {
				array_push( $range, $timestamp_from );
			}

			while ( $timestamp_from < $timestamp_to ) {
				$timestamp_from = $timestamp_from + 86400; // + 1 day (in seconds).

				if ( self::is_delivery_day( $timestamp_from ) ) {
					array_push( $range, $timestamp_from );
				}
			}
		}

		return $range;
	}

	/**
	 * Get allowed days
	 *
	 * @param bool $minmax Mimax or default.
	 *
	 * @return array
	 */
	public static function get_allowed_delivery_days( $minmax = false ) {
		global $jckwds;

		$key = $minmax ? 'minmax' : 'default';

		if ( ! empty( $jckwds->allowed_delivery_days[ $key ] ) ) {
			return $jckwds->allowed_delivery_days[ $key ];
		}

		$jckwds->allowed_delivery_days[ $key ] = array(
			0 => false,
			1 => false,
			2 => false,
			3 => false,
			4 => false,
			5 => false,
			6 => false,
		);

		$mixmax_method = Iconic_WDS_Settings::get_minmax_method();

		if ( ! $minmax || 'allowed' === $mixmax_method ) {
			$chosen_days = Iconic_WDS_Settings::get_delivery_days();

			if ( $chosen_days && ! empty( $chosen_days ) ) {
				foreach ( $chosen_days as $day ) {
					$jckwds->allowed_delivery_days[ $key ][ $day ] = true;
				}
			}

			$jckwds->allowed_delivery_days[ $key ] = apply_filters( 'iconic_wds_allowed_days', $jckwds->allowed_delivery_days[ $key ], $minmax );

			return $jckwds->allowed_delivery_days[ $key ];
		}

		if ( 'all' === $mixmax_method ) {
			$jckwds->allowed_delivery_days[ $key ] = array(
				0 => true,
				1 => true,
				2 => true,
				3 => true,
				4 => true,
				5 => true,
				6 => true,
			);
		} elseif ( 'weekdays' === $mixmax_method ) {
			$jckwds->allowed_delivery_days[ $key ] = array(
				0 => false,
				1 => true,
				2 => true,
				3 => true,
				4 => true,
				5 => true,
				6 => false,
			);
		}

		$jckwds->allowed_delivery_days[ $key ] = apply_filters( 'iconic_wds_allowed_days', $jckwds->allowed_delivery_days[ $key ], $minmax );

		return $jckwds->allowed_delivery_days[ $key ];
	}

	/**
	 * Get allowed delivery date (x) days from now
	 *
	 * @param string $type min/max.
	 *
	 * @return array timestamp, days_to_add
	 */
	public static function get_minmax_delivery_date( $type = 'min' ) {
		global $jckwds;

		$days     = 'min' === $type ? (int) $jckwds->settings['datesettings_datesettings_minimum'] : (int) $jckwds->settings['datesettings_datesettings_maximum'];
		$property = sprintf( 'days_to_add_%s', $type );

		if ( 'min' === $type && $jckwds->days_to_add_min ) {
			return $jckwds->days_to_add_min;
		} elseif ( 'max' === $type && $jckwds->days_to_add_max ) {
			return $jckwds->days_to_add_max;
		}

		$max_days       = 'max' === $type ? $days : false;
		$next_timestamp = self::get_next_allowed_timestamp( self::get_current_timestamp(), $days, $max_days );

		$jckwds->$property = apply_filters(
			"iconic_wds_{$type}_delivery_date",
			array(
				'days_to_add' => $next_timestamp['count'],
				'timestamp'   => $next_timestamp['timestamp'],
			)
		);

		return $jckwds->$property;
	}

	/**
	 * Get same day date.
	 *
	 * @param string $format Format.
	 *
	 * @return mixed
	 */
	public static function get_same_day_date( $format = 'timestamp' ) {
		$same_day_timestamp = self::get_next_allowed_timestamp( self::get_current_timestamp() );

		if ( ! $same_day_timestamp ) {
			return apply_filters( 'iconic_wds_same_day_date', false, $format, $same_day_timestamp );
		}

		$same_day_formatted = 'timestamp' === $format ? $same_day_timestamp['timestamp'] : date_i18n( $format, $same_day_timestamp['timestamp'] );

		return apply_filters( 'iconic_wds_same_day_date', $same_day_formatted, $format, $same_day_timestamp['timestamp'] );
	}

	/**
	 * Get next day date.
	 *
	 * Next day should be the next allowed delivery day.
	 *
	 * @param string $format Format.
	 *
	 * @return mixed
	 */
	public static function get_next_day_date( $format = 'timestamp' ) {
		global $iconic_wds;

		$min_days           = (int) $iconic_wds->settings['datesettings_datesettings_minimum'];
		$next_day_timestamp = self::get_next_allowed_timestamp( self::get_current_timestamp(), $min_days > 1 ? $min_days : 1 );

		if ( ! $next_day_timestamp ) {
			return apply_filters( 'iconic_wds_next_day_date', false, $format, $next_day_timestamp );
		}

		$next_day_formatted = 'timestamp' === $format ? $next_day_timestamp['timestamp'] : date_i18n( $format, $next_day_timestamp['timestamp'] );

		return apply_filters( 'iconic_wds_next_day_date', $next_day_formatted, $format, $next_day_timestamp['timestamp'] );
	}

	/**
	 * Check if same day delivery is allowed
	 *
	 * @return mixed Returns true if allowed, or today's date if not
	 */
	public static function is_same_day_allowed() {
		global $jckwds;

		/**
		 * Allow plugins/themes to set "is same day delivery allowed".
		 *
		 * @param bool $allowed
		 */
		$allowed = apply_filters( 'iconic_wds_is_same_day_allowed', null );

		if ( null !== $allowed ) {
			return $allowed;
		}

		$same_day_cutoff = isset( $jckwds->settings['datesettings_datesettings_sameday_cutoff'] ) ? $jckwds->settings['datesettings_datesettings_sameday_cutoff'] : '';
		$same_day_cutoff = apply_filters( 'iconic_wds_same_day_cutoff', $same_day_cutoff );

		if ( empty( $same_day_cutoff ) ) {
			return true;
		}

		$same_day_cutoff_formatted = DateTime::createFromFormat( 'Ymd H:i', sprintf( '%s %s', $jckwds->current_ymd, $same_day_cutoff ), wp_timezone() );

		$now     = new DateTime( 'now', wp_timezone() );
		$in_past = $now >= $same_day_cutoff_formatted ? true : false;

		if ( $in_past ) {
			return self::get_same_day_date( 'D, jS M' );
		} else {
			return true;
		}
	}

	/**
	 * Check if next day delivery is allowed
	 *
	 * @return mixed Returns true if allowed, or tomorrow's date if not
	 */
	public static function is_next_day_allowed() {
		global $jckwds;

		/**
		 * Allow plugins/themes to set "is next day delivery allowed".
		 *
		 * @param bool $allowed
		 */
		$allowed = apply_filters( 'iconic_wds_is_next_day_allowed', null );

		if ( null !== $allowed ) {
			return $allowed;
		}

		$next_day_cutoff = isset( $jckwds->settings['datesettings_datesettings_nextday_cutoff'] ) ? $jckwds->settings['datesettings_datesettings_nextday_cutoff'] : '';
		$next_day_cutoff = apply_filters( 'iconic_wds_next_day_cutoff', $next_day_cutoff );

		if ( empty( $next_day_cutoff ) ) {
			return true;
		}

		$next_day_cutoff_formatted = DateTime::createFromFormat( 'Ymd H:i', sprintf( '%s %s', $jckwds->current_ymd, $next_day_cutoff ), wp_timezone() );

		$now     = new DateTime( 'now', wp_timezone() );
		$in_past = $now >= $next_day_cutoff_formatted ? true : false;

		if ( $in_past ) {
			return self::get_next_day_date( 'D, jS M' );
		} else {
			return true;
		}
	}

	/**
	 * Get date format based on settings
	 *
	 * @param string $js_format JS formatted date to convert to PHP format.
	 *
	 * @return string
	 */
	public static function date_format( $js_format = '' ) {
		global $jckwds;

		$js_format = empty( $js_format ) ? $jckwds->settings['datesettings_datesettings_dateformat'] : $js_format;

		$trans = array(
			// Days.
			'dd' => 'd',
			'd'  => 'j',
			'DD' => 'l',
			'o'  => 'z',

			// Months.
			'MM' => 'F',
			'M'  => 'M',
			'mm' => 'm',
			'm'  => 'n',

			// Years.
			'yy' => 'Y',
			'y'  => 'y',
		);

		return strtr( $js_format, $trans );
	}

	/**
	 * Convert date to database format (Y-m-d)
	 *
	 * @param string $date   Date.
	 * @param string $format Date Format.
	 *
	 * @return string
	 */
	public static function convert_date_for_database( $date, $format = 'Ymd' ) {
		$dformat = DateTime::createFromFormat( $format, $date, wp_timezone() );

		return $dformat->format( 'Y-m-d' );
	}

	/**
	 * Get current timestamp.
	 *
	 * @return float|int
	 */
	public static function get_current_timestamp() {
		return time() + wc_timezone_offset();
	}

	/**
	 * Get timezone offset for the given timestamp.
	 *
	 * This function is inspired by wc_timezone_offset().
	 * wc_timezone_offset() always returns the offset for today's date,
	 * which is inacurate during daylight savings.
	 * Hence this function, which returns offset for specified date.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return int
	 */
	public static function get_timezone_offset( $timestamp ) {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone ) {
			$timezone_object = new DateTimeZone( $timezone );
			$datetime        = new DateTime();
			$datetime->setTimestamp( $timestamp );

			return $timezone_object->getOffset( $datetime );
		} else {
			return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
		}
	}
}
