<?php
/**
 * WDS Settings.
 *
 * @package Iconic_WDS
 */

add_filter( 'wpsf_register_settings_jckwds', 'jckwds_settings' );

/**
 * Delivery Slots Settings
 *
 * @param array $wpsf_settings Settings.
 *
 * @return array
 */
function jckwds_settings( $wpsf_settings ) {
	global $jckwds;

	if ( ! $jckwds || ! function_exists( 'WC' ) ) {
		return $wpsf_settings;
	}

	$date_format = Iconic_WDS_Core_Settings::get_setting_from_db( 'datesettings_datesettings', 'dateformat' );
	$date_format = $date_format ? $date_format : 'dd/mm/yy';

	// Tabs.

	$wpsf_settings['tabs'][] = array(
		'id'    => 'general',
		'title' => __( 'General Settings', 'jckwds' ),
	);

	$wpsf_settings['tabs'][] = array(
		'id'    => 'datesettings',
		'title' => __( 'Date Settings', 'jckwds' ),
	);

	$wpsf_settings['tabs'][] = array(
		'id'    => 'timesettings',
		'title' => __( 'Time Settings', 'jckwds' ),
	);

	$wpsf_settings['tabs'][] = array(
		'id'    => 'holidays',
		'title' => __( 'Holidays', 'jckwds' ),
	);

	$wpsf_settings['tabs'][] = array(
		'id'    => 'reservations',
		'title' => __( 'Reservation Table', 'jckwds' ),
	);

	// Sections.

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'general',
		'section_id'          => 'setup',
		'section_title'       => __( 'General Setup', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'       => 'position',
				'title'    => __( 'Checkout Fields Position', 'jckwds' ),
				'subtitle' => __( 'Where should the date and time fields show on the checkout page?', 'jckwds' ),
				'type'     => 'select',
				'default'  => 'woocommerce_checkout_order_review',
				'choices'  => Iconic_WDS_Settings::get_field_position_choices(),
			),
			array(
				'id'          => 'position_priority',
				'title'       => __( 'Checkout Fields Position Priority', 'jckwds' ),
				'subtitle'    => __( 'Enter a number of priority, e.g. 10 is early/before, 50 is late/after', 'jckwds' ),
				'type'        => 'number',
				'default'     => '10',
				'placeholder' => '',
			),
			array(
				'id'          => 'display_for_virtual',
				'title'       => __( 'Display Fields Even When Shipping Is Not Required?', 'jckwds' ),
				'subtitle'    => __( 'Should we display the date and time fields even if shipping is not required at checkout?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'       => 'max_order_calculation_method',
				'title'    => __( 'Max Orders Calculation Method', 'jckwds' ),
				'subtitle' => __( 'Determine maximum orders limit by the number of orders placed or number of products purchased.', 'jckwds' ),
				'type'     => 'select',
				'default'  => 'orders',
				'choices'  => Iconic_WDS_Settings::get_max_order_calculation_methods(),
			),
			array(
				'id'       => 'labels',
				'title'    => __( 'Default Labels', 'jckwds' ),
				'subtitle' => __( 'Select which labels to use for the delivery slots checkout fields. You can override these based on the select shipping method below.', 'jckwds' ),
				'type'     => 'select',
				'default'  => 'delivery',
				'choices'  => array(
					'delivery'   => __( 'Delivery', 'jckwds' ),
					'collection' => __( 'Collection', 'jckwds' ),
				),
			),
			array(
				'id'       => 'shipping_methods',
				'title'    => __( 'Shipping Methods', 'jckwds' ),
				'subtitle' => __( 'Enable delivery slots for the following shipping methods.', 'jckwds' ),
				'type'     => 'custom',
				'default'  => array( 'any' ),
				'output'   => Iconic_WDS_Settings::get_field_labels_by_shipping_method(),
			),
			array(
				'id'       => 'exclude_products',
				'title'    => __( 'Exclude Products', 'jckwds' ),
				'subtitle' => __( 'If these products are in the cart, delivery date selection will be disabled.', 'jckwds' ),
				'type'     => 'custom',
				'default'  => array(),
				'output'   => Iconic_WDS_Settings::get_exclude_products_field(),
			),
			array(
				'id'       => 'exclude_products_condition',
				'title'    => __( 'Exclude Product Condition', 'jckwds' ),
				'subtitle' => __( 'Disable delivery date selection when any/all of the excluded products are in the cart.', 'jckwds' ),
				'type'     => 'select',
				'choices'  => array(
					'any' => 'Any',
					'all' => 'All',
				),
				'default'  => 'any',
			),
			array(
				'id'          => 'exclude_categories',
				'title'       => __( 'Exclude Categories', 'jckwds' ),
				'subtitle'    => __( 'If a product from these categories is in the cart, delivery date selection will be disabled.', 'jckwds' ),
				'type'        => 'checkboxes',
				'default'     => '',
				'placeholder' => '',
				'choices'     => Iconic_WDS_Settings::get_category_options(),
			),
			array(
				'id'       => 'exclude_categories_condition',
				'title'    => __( 'Exclude Category Condition', 'jckwds' ),
				'subtitle' => __( 'Disable delivery date selection when any/all products in the cart have at least 1 excluded category.', 'jckwds' ),
				'type'     => 'select',
				'choices'  => array(
					'any' => 'Any',
					'all' => 'All',
				),
				'default'  => 'any',
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'datesettings',
		'section_id'          => 'datesettings_setup',
		'section_title'       => __( 'Date Setup', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'          => 'mandatory',
				'title'       => __( 'Required', 'jckwds' ),
				'subtitle'    => __( 'Is the delivery date a required field at checkout?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 1,
				'placeholder' => '',
			),
			array(
				'id'          => 'show_description',
				'title'       => __( 'Show Description?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'uitheme',
				'title'       => __( 'Theme', 'jckwds' ),
				'subtitle'    => __( 'Select a theme for the front-end calendar at checkout.', 'jckwds' ),
				'type'        => 'select',
				'default'     => 'dark',
				'placeholder' => '',
				'choices'     => array(
					'dark'  => __( 'Dark', 'jckwds' ),
					'light' => __( 'Light', 'jckwds' ),
					'none'  => __( 'None', 'jckwds' ),
				),
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'datesettings',
		'section_id'          => 'datesettings',
		'section_title'       => __( 'Date Settings', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 10,
		'fields'              => array(
			array(
				'id'       => 'days',
				'title'    => __( 'Delivery Days', 'jckwds' ),
				'subtitle' => __( 'Which days do you deliver, and how many orders can you accept on any given day? Leave maximum orders blank for "unlimited".', 'jckwds' ),
				'type'     => 'custom',
				'default'  => array( 0, 1, 2, 3, 4, 5, 6 ),
				'output'   => Iconic_WDS_Settings::get_delivery_days_fields(),
			),
			array(
				'id'        => 'specific_days',
				'title'     => __( 'Specific Delivery Days', 'jckwds' ),
				'subtitle'  => __( 'Enter any specific days you would like to enable for delivery or collection.', 'jckwds' ),
				'type'      => 'group',
				'default'   => array(
					array(
						'row_id' => uniqid(),
					),
				),
				'subfields' => array(
					array(
						'id'         => 'date',
						'title'      => __( 'Date', 'jckwds' ),
						'type'       => 'date',
						'datepicker' => array(
							'dateFormat' => $date_format,
							'altFormat'  => 'yy-mm-dd',
							'altField'   => '#datesettings_datesettings_specific_days_0_alt_date',
						),
					),
					array(
						'id'    => 'alt_date',
						'title' => __( 'From', 'jckwds' ),
						'type'  => 'hidden',
					),
					array(
						'id'          => 'fee',
						// Translators: currency symbol.
						'title'       => sprintf( __( 'Fee (%s)', 'jckwds' ), get_woocommerce_currency_symbol() ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => 'E.g. 3.00',
					),
					array(
						'id'       => 'lockout',
						'title'    => __( 'Maximum Orders', 'jckwds' ),
						'subtitle' => __( 'Enter the maximum number of orders allowed for this date.', 'jckwds' ),
						'type'     => 'number',
						'default'  => '',
					),
					array(
						'id'      => 'repeat_yearly',
						'title'   => '',
						'desc'    => __( 'Repeat every year?', 'jckwds' ),
						'type'    => 'checkbox',
						'default' => '',
					),
					array(
						'id'      => 'bypass_max',
						'title'   => '',
						'desc'    => __( 'Bypass max selectable date setting?', 'jckwds' ),
						'type'    => 'checkbox',
						'default' => '',
					),
				),
			),
			array(
				'id'          => 'minmaxmethod',
				'title'       => __( 'Delivery Days Calculation Method', 'jckwds' ),
				'subtitle'    => __( 'Calculate minimum, maximum, same day, and next day delivery dates based on all days of the week, selected days only, or weekdays only.', 'jckwds' ),
				'type'        => 'select',
				'default'     => 'allowed',
				'placeholder' => '',
				'choices'     => array(
					'allowed'  => 'Selected Days Only',
					'all'      => 'All Days',
					'weekdays' => 'Weekdays Only',
				),
			),
			array(
				'id'          => 'skip_current',
				'title'       => __( 'Skip Current Day if Not a Selected Delivery Day?', 'jckwds' ),
				'subtitle'    => __( 'When checked, same day delivery will be classed as the next available delivery day.', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'minimum',
				'title'       => __( 'Minimum Selectable Date', 'jckwds' ),
				'subtitle'    => __( 'Days from now. Enter "0" for same day.', 'jckwds' ),
				'type'        => 'text',
				'default'     => '2',
				'placeholder' => '',
			),
			array(
				'id'          => 'maximum',
				'title'       => __( 'Maximum Selectable Date', 'jckwds' ),
				'subtitle'    => __( 'Days from now.', 'jckwds' ),
				'type'        => 'text',
				'default'     => '14',
				'placeholder' => '',
			),
			array(
				'id'         => 'sameday_cutoff',
				'title'      => __( 'Disable Same Day Delivery if Current Time is After (x)', 'jckwds' ),
				'type'       => 'time',
				'timepicker' => array(
					'amPmText' => array(
						__( 'AM', 'jckwds' ),
						__( 'PM', 'jckwds' ),
					),
				),
			),
			array(
				'id'         => 'nextday_cutoff',
				'title'      => __( 'Disable Next Day Delivery if Current Time is After (x)', 'jckwds' ),
				'type'       => 'time',
				'timepicker' => array(
					'amPmText' => array(
						__( 'AM', 'jckwds' ),
						__( 'PM', 'jckwds' ),
					),
				),
			),
			array(
				'id'          => 'week_limit',
				'title'       => __( 'Only Allow Deliveries Within the Current Week?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'last_day_of_week',
				'title'       => __( 'Last Day of the Week', 'jckwds' ),
				'subtitle'    => '',
				'type'        => 'select',
				'placeholder' => '',
				'choices'     => array(
					'sunday'    => __( 'Sunday', 'jckwds' ),
					'monday'    => __( 'Monday', 'jckwds' ),
					'tuesday'   => __( 'Tuesday', 'jckwds' ),
					'wednesday' => __( 'Wednesday', 'jckwds' ),
					'thursday'  => __( 'Thursday', 'jckwds' ),
					'friday'    => __( 'Friday', 'jckwds' ),
					'saturday'  => __( 'Saturday', 'jckwds' ),
				),
				'default'     => 'sunday',
			),
			array(
				'id'          => 'dateformat',
				'title'       => __( 'Date Format', 'jckwds' ),
				'subtitle'    => __( 'Available formats can be found <a href="http://api.jqueryui.com/datepicker/#utility-formatDate" target="_blank">here</a>.', 'jckwds' ),
				'type'        => 'text',
				'default'     => 'dd/mm/yy',
				'placeholder' => '',
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'datesettings',
		'section_id'          => 'fees',
		'section_title'       => __( 'Fees', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 20,
		'fields'              => array(
			array(
				'id'       => 'days',
				'title'    => sprintf( '%s (%s)', __( 'Days', 'jckwds' ), get_woocommerce_currency_symbol() ),
				'subtitle' => __( 'Fees applied to specific days of the week.', 'jckwds' ),
				'type'     => 'custom',
				'default'  => array(),
				'output'   => Iconic_WDS_Settings::get_day_fees_fields(),
			),
			array(
				'id'          => 'same_day',
				'title'       => sprintf( '%s (%s)', __( 'Same Day', 'jckwds' ), get_woocommerce_currency_symbol() ),
				'subtitle'    => __( 'Fee applied when a same day delivery is selected.', 'jckwds' ),
				'type'        => 'number',
				'default'     => '',
				'placeholder' => __( 'E.g. 3.00', 'jckwds' ),
			),
			array(
				'id'          => 'next_day',
				'title'       => sprintf( '%s (%s)', __( 'Next Day', 'jckwds' ), get_woocommerce_currency_symbol() ),
				'subtitle'    => __( 'Fee applied when a next day delivery is selected.', 'jckwds' ),
				'type'        => 'number',
				'default'     => '',
				'placeholder' => __( 'E.g. 3.00', 'jckwds' ),
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'timesettings',
		'section_id'          => 'timesettings_setup',
		'section_title'       => __( 'Time Setup', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'          => 'enable',
				'title'       => __( 'Enable Time Slots', 'jckwds' ),
				'subtitle'    => __( 'Check this box to enable time slots at checkout.', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 1,
				'placeholder' => '',
			),
			array(
				'id'          => 'mandatory',
				'title'       => __( 'Requried', 'jckwds' ),
				'subtitle'    => __( 'Is the time slot a required field at checkout?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 1,
				'placeholder' => '',
			),
			array(
				'id'          => 'show_description',
				'title'       => __( 'Show Description?', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'timeformat',
				'title'       => __( 'Time Format', 'jckwds' ),
				'subtitle'    => __( 'Select a time format for the frontend.', 'jckwds' ),
				'type'        => 'select',
				'default'     => 'H:i A',
				'placeholder' => '',
				'choices'     => array(
					'H:i A' => '13:30 PM',
					'H:i'   => '13:30',
					'h:i A' => '01:30 PM',
				),
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'timesettings',
		'section_id'          => 'timesettings_asap',
		'section_title'       => __( 'ASAP Delivery', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'          => 'enable',
				'title'       => __( 'Enable ASAP Delivery', 'jckwds' ),
				'subtitle'    => __( 'Allow your customers to choose ASAP for their selected delivery date, or a time slot.', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'       => 'lockout',
				'title'    => __( 'Maximum Orders', 'jckwds' ),
				'subtitle' => __( 'Enter the maximum number of orders allowed for the ASAP timeslot.', 'jckwds' ),
				'type'     => 'number',
				'default'  => '',
			),
			array(
				'id'          => 'fee',
				'title'       => __( 'ASAP Fee', 'jckwds' ),
				'subtitle'    => __( 'Fee applied when an ASAP delivery time is selected.', 'jckwds' ),
				'type'        => 'number',
				'default'     => '',
				'placeholder' => __( 'E.g. 3.00', 'jckwds' ),
			),
			array(
				'id'         => 'cutoff',
				'title'      => __( 'Same Day Cut Off', 'jckwds' ),
				'subtitle'   => __( 'Disable same day ASAP delivery slot if current time is after (x).', 'jckwds' ),
				'type'       => 'time',
				'timepicker' => array(
					'amPmText' => array(
						__( 'AM', 'jckwds' ),
						__( 'PM', 'jckwds' ),
					),
				),
			),
		),
	);

	$wpsf_settings['sections']['timesettings'] = array(
		'tab_id'              => 'timesettings',
		'section_id'          => 'timesettings',
		'section_title'       => __( 'Time Slot Configuration', 'jckwds' ),
		'section_description' => '',
		'section_order'       => 10,
		'fields'              => array(
			array(
				'id'          => 'calculate_tax',
				'title'       => __( 'Calculate Tax?', 'jckwds' ),
				'subtitle'    => __( 'Check this box to calculate tax on timeslot fees. If enabled, fees should be entered exclusive of tax.', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'cutoff',
				'title'       => __( 'Allow Bookings Up To (x) Minutes Before Slot', 'jckwds' ),
				'subtitle'    => __( 'This option will prevent bookings being made too close to the delivery time. Can be overridden on an individual time slot basis. (Check your timezone in WordPress Settings).', 'jckwds' ),
				'type'        => 'text',
				'default'     => '30',
				'placeholder' => '',
			),
			'timeslots' => array(
				'id'        => 'timeslots',
				'title'     => __( 'Time Slots', 'jckwds' ),
				'subtitle'  => __( 'Fill the Slot Duration and Slot Frequency fields to dynamically generate slots. Leave them empty to create a single time slot.', 'jckwds' ),
				'type'      => 'group',
				'row_title' => __( 'Time Slot', 'jckwds' ),
				'format'    => 'table',
				'default'   => array(
					array(
						'row_id'           => uniqid(),
						'duration'         => 30,
						'frequency'        => 30,
						'timefrom'         => '07:00',
						'timeto'           => '10:00',
						'cutoff'           => '',
						'lockout'          => 4,
						'shipping_methods' => array( 'any' ),
						'fee'              => '',
						'days'             => array( 0, 1, 2, 3, 4, 5, 6 ),
					),
					array(
						'row_id'           => uniqid(),
						'duration'         => '',
						'frequency'        => '',
						'timefrom'         => '12:00',
						'timeto'           => '14:00',
						'cutoff'           => '',
						'lockout'          => 2,
						'shipping_methods' => array( 'any' ),
						'fee'              => 5,
						'days'             => array( 1, 5 ),
					),
				),
				'subfields' => array(
					array(
						'id'          => 'duration',
						'title'       => __( 'Slot Duration - (x) Minutes per Slot', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => '',
					),
					array(
						'id'          => 'frequency',
						'title'       => __( 'Slot Frequency - Every (x) Minutes', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => '',
					),
					array(
						'id'         => 'timefrom',
						'title'      => __( 'From', 'jckwds' ),
						'type'       => 'time',
						'timepicker' => array(
							'amPmText' => array(
								__( 'AM', 'jckwds' ),
								__( 'PM', 'jckwds' ),
							),
						),
					),
					array(
						'id'         => 'timeto',
						'title'      => __( 'To', 'jckwds' ),
						'type'       => 'time',
						'timepicker' => array(
							'amPmText' => array(
								__( 'AM', 'jckwds' ),
								__( 'PM', 'jckwds' ),
							),
						),
					),
					array(
						'id'          => 'cutoff',
						'title'       => __( 'Allow Bookings Up To (x) Minutes Before Slot', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => '',
					),
					array(
						'id'          => 'lockout',
						'title'       => __( 'Maximum Orders per Time Slot', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => '',
					),
					'postcodes' => array(
						'id'          => 'postcodes',
						'title'       => __( 'Postcodes', 'jckwds' ),
						'type'        => 'text',
						'placeholder' => '',
					),
					array(
						'id'          => 'fee',
						// Translators: currency symbol.
						'title'       => sprintf( __( 'Fee (%s)', 'jckwds' ), get_woocommerce_currency_symbol() ),
						'subtitle'    => '',
						'type'        => 'number',
						'placeholder' => 'E.g. 3.00',
					),
					array(
						'id'          => 'days',
						'title'       => __( 'Days', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'checkboxes',
						'placeholder' => '',
						'default'     => array( 0, 1, 2, 3, 4, 5, 6 ),
						'choices'     => Iconic_WDS_Settings::get_time_slot_day_choices(),
					),
				),
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'holidays',
		'section_id'          => 'holidays',
		'section_title'       => __( 'Holidays', 'jckwds' ),
		'section_description' => __( 'Please add any holidays where deliveries should not be made.', 'jckwds' ),
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'        => 'holidays',
				'title'     => __( 'Holidays', 'jckwds' ),
				'subtitle'  => __( 'For single days, just enter a date in the "From" field. For ranges, enter a "From" and "To" date. Ranges are up to and including the dates you enter.', 'jckwds' ),
				'type'      => 'group',
				'row_title' => __( 'Holiday', 'jckwds' ),
				'format'    => 'table',
				'subfields' => array(
					array(
						'id'         => 'date',
						'title'      => __( 'From', 'jckwds' ),
						'type'       => 'date',
						'datepicker' => array(
							'dateFormat' => $date_format,
							'altFormat'  => 'dd/mm/yy',
							'altField'   => '#holidays_holidays_holidays_0_alt_date',
						),
					),
					array(
						'id'    => 'alt_date',
						'title' => __( 'From', 'jckwds' ),
						'type'  => 'hidden',
					),
					array(
						'id'         => 'date_to',
						'title'      => __( 'To', 'jckwds' ),
						'type'       => 'date',
						'datepicker' => array(
							'dateFormat' => $date_format,
							'altFormat'  => 'dd/mm/yy',
							'altField'   => '#holidays_holidays_holidays_0_alt_date_to',
						),
					),
					array(
						'id'    => 'alt_date_to',
						'title' => __( 'to', 'jckwds' ),
						'type'  => 'hidden',
					),
					array(
						'id'          => 'shipping_methods',
						'title'       => __( 'Shipping Methods', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'checkboxes',
						'placeholder' => '',
						'choices'     => $jckwds->get_shipping_method_options(),
					),
					array(
						'id'          => 'name',
						'title'       => __( 'Name', 'jckwds' ),
						'subtitle'    => '',
						'type'        => 'text',
						'default'     => '',
						'placeholder' => __( 'e.g. Christmas', 'jckwds' ),
					),
					array(
						'id'      => 'repeat_yearly',
						'title'   => '',
						'desc'    => __( 'Repeat every year?', 'jckwds' ),
						'type'    => 'checkbox',
						'default' => '',
					),
				),
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'reservations',
		'section_id'          => 'reservations',
		'section_title'       => __( 'Reservations', 'jckwds' ),
		'section_description' => __( 'You can insert a reservation table using the shortcode <strong>[jckwds]</strong>. This allows your customers to reserve a delivery time and date while they shop. <strong>Note:</strong> Time Slots should be enabled if you want to use the reservation table.', 'jckwds' ),
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'          => 'expires',
				'title'       => __( 'Expiration', 'jckwds' ),
				'subtitle'    => __( 'Reservations expire after (x) Minutes.', 'jckwds' ),
				'type'        => 'text',
				'default'     => '30',
				'placeholder' => '30',
			),
			array(
				'id'          => 'hide_unavailable_dates',
				'title'       => __( 'Hide Unavailable Dates?', 'jckwds' ),
				'subtitle'    => __( 'Check this box to hide any dates if there are no available time slots.', 'jckwds' ),
				'type'        => 'checkbox',
				'default'     => 0,
				'placeholder' => '',
			),
			array(
				'id'          => 'columns',
				'title'       => __( 'Date Columns', 'jckwds' ),
				'subtitle'    => __( 'How many date columns should the reservation table display?', 'jckwds' ),
				'type'        => 'text',
				'default'     => '3',
				'placeholder' => '3',
			),
			array(
				'id'          => 'selection_type',
				'title'       => __( 'Selection Type', 'jckwds' ),
				'subtitle'    => __( 'Choose the selection type for the time slots in the table.', 'jckwds' ),
				'type'        => 'select',
				'default'     => 'fee',
				'placeholder' => '',
				'choices'     => array(
					'checkbox' => __( 'Checkbox', 'jckwds' ),
					'fee'      => __( 'Fee', 'jckwds' ),
				),
			),
			array(
				'id'          => 'dateformat',
				'title'       => __( 'Header Date Format', 'jckwds' ),
				'subtitle'    => __( 'Available formats can be found <a href="http://api.jqueryui.com/datepicker/#utility-formatDate" target="_blank">here</a>.', 'jckwds' ),
				'type'        => 'text',
				'default'     => 'j D',
				'placeholder' => '',
			),
		),
	);

	$wpsf_settings['sections'][] = array(
		'tab_id'              => 'reservations',
		'section_id'          => 'styling',
		'section_title'       => __( 'Table Styling', 'jckwds' ),
		'section_description' => __( 'Customise the look of your reservation table to match your website.', 'jckwds' ),
		'section_order'       => 0,
		'fields'              => array(
			array(
				'id'       => 'thbgcol',
				'title'    => __( 'Header Cell Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#333333',
			),
			array(
				'id'       => 'thbordercol',
				'title'    => __( 'Header Cell Border Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#2A2A2A',
			),
			array(
				'id'       => 'thfontcol',
				'title'    => __( 'Header Cell Font Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#FFFFFF',
			),
			array(
				'id'       => 'tharrcol',
				'title'    => __( 'Arrow Icon Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#CCCCCC',
			),
			array(
				'id'       => 'tharrhovcol',
				'title'    => __( 'Arrow Icon Hover Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#FFFFFF',
			),
			array(
				'id'       => 'reservebgcol',
				'title'    => __( 'Reserve Cell Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#FFFFFF',
			),
			array(
				'id'       => 'reservebordercol',
				'title'    => __( 'Reserve Cell Border Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#EAEAEA',
			),
			array(
				'id'       => 'reserveiconcol',
				'title'    => __( 'Reserve Icon Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#B7B7B7',
			),
			array(
				'id'       => 'reserveiconhovcol',
				'title'    => __( 'Reserve Icon Hover Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#848484',
			),
			array(
				'id'       => 'unavailcell',
				'title'    => __( 'Unavailable Cell Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#F7F7F7',
			),
			array(
				'id'       => 'reservedbgcol',
				'title'    => __( 'Reserved Cell Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#15b374',
			),
			array(
				'id'       => 'reservediconcol',
				'title'    => __( 'Reserved Icon Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#FFFFFF',
			),
			array(
				'id'       => 'loadingiconcol',
				'title'    => __( 'Loading Icon Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#666666',
			),
			array(
				'id'       => 'lockiconcol',
				'title'    => __( 'Lock Icon Colour', 'jckwds' ),
				'subtitle' => '',
				'type'     => 'color',
				'default'  => '#666666',
			),
		),
	);

	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		$wpsf_settings['sections']['timesettings']['fields']['timeslots']['subfields']['postcodes'] = array(
			'id'          => 'shipping_methods',
			'title'       => __( 'Shipping Methods', 'jckwds' ),
			'subtitle'    => '',
			'type'        => 'checkboxes',
			'placeholder' => '',
			'choices'     => $jckwds->get_shipping_method_options(),
		);
	}

	return $wpsf_settings;
}
