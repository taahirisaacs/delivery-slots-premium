<?php
/**
 * Plugin Name: WooCommerce Delivery Slots by Iconic
 * Plugin URI: https://iconicwp.com/products/woocommerce-delivery-slots/
 * Description: Allow your customers to select a delivery slot for their order
 * Version: 1.14.0
 * Author: Iconic
 * Author URI: https://iconicwp.com
 * Author Email: support@iconicwp.com
 * Text Domain: jckwds
 * WC requires at least: 2.6.14
 * WC tested up to: 5.1.0
 *
 * @package Iconic_WDS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 *
 * @class Iconic_WDS
 */
class Iconic_WDS {
	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public static $name = 'WooCommerce Delivery Slots';

	/**
	 * Plugin shortname.
	 *
	 * @var string
	 */
	public static $shortname = 'Delivery Slots';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public static $slug = 'jckwds';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $version = '1.14.0';

	/**
	 * Database version.
	 *
	 * @var string
	 */
	public $db_version = '1.6';

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Guest user_id cookie name.
	 *
	 * @var string
	 */
	public $guest_user_id_cookie_name = 'jckwds-guest-user-id';

	/**
	 * WP Settings framework option group.
	 *
	 * @var string
	 */
	public $option_group;

	/**
	 * User ID.
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * Timeslot meta key.
	 *
	 * @var string
	 */
	public $timeslot_meta_key = 'jckwds_timeslot';

	/**
	 * Date meta key.
	 *
	 * @var string
	 */
	public $date_meta_key = 'jckwds_date';

	/**
	 * Timestamp meta key.
	 *
	 * @var string
	 */
	public $timestamp_meta_key = 'jckwds_timestamp';

	/**
	 * Resevation table name.
	 *
	 * @var string
	 */
	public $reservations_db_table_name;

	/**
	 * Timeslot date transient name.
	 *
	 * @var string
	 */
	public $timeslot_data_transient_name;

	/**
	 * Current day number.
	 *
	 * @var int
	 */
	public $current_day_number;

	/**
	 * Current day in ymd format.
	 *
	 * @var string
	 */
	public $current_ymd;

	/**
	 * Holidays Formatted.
	 *
	 * @var array
	 */
	public $holidays_formatted = array();

	/**
	 * Available shipping methods.
	 *
	 * @var array
	 */
	public $shipping_methods = array();

	/**
	 * Allowed shipping days.
	 *
	 * @var array
	 */
	public $allowed_delivery_days = array();

	/**
	 * Days to add, min.
	 *
	 * @var boolean
	 */
	public $days_to_add_min = false;

	/**
	 * Days to add, max.
	 *
	 * @var boolean
	 */
	public $days_to_add_max = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
			add_action( 'admin_notices', array( $this, 'php_version_error' ) );

			return false;
		}

		$this->define_constants();
		$this->setup_autoloader();

		if ( ! Iconic_WDS_Core_Helpers::is_plugin_active( 'woocommerce/woocommerce.php' ) && ! Iconic_WDS_Core_Helpers::is_plugin_active( 'woocommerce-old/woocommerce.php' ) ) {
			return;
		}

		$this->load_classes();
		$this->setup_constants();
		$this->add_compatibility();

		add_action( 'set_current_user', array( $this, 'set_user_id' ) );
		add_action( 'init', array( $this, 'initiate' ) );
	}

	/**
	 * Define Constants.
	 */
	private function define_constants() {
		$this->define( 'ICONIC_WDS_PATH', plugin_dir_path( __FILE__ ) );
		$this->define( 'ICONIC_WDS_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'ICONIC_WDS_INC_PATH', ICONIC_WDS_PATH . 'inc/' );
		$this->define( 'ICONIC_WDS_VENDOR_PATH', ICONIC_WDS_INC_PATH . 'vendor/' );
		$this->define( 'ICONIC_WDS_BASENAME', plugin_basename( __FILE__ ) );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Definition name.
	 * @param string|bool $value Definition value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Setup Constants
	 */
	public function setup_constants() {
		global $wpdb;

		$this->reservations_db_table_name   = $wpdb->prefix . self::$slug;
		$this->timeslot_data_transient_name = sprintf( '%s-timeslot-data', self::$slug );
		$this->current_day_number           = absint( current_time( 'w' ) );
		$this->current_ymd                  = current_time( 'Ymd' );
	}

	/**
	 * Setup autoloader.
	 */
	private function setup_autoloader() {
		require_once ICONIC_WDS_INC_PATH . 'class-core-autoloader.php';

		Iconic_WDS_Core_Autoloader::run(
			array(
				'prefix'   => 'Iconic_WDS_',
				'inc_path' => ICONIC_WDS_INC_PATH,
			)
		);
	}

	/**
	 * Load classes.
	 */
	private function load_classes() {
		$this->init_licence();

		Iconic_WDS_Core_Settings::run(
			array(
				'vendor_path'   => ICONIC_WDS_VENDOR_PATH,
				'title'         => self::$name,
				'version'       => self::$version,
				'menu_title'    => self::$shortname,
				'settings_path' => ICONIC_WDS_INC_PATH . 'admin/settings.php',
				'option_group'  => 'jckwds',
				'docs'          => array(
					'collection'      => '/collection/120-woocommerce-delivery-slots',
					'troubleshooting' => '/collection/120-woocommerce-delivery-slots',
					'getting-started' => '/category/123-getting-started',
				),
				'cross_sells'   => array(
					'iconic-woo-show-single-variations',
					'iconic-woothumbs',
				),
			)
		);

		Iconic_WDS_Settings::run();
		Iconic_WDS_Reservations::run();
		Iconic_WDS_API::init();
		Iconic_WDS_Ajax::init();
		Iconic_WDS_Order::run();
		Iconic_WDS_Helpers::run();
		Iconic_WDS_Shortcodes::run();
		Iconic_WDS_Checkout::run();
	}

	/**
	 * Init licence class.
	 */
	public function init_licence() {
		Iconic_WDS_Core_Licence::run(
			array(
				'basename' => ICONIC_WDS_BASENAME,
				'urls'     => array(
					'product'  => 'https://iconicwp.com/products/woocommerce-delivery-slots',
					'settings' => admin_url( 'admin.php?page=jckwds-settings' ),
					'account'  => admin_url( 'admin.php?page=jckwds-settings-account' ),
				),
				'paths'    => array(
					'inc'    => ICONIC_WDS_INC_PATH,
					'plugin' => ICONIC_WDS_PATH,
					'file'   => __FILE__,
				),
				'freemius' => array(
					'id'         => '1038',
					'slug'       => 'iconic-woo-delivery-slots',
					'public_key' => 'pk_ae98776906ff416522057aab876c0',
					'menu'       => array(
						'slug' => 'jckwds-settings',
					),
				),
			)
		);
	}

	/**
	 * Set settings.
	 *
	 * @param array $settings Settings array.
	 */
	public function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Add third party compatibility.
	 */
	public function add_compatibility() {
		Iconic_WDS_Compat_Flexible_Shipping::run();
		Iconic_WDS_Compat_Table_Rate_Shipping::run();
		Iconic_WDS_Compat_Bootstrap_Date::run();
		Iconic_WDS_Compat_Pdf_Invoices_Packing_Slips::run();
		Iconic_WDS_Compat_Lead_Time::run();
		Iconic_WDS_Compat_Multistep_Checkout::run();
		Iconic_WDS_Compat_Woocommerce_Advanced_Shipping::run();
	}

	/**
	 * PHP Version Error Message
	 */
	public function php_version_error() {
		// Translators: PHP version.
		$message = sprintf( __( "You need to be running PHP 5.3+ for Delivery Slots to work. You're on %s.", 'jckwds' ), PHP_VERSION );

		echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Runs when the plugin is initialized
	 */
	public function initiate() {
		// Setup localization.
		load_plugin_textdomain( 'jckwds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		if ( is_admin() ) {
			add_filter( 'option_page_capability_' . self::$slug, array( $this, 'option_page_capability' ) );

			if ( ! Iconic_WDS_Core_Licence::has_valid_licence() ) {
				return;
			}

			add_action( 'admin_menu_jckwds', array( $this, 'setup_deliveries_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
			add_action( 'setted_transient', array( __CLASS__, 'on_update_shipping' ), 10, 3 );
		} else {
			if ( ! Iconic_WDS_Core_Licence::has_valid_licence() ) {
				return;
			}

			add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			add_action( 'wp_head', array( $this, 'dynamic_css' ) );
			add_shortcode( 'jckwds', array( $this, 'reservation_table_shortcode' ) );
		}

		// WooCommerce Actions and Hooks.

		// Less than 2.3.0.
		if ( version_compare( $this->get_woo_version_number(), '2.3.0', '<' ) ) {
			add_filter( 'woocommerce_email_order_meta_keys', array( $this, 'email_order_meta_keys' ) );
		}

		// Add fee at checkout, if required.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'check_fee' ), 10 );
		// Add fee.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_timeslot_fee' ), 10 );

		add_filter(
			'woocommerce_update_order_review_fragments',
			array(
				$this,
				'update_order_review_fragments',
			),
			10,
			1
		);

		$this->position_checkout_fields();
	}

	/**
	 * Transition settings
	 */
	public function transition_settings() {
		$new_settings = get_option( 'jckwds_settings' );
		$old_settings = get_option( 'jckdeliveryslots_settings' );

		if ( ! $new_settings && $old_settings ) {
			$old_settings_formatted = array();

			foreach ( $old_settings as $setting_name => $value ) {
				$old_settings_formatted[ $setting_name ] = $value;

				if ( 'timesettings_timesettings_timeslots' === $setting_name ) {
					if ( ! empty( $value ) ) {
						foreach ( $value as $index => $timeslot ) {
							$old_settings_formatted[ $setting_name ][ $index ]['timefrom'] = $timeslot['timefrom']['time'];
							$old_settings_formatted[ $setting_name ][ $index ]['timeto']   = $timeslot['timeto']['time'];
						}
					}
				}

				if ( 'holidays_holidays_holidays' === $setting_name ) {
					if ( ! empty( $value ) ) {
						foreach ( $value as $index => $holiday ) {
							$old_settings_formatted[ $setting_name ][ $index ]['date'] = $holiday['date']['date'];
						}
					}
				}

				if ( 'datesettings_datesettings_sameday_cutoff' === $setting_name || 'datesettings_datesettings_nextday_cutoff' === $setting_name ) {
					if ( ! empty( $value ) ) {
						$old_settings_formatted[ $setting_name ] = $value['time'];
					}
				}
			}

			update_option( 'jckwds_settings', $old_settings_formatted );
		}
	}

	/**
	 * Admin: Allow shop managers to save options.
	 *
	 * @param string $capability User capability.
	 *
	 * @return string
	 */
	public function option_page_capability( $capability ) {
		return 'manage_woocommerce';
	}

	/**
	 * Admin: Setup Deliveries page.
	 */
	public function setup_deliveries_page() {
		$deliveries_page = add_submenu_page(
			'woocommerce',
			__( 'Deliveries', 'jckwds' ),
			sprintf( '<span class="fs-submenu-item fs-sub woothumbs">%s</span>', __( 'Deliveries', 'jckwds' ) ),
			'manage_woocommerce',
			self::$slug . '-deliveries',
			array(
				$this,
				'deliveries_page_display',
			)
		);

		$page = filter_input( INPUT_GET, 'page' );

		if ( $page && self::$slug . '-deliveries' === $page ) {
			// Woo styles.
			wp_enqueue_style( 'admin_enqueue_styles-' . $deliveries_page, WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );

			// Woo scripts register.
			wp_register_script(
				'woocommerce_admin',
				WC()->plugin_url() . '/assets/js/admin/woocommerce_admin.min.js',
				array(
					'jquery',
					'jquery-blockui',
					'jquery-ui-sortable',
					'jquery-ui-widget',
					'jquery-ui-core',
					'jquery-tiptip',
				),
				WC_VERSION,
				true
			);
			wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), WC_VERSION, true );

			// Woo scripts enqueue.
			wp_enqueue_script( 'jquery-tiptip' );
			wp_enqueue_script( 'woocommerce_admin' );
		}
	}

	/**
	 * Admin: Display Deliveries page
	 */
	public function deliveries_page_display() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'jckwds' ) );
		}

		require_once 'inc/admin/deliveries.php';
	}

	/**
	 * Frontend: Position the checkout fields
	 */
	public function position_checkout_fields() {
		add_action(
			$this->settings['general_setup_position'],
			array(
				$this,
				'display_checkout_fields',
			),
			$this->settings['general_setup_position_priority']
		);
	}

	/**
	 * Helper: Display reservations in a table
	 *
	 * @param array $reservations Array of reservations.
	 */
	public function reservations_layout( $reservations ) {
		if ( empty( $reservations['results'] ) ) {
			if ( $reservations['processed'] ) {
				echo '<p>' . esc_html__( 'There are currently no upcoming deliveries.', 'jckwds' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'There are currently no active reservations.', 'jckwds' ) . '</p>';
			}

			return;
		}
		?>

		<style>
			.iconic-wds-key__dot,
			.iconic-wds-delivery__date:before {
				content: "";
				display: inline-block;
				width: 8px;
				height: 8px;
				border-radius: 4px;
				background: #D2E4E6;
				margin: 0 5px 0 0;
			}

			.iconic-wds-key__dot--same-day,
			.iconic-wds-delivery--same-day .iconic-wds-delivery__date:before {
				background-color: #F65536;
			}

			.iconic-wds-key__dot--next-day,
			.iconic-wds-delivery--next-day .iconic-wds-delivery__date:before {
				background-color: #3185FC;
			}

			.iconic-wds-key {
				list-style: none none outside;
				padding: 0;
				margin: 0 0 20px;
			}

			.iconic-wds-key li {
				display: inline-block;
				margin: 0 10px 0 0;
			}

			.iconic-wds-delivery__mobile_time {
				display: none;
				margin-left: 10px;
			}

			@media (max-width: 782px) {
				.iconic-wds-delivery__mobile_time {
					display: inline-block;
				}
			}
		</style>

		<ul class="iconic-wds-key">
			<li><strong><?php esc_html_e( 'Key:', 'jckwds' ); ?></strong></li>
			<li>
				<span class="iconic-wds-key__dot iconic-wds-key__dot--same-day"></span><?php esc_html_e( 'Today', 'jckwds' ); ?>
			</li>
			<li>
				<span class="iconic-wds-key__dot iconic-wds-key__dot--next-day"></span><?php esc_html_e( 'Tomorrow', 'jckwds' ); ?>
			</li>
			<li><span class="iconic-wds-key__dot"></span><?php esc_html_e( 'Upcoming', 'jckwds' ); ?></li>
		</ul>

		<table class="wp-list-table widefat fixed striped" cellspacing="0">
			<thead>
			<tr>
				<th class="column-primary" scope="col"><?php esc_html_e( 'Date', 'jckwds' ); ?></th>
				<?php if ( $this->settings['timesettings_timesettings_setup_enable'] ) { ?>
					<th scope="col"><?php esc_html_e( 'Time Slot', 'jckwds' ); ?></th>
				<?php } ?>
				<?php if ( $reservations['processed'] ) { ?>
					<th scope="col"><?php esc_html_e( 'Order', 'jckwds' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Ship to', 'jckwds' ); ?></th>
				<?php } ?>
				<th scope="col"><?php esc_html_e( 'Customer Name', 'jckwds' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Customer Email', 'jckwds' ); ?></th>
				<?php if ( $reservations['processed'] ) { ?>
					<th id="order_status" class="manage-column column-order_status" scope="col">
						<?php esc_html_e( 'Status', 'jckwds' ); ?>
					</th>
				<?php } ?>
				<?php
				do_action( 'iconic_wds_admin_deliveries_table_heading' );
				?>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $reservations['results'] as $reservation ) { ?>
				<?php
				$classes = array(
					'iconic-wds-delivery',
				);

				if ( $reservation->iconic_wds['same_day'] ) {
					$classes[] = 'iconic-wds-delivery--same-day';
				}

				if ( $reservation->iconic_wds['next_day'] ) {
					$classes[] = 'iconic-wds-delivery--next-day';
				}
				?>
				<tr class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
					<td class='column-primary'>
						<strong class="iconic-wds-delivery__date"><?php echo esc_html( $reservation->iconic_wds['date_formatted'] ); ?></strong>
						<button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'jckwds' ); ?></span></button>
						<?php if ( $this->settings['timesettings_timesettings_setup_enable'] ) { ?>
							<span class='iconic-wds-delivery__mobile_time'>
								<?php if ( $reservation->asap ) { ?>
									<?php esc_html_e( 'ASAP', 'jckwds' ); ?>
								<?php } else { ?>
									<?php echo esc_html( $reservation->iconic_wds['time_slot_formatted'] ); ?>
								<?php } ?>
							</span>
						<?php } ?>
					</td>
					<?php if ( $this->settings['timesettings_timesettings_setup_enable'] ) { ?>
						<td data-colname="<?php esc_html_e( 'Timeslot', 'jckwds' ); ?>">
							<?php if ( $reservation->asap ) { ?>
								<?php esc_html_e( 'ASAP', 'jckwds' ); ?>
							<?php } else { ?>
								<?php echo esc_html( empty( $reservation->starttime ) ? '&mdash;' : $reservation->iconic_wds['time_slot_formatted'] ); ?>
							<?php } ?>
						</td>
					<?php } ?>
					<?php if ( $reservations['processed'] ) { ?>
						<td data-colname="<?php esc_html_e( 'Order', 'jckwds' ); ?>">
							<div><?php echo wp_kses_post( $reservation->order_edit ); ?></div>
							<div><span class="description"><?php echo wp_kses_post( $reservation->order_items ); ?></span></div>
						</td>
						<td data-colname="<?php esc_html_e( 'Ship to', 'jckwds' ); ?>">
							<div><strong><?php echo esc_html( $reservation->method_label ); ?></strong></div>
							<div><?php echo wp_kses_post( $reservation->address_link ); ?></div>
							<div>
								<span class="description"><?php printf( '%s %s', esc_html__( 'via', 'jckwds' ), esc_html( $reservation->shipping_method ) ); ?></span>
							</div>
						</td>
					<?php } ?>
					<td data-colname="<?php esc_html_e( 'Customer Name', 'jckwds' ); ?>"><?php echo esc_html( $reservation->billing_name ); ?></td>
					<td data-colname="<?php esc_html_e( 'Customer Email', 'jckwds' ); ?>"><?php echo wp_kses_post( $reservation->billing_email ); ?></td>
					<?php if ( $reservations['processed'] ) { ?>
						<td data-colname="<?php esc_html_e( 'Status', 'jckwds' ); ?>" class="order_status column-order_status">
							<?php echo wp_kses_post( $reservation->order_status_badge ); ?>
						</td>
					<?php } ?>
					<?php
					do_action( 'iconic_wds_admin_deliveries_table_body_cell', $reservation );
					?>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<?php
	}

	/**
	 * Helper: Get Checkout fields data
	 *
	 * @param boolean $admin Is Admin.
	 *
	 * @return array
	 */
	public function get_checkout_fields_data( $admin = false ) {
		$fields   = array();
		$reserved = $this->get_reserved_slot();
		$order    = null;

		if ( $admin ) {
			global $post;
			$order = wc_get_order( $post->ID );
		}

		$fields['jckwds-delivery-date'] = array(
			'value'      => '',
			'field_args' => array(
				'type'              => 'text',
				'label'             => Iconic_WDS_Helpers::get_label( 'date', $order ),
				'required'          => $this->settings['datesettings_datesettings_setup_mandatory'],
				'class'             => array( 'jckwds-delivery-date', 'form-row-wide' ),
				'placeholder'       => Iconic_WDS_Helpers::get_label( 'select_date', $order ),
				'custom_attributes' => array( 'readonly' => 'true' ),
				'description'       => $admin ? '' : ( $this->settings['datesettings_datesettings_setup_show_description'] ? Iconic_WDS_Helpers::get_label( 'choose_date', $order ) : false ),
			),
		);

		$fields['jckwds-delivery-date-ymd'] = array(
			'value'      => '',
			'field_args' => array(
				'type'     => 'hidden',
				'label'    => '',
				'required' => false,
			),
		);

		if ( $reserved ) {
			$fields['jckwds-delivery-date']['value']     = $reserved['date']['formatted'];
			$fields['jckwds-delivery-date-ymd']['value'] = $reserved['date']['id'];
		}

		if ( $this->settings['timesettings_timesettings_setup_enable'] ) {
			$fields['jckwds-delivery-time'] = array(
				'value'      => '',
				'field_args' => array(
					'type'        => 'select',
					'label'       => Iconic_WDS_Helpers::get_label( 'time_slot', $order ),
					'required'    => $this->settings['timesettings_timesettings_setup_mandatory'],
					'class'       => array( 'jckwds-delivery-time', 'form-row-wide' ),
					'options'     => array(
						0 => Iconic_WDS_Helpers::get_label( 'select_date_first' ),
					),
					'description' => $admin ? '' : ( $this->settings['timesettings_timesettings_setup_show_description'] ? Iconic_WDS_Helpers::get_label( 'choose_time_slot', $order ) : false ),
				),
			);

			if ( $reserved && ! $admin ) {
				$fields['jckwds-delivery-time']['value']                    = $this->get_timeslot_value( $reserved['time'] );
				$fields['jckwds-delivery-time']['field_args']['class'][]    = 'jckwds-delivery-time--has-reservation';
				$fields['jckwds-delivery-time']['field_args']['options'][0] = Iconic_WDS_Helpers::get_label( 'select_time_slot', $order );

				$available_slots = $this->slots_available_on_date( $reserved['date']['id'] );

				if ( $available_slots && ! empty( $available_slots ) ) {
					foreach ( $available_slots as $available_slot ) {
						$fields['jckwds-delivery-time']['field_args']['options'][ $available_slot['value'] ] = $available_slot['formatted_with_fee'];
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Helper: Get timeslot select value
	 * Format a timeslot for use in a select field.
	 *
	 * @param array $timeslot Timeslot.
	 *
	 * @return str
	 */
	public function get_timeslot_value( $timeslot ) {
		return sprintf( '%s|%01.2f', $timeslot['id'], '' === $timeslot['fee']['value'] ? 0 : $timeslot['fee']['value'] );
	}

	/**
	 * Frontend: Display the checkout fields.
	 */
	public function display_checkout_fields() {
		$fields = $this->get_checkout_fields_data();
		$active = Iconic_WDS_Checkout::is_delivery_slots_allowed();

		include 'templates/checkout-fields.php';
	}

	/**
	 * Helper: Add timestamp order meta
	 *
	 * @param string $date     Ymd.
	 * @param array  $timeslot get_timeslot_data().
	 * @param int    $order_id Order ID.
	 *
	 * @return bool
	 */
	public function add_timestamp_order_meta( $date, $timeslot, $order_id ) {
		if ( empty( $date ) ) {
			return false;
		}

		$time = '10:00';

		if ( ! empty( $timeslot ) ) {
			$time = $timeslot['timefrom']['time'];
		}

		// Add meta to order for "ordering".
		$datetime = DateTime::createFromFormat( 'Ymd H:i', sprintf( '%s %s', $date, $time ), wp_timezone() );

		if ( ! $datetime ) {
			return false;
		}

		$timestamp = $datetime->getTimestamp();

		update_post_meta( $order_id, $this->timestamp_meta_key, $timestamp );

		return true;
	}

	/**
	 * Helper: Display Date and Timeslot
	 *
	 * @param WC_Order $order      Order.
	 * @param bool     $show_title Show title.
	 * @param bool     $plain_text Plain text.
	 */
	public function display_date_and_timeslot( $order, $show_title = false, $plain_text = false ) {
		$date_time = $this->has_date_or_time( $order );

		if ( ! $date_time ) {
			return;
		}

		$delivery_details_text = Iconic_WDS_Helpers::get_label( 'details', $order );
		$delivery_date_text    = Iconic_WDS_Helpers::get_label( 'date', $order );
		$time_slot_text        = Iconic_WDS_Helpers::get_label( 'time_slot', $order );
		$date                  = empty( $date_time['date'] ) ? false : apply_filters( 'iconic_wds_date_display', $date_time['date'], $date_time );
		$time                  = empty( $date_time['time'] ) ? false : apply_filters( 'iconic_wds_time_display', $date_time['time'], $date_time );

		if ( $plain_text ) {
			echo "\n\n==========\n\n";

			if ( $show_title ) {
				printf( "%s \n", esc_html( strtoupper( $delivery_details_text ) ) );
			}

			if ( $date_time['date'] ) {
				printf( "\n%s: %s", esc_html( $delivery_date_text ), esc_html( $date ) );
			}

			if ( $date_time['time'] ) {
				printf( "\n%s: %s", esc_html( $time_slot_text ), esc_html( $time ) );
			}

			echo "\n\n==========\n\n";
		} else {
			if ( $show_title ) {
				printf( '<h2>%s</h2>', esc_html( $delivery_details_text ) );
			}

			if ( $date_time['date'] ) {
				printf( '<p><strong>%s</strong> <br>%s</p>', esc_html( $delivery_date_text ), esc_html( $date ) );
			}

			if ( $date_time['time'] ) {
				printf( '<p><strong>%s</strong> <br>%s</p>', esc_html( $time_slot_text ), esc_html( $time ) );
			}
		}
	}

	/**
	 * Frontend: Add date and timeslot to order email.
	 *
	 * @param array $keys Meta Keys.
	 *
	 * @return array
	 */
	public function email_order_meta_keys( $keys ) {
		$date_label               = Iconic_WDS_Helpers::get_label( 'date' );
		$time_slot_label          = Iconic_WDS_Helpers::get_label( 'time_slot' );
		$keys[ $date_label ]      = $this->date_meta_key;
		$keys[ $time_slot_label ] = $this->timeslot_meta_key;

		return $keys;
	}

	/**
	 * Helper: Check if order has date or time
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool|array
	 */
	public function has_date_or_time( $order ) {
		$meta = array(
			'date'      => false,
			'time'      => false,
			'timestamp' => false,
		);

		$has_meta  = false;
		$date      = get_post_meta( Iconic_WDS_Order::get_id( $order ), $this->date_meta_key, true );
		$time      = get_post_meta( Iconic_WDS_Order::get_id( $order ), $this->timeslot_meta_key, true );
		$timestamp = get_post_meta( Iconic_WDS_Order::get_id( $order ), $this->timestamp_meta_key, true );

		if ( ! empty( $date ) ) {
			$meta['date'] = $date;
			$has_meta     = true;
		}

		if ( ! empty( $time ) ) {
			$meta['time'] = $time;
			$has_meta     = true;
		}

		if ( ! empty( $timestamp ) ) {
			$meta['timestamp'] = $timestamp;
		}

		if ( ! $has_meta ) {
			return false;
		}

		return $meta;
	}

	/**
	 * Helper: Get formatted holidays
	 *
	 * @return array
	 */
	public function get_formatted_holidays() {
		$chosen_shipping_method = self::get_chosen_shipping_method();

		if ( isset( $this->holidays_formatted[ $chosen_shipping_method ] ) ) {
			return $this->holidays_formatted[ $chosen_shipping_method ];
		}

		$holidays           = $this->settings['holidays_holidays_holidays'];
		$holidays_formatted = array();

		if ( ! empty( $holidays ) ) {
			$utc_timezone = new DateTimeZone( 'UTC' );

			foreach ( $holidays as $holiday ) {
				if ( empty( $holiday['date'] ) ) {
					continue;
				}

				if ( ! empty( $holiday['shipping_methods'] ) && ! in_array( 'any', $holiday['shipping_methods'], true ) && ! in_array( self::get_chosen_shipping_method(), $holiday['shipping_methods'], true ) ) {
					continue;
				}

				$range        = false;
				$format       = ! empty( $holiday['repeat_yearly'] ) ? 'md' : 'Ymd';
				$holiday_from = isset( $holiday['alt_date'] ) ? $holiday['alt_date'] : $holiday['date'];
				$holiday_to   = isset( $holiday['alt_date_to'] ) ? $holiday['alt_date_to'] : $holiday['date_to'];

				$holiday_from_object = DateTime::createFromFormat( 'd/m/Y H:i:s', $holiday_from . ' 00:00:00', $utc_timezone );

				if ( ! empty( $holiday_to ) ) {
					$holiday_to_object = DateTime::createFromFormat( 'd/m/Y H:i:s', $holiday_to . ' 00:00:00', $utc_timezone );

					$range = Iconic_WDS_Date_Helpers::create_timestamp_range( $holiday_from_object->getTimestamp(), $holiday_to_object->getTimestamp() );
				}

				if ( $range && ! empty( $range ) ) {
					foreach ( $range as $timestamp ) {
						$holidays_formatted[] = date_i18n( $format, $timestamp );
					}
				} else {
					$holidays_formatted[] = date_i18n( $format, $holiday_from_object->getTimestamp() );
				}
			}
		}

		$this->holidays_formatted[ $chosen_shipping_method ] = $holidays_formatted;

		return $holidays_formatted;
	}

	/**
	 * Helper: Get upcoming bookable dates
	 *
	 * @param string $format       Format of results.
	 * @param bool   $ignore_slots Ignore whether there are slots available.
	 *
	 * @return array
	 */
	public function get_upcoming_bookable_dates( $format = 'array', $ignore_slots = false ) {
		$min             = Iconic_WDS_Date_Helpers::get_minmax_delivery_date( 'min' );
		$max             = Iconic_WDS_Date_Helpers::get_minmax_delivery_date( 'max' );
		$date_range      = Iconic_WDS_Date_Helpers::create_timestamp_range( $min['timestamp'], $max['timestamp'] );
		$specific_dates  = Iconic_WDS_Settings::get_specific_delivery_dates();
		$available_dates = array();
		$allow_same_day  = Iconic_WDS_Date_Helpers::is_same_day_allowed();
		$allow_next_day  = Iconic_WDS_Date_Helpers::is_next_day_allowed();

		// By default, user profile's locale is set during AJAX.
		// Switch locale to site's default.
		if ( wp_doing_ajax() ) {
			switch_to_locale( get_locale() );
		}

		// Add specific dates to our date range, if set.
		$date_range = array_merge( $date_range, wp_list_pluck( $specific_dates, 'timestamp' ) );

		sort( $date_range, SORT_NUMERIC );

		$date_range = array_unique( array_filter( $date_range ) );

		foreach ( $date_range as $timestamp ) {
			if ( Iconic_WDS_Date_Helpers::is_holiday( $timestamp ) ) {
				continue;
			}

			$date            = date_i18n( 'D, jS M', $timestamp );
			$ymd             = date_i18n( 'Ymd', $timestamp );
			$order_remaining = $this->get_orders_remaining_for_day( $ymd );

			if ( $allow_next_day === $date || $allow_same_day === $date ) {
				continue;
			}

			if ( $this->settings['timesettings_timesettings_setup_enable'] ) {
				$slots_available = $ignore_slots ? true : $this->slots_available_on_date( $ymd );

				if ( empty( $slots_available ) ) {
					continue;
				}
			} else {
				// If timeslot is disabled, still we need to check the the order remaining for the day.
				if ( empty( $order_remaining ) ) {
					continue;
				}
			}

			// Check if number of products in cart are not more than the max limit.
			if ( is_int( $order_remaining ) && 'products' === $this->settings['general_setup_max_order_calculation_method'] ) {
				global $woocommerce;
				if ( $order_remaining < $woocommerce->cart->cart_contents_count ) {
					continue;
				}
			}

			if ( 'array' === $format ) {
				$available_dates[] = array(
					'formatted'        => $date,
					'header_formatted' => date_i18n( $this->settings['reservations_reservations_dateformat'], $timestamp ),
					'admin_formatted'  => date_i18n( Iconic_WDS_Date_Helpers::date_format(), $timestamp ),
					'timestamp'        => $timestamp,
					'ymd'              => $ymd,
					'weekday_number'   => date_i18n( 'w', $timestamp ),
					'same_day'         => Iconic_WDS_Date_Helpers::get_same_day_date( 'Ymd' ) === $ymd,
					'next_day'         => Iconic_WDS_Date_Helpers::get_next_day_date( 'Ymd' ) === $ymd,
				);
			} else {
				$available_dates[] = date_i18n( $format, $timestamp );
			}
		}

		return apply_filters( 'iconic_wds_available_dates', $available_dates, $format, $ignore_slots );
	}

	/**
	 * Helper: Get timeslot data
	 *
	 * @param int $timeslot_id If an Id is passed, get a single timeslot, else get all.
	 *
	 * @return array|bool Returns timeslots with some additional data, like formatted times and values
	 */
	public function get_timeslot_data( $timeslot_id = false ) {
		if ( ! $this->settings['timesettings_timesettings_setup_enable'] ) {
			return false;
		}

		$timeslot_data = get_transient( $this->timeslot_data_transient_name );

		if ( false === $timeslot_data ) {
			$timeslot_data = array();

			if ( $this->settings['timesettings_timesettings_asap_enable'] ) {
				$timeslot_data['asap'] = $this->get_asap_slot_data();
			}

			$timeslots           = $this->settings['timesettings_timesettings_timeslots'];
			$formatted_timeslots = array();

			if ( ! empty( $timeslots ) ) {
				foreach ( $timeslots as $slot_id => $timeslot ) {
					$slot_id = empty( $timeslot['row_id'] ) ? $slot_id : $timeslot['row_id'];

					if ( empty( $timeslot['frequency'] ) ) {
						$formatted_timeslots[ $slot_id ] = $timeslot;
						continue;
					}

					$looping         = true;
					$i               = 0;
					$start_timestamp = strtotime( '1970-01-01 ' . $timeslot['timefrom'] . ':00' );
					$end_timestamp   = strtotime( '1970-01-01 ' . $timeslot['timeto'] . ':00' );
					$frequency       = floatval( $timeslot['frequency'] );
					$duration        = '' !== $timeslot['duration'] ? floatval( $timeslot['duration'] ) : $frequency;

					while ( $looping ) {
						$difference_in_minutes = ( $end_timestamp - $start_timestamp ) / 60;

						// Exit if the start time is after the end time.
						if ( $difference_in_minutes < $frequency || $start_timestamp >= $end_timestamp ) {
							$looping = false;
							break;
						}

						$dynamic_slot_id                                     = $slot_id . '/' . $i;
						$timeto_timestamp                                    = $start_timestamp + ( 60 * $duration );
						$formatted_timeslots[ $dynamic_slot_id ]             = $timeslot;
						$formatted_timeslots[ $dynamic_slot_id ]['timefrom'] = gmdate( 'H:i', $start_timestamp );
						$formatted_timeslots[ $dynamic_slot_id ]['timeto']   = gmdate( 'H:i', $timeto_timestamp );
						$start_timestamp                                     = $start_timestamp + ( 60 * $frequency );

						$i ++;
					}
				}

				foreach ( $formatted_timeslots as $slot_id => $timeslot ) {
					$timeslot_data[ $slot_id ] = $timeslot;

					$start_time_formatted = $this->format_time( $timeslot['timefrom'], 'H:i' );
					$end_time_formatted   = $this->format_time( $timeslot['timeto'], 'H:i' );

					$timeslot_data[ $slot_id ]['id']                 = $slot_id;
					$timeslot_data[ $slot_id ]['timefrom']           = array(
						'time'     => $timeslot_data[ $slot_id ]['timefrom'],
						'stripped' => str_replace( ':', '', $timeslot['timefrom'] ),
					);
					$timeslot_data[ $slot_id ]['timeto']             = array(
						'time'     => $timeslot_data[ $slot_id ]['timeto'],
						'stripped' => str_replace( ':', '', $timeslot['timeto'] ),
					);
					$timeslot_data[ $slot_id ]['time_id']            = $timeslot_data[ $slot_id ]['timefrom']['stripped'] . $timeslot_data[ $slot_id ]['timeto']['stripped'];
					$timeslot_data[ $slot_id ]['fee']                = array(
						'value'     => $timeslot['fee'],
						'formatted' => wc_price( $timeslot['fee'] ),
					);
					$timeslot_data[ $slot_id ]['formatted']          = $start_time_formatted === $end_time_formatted ? $start_time_formatted : sprintf( '%s - %s', $start_time_formatted, $end_time_formatted );
					$timeslot_data[ $slot_id ]['formatted_with_fee'] = $timeslot_data[ $slot_id ]['fee']['value'] > 0 ? sprintf( '%s (+%s)', $timeslot_data[ $slot_id ]['formatted'], wp_strip_all_tags( $timeslot_data[ $slot_id ]['fee']['formatted'] ) ) : $timeslot_data[ $slot_id ]['formatted'];
					$timeslot_data[ $slot_id ]['value']              = $this->get_timeslot_value( $timeslot_data[ $slot_id ] );
				}
			}

			// Sort timeslots array based on the start time.
			uasort( $timeslot_data, array( __CLASS__, 'sort_timeslot_by_from_value' ) );

			set_transient( $this->timeslot_data_transient_name, $timeslot_data, 24 * HOUR_IN_SECONDS );
		}

		// If a specific timeslot IS being grabbed,
		// add dynamic data for that slot only.

		if ( false !== $timeslot_id ) {
			if ( isset( $timeslot_data[ $timeslot_id ] ) ) {
				return apply_filters( 'iconic_wds_timeslot', $timeslot_data[ $timeslot_id ] );
			} else {
				return false;
			}
		}

		// Otherwise, return all timeslots.

		return apply_filters( 'iconic_wds_timeslots', $timeslot_data );
	}

	/**
	 * Helper: Get reservation table data
	 *
	 * Gets an array to use for outputting the reservation table
	 *
	 * @return array|bool array("headers" => array(), "body" => array())
	 */
	public function get_reservation_table_data() {
		$show_all_dates = (bool) $this->settings['reservations_reservations_hide_unavailable_dates'];
		$bookable_dates = $this->get_upcoming_bookable_dates( 'array', ! $show_all_dates );

		if ( empty( $bookable_dates ) ) {
			return false;
		}

		$table_data            = array();
		$table_data['headers'] = array();
		$table_data['body']    = array();
		$timeslots             = $this->get_timeslot_data();
		$column_count          = (int) $this->settings['reservations_reservations_columns'];
		$column_visible_class  = 'colVis';
		$reserved              = $this->get_reserved_slot();
		$available_slots       = array();

		// Headers.
		$i = 0;
		foreach ( $bookable_dates as $bookable_date ) {
			$available_slots[ $bookable_date['ymd'] ] = $this->get_slots_available_count( $timeslots, $bookable_date['ymd'] );

			$classes = array(
				sprintf( '%s-reservation-date', self::$slug ),
				$i < $column_count ? $column_visible_class : '',
			);

			$table_data['headers'][] = array(
				'cell'    => $bookable_date['header_formatted'],
				'classes' => $this->implode_classes( $classes ),
			);

			$i ++;
		}

		// Body.

		if ( $timeslots && ! empty( $timeslots ) ) {
			foreach ( $timeslots as $timeslot ) {
				$row = $timeslot['time_id'];

				$classes = array(
					sprintf( '%s-reservation-action', self::$slug ),
					$i < $column_count ? $column_visible_class : '',
				);

				if ( ! isset( $table_data['body'][ $row ] ) ) {
					$table_data['body'][ $row ]   = array();
					$table_data['body'][ $row ][] = array(
						'cell_type'  => 'th',
						'cell'       => $timeslot['formatted'],
						'attributes' => '',
						'classes'    => $this->implode_classes( $classes ),
					);
				}

				$i = 0;
				foreach ( $bookable_dates as $bookable_date ) {
					if ( isset( $table_data['body'][ $row ][ $bookable_date['ymd'] ] ) && $table_data['body'][ $row ][ $bookable_date['ymd'] ]['active'] ) {
						$i ++;
						continue;
					}

					$fee = (float) $timeslot['fee']['value'];

					if ( $bookable_date['same_day'] ) {
						$fee += floatval( $this->settings['datesettings_fees_same_day'] );
					}

					if ( $bookable_date['next_day'] ) {
						$fee += floatval( $this->settings['datesettings_fees_next_day'] );
					}

					if ( ! empty( $this->settings['datesettings_fees_days'][ $bookable_date['weekday_number'] ] ) ) {
						$fee += floatval( $this->settings['datesettings_fees_days'][ $bookable_date['weekday_number'] ] );
					}

					$slot_id                  = sprintf( '%s_%s', $bookable_date['ymd'], $timeslot['id'] );
					$slots_available          = isset( $available_slots[ $bookable_date['ymd'] ][ $timeslot['id'] ] ) ? $available_slots[ $bookable_date['ymd'] ][ $timeslot['id'] ] : 0;
					$timeslot_allowed_on_date = $this->is_timeslot_available_on_day( $bookable_date['timestamp'], $timeslot );
					$in_past                  = $this->is_timeslot_in_past( $timeslot, $bookable_date['ymd'] );
					$classes                  = array(
						sprintf( '%s-reservation-action', self::$slug ),
						$i < $column_count ? $column_visible_class : '',
						$reserved['id'] === $slot_id ? 'jckwds-reserved' : '',
					);
					$attributes               = array(
						'data-timeslot-id'         => esc_html( $slot_id ),
						'data-timeslot-date'       => esc_html( $bookable_date['ymd'] ),
						'data-timeslot-start-time' => esc_html( $timeslot['timefrom']['stripped'] ),
						'data-timeslot-end-time'   => esc_html( $timeslot['timeto']['stripped'] ),
					);

					if ( $slots_available <= 0 || ! $timeslot_allowed_on_date || $in_past ) {
						$cell_data = '<i class="jckwds-icn-lock"></i>';
						$classes[] = 'jckwds_full';

						$active = false;
					} else {
						$cell_data = '<a href="javascript: void(0);" class="jckwds-reserve-slot">%s</a>';

						if ( 'fee' === $this->settings['reservations_reservations_selection_type'] ) {
							$cell_data = sprintf( $cell_data, wc_price( $fee ) );
						} else {
							$cell_data = sprintf( $cell_data, '<i class="jckwds-icn-unchecked"></i><i class="jckwds-icn-checked"></i>' );
						}

						$active = true;
					}

					$table_data['body'][ $row ][ $bookable_date['ymd'] ] = array(
						'cell_type'  => 'td',
						'cell'       => $cell_data, // Show price or button or padlock, depending on settings.
						'attributes' => $attributes,
						'classes'    => $this->implode_classes( $classes ),
						'active'     => $active,
					);

					$i ++;
				}
			}
		}

		return $table_data;
	}

	/**
	 * The callback function to be used by usort.
	 *
	 * @param array $a Single timeslot.
	 * @param array $b Single timeslot.
	 *
	 * @return int difference.
	 */
	public static function sort_timeslot_by_from_value( $a, $b ) {
		return strcmp( $a['timefrom']['stripped'], $b['timefrom']['stripped'] );
	}

	/**
	 * Helper: Implode classes
	 *
	 * @param array $classes Classes.
	 *
	 * @return string
	 */
	public function implode_classes( $classes ) {
		if ( empty( $classes ) ) {
			return '';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Helper: Is timeslot in past?
	 *
	 * Checks whether the satrt time of the timeslot has already passed for the current day
	 *
	 * @param array  $timeslot Timeslot.
	 * @param string $date     Ymd.
	 *
	 * @return bool
	 */
	public function is_timeslot_in_past( $timeslot, $date = false ) {
		$date = $date ? $date : $this->current_ymd;

		$cutoff = $this->get_cutoff( $timeslot );

		if ( 'asap' === $timeslot['id'] ) {
			$asap_cutoff    = ! empty( $this->settings['timesettings_timesettings_asap_cutoff'] ) ? $this->settings['timesettings_timesettings_asap_cutoff'] : '23:59';
			$timeslot_ymdgi = $date . str_replace( ':', '', $asap_cutoff );
		} else {
			$timeslot_ymdgi = $date . $timeslot['timefrom']['stripped'];
		}

		// Deduct cutoff from timeslot date/time.
		$timeslot_date_time = DateTime::createFromFormat( 'YmdGi', $timeslot_ymdgi, wp_timezone() );
		$timeslot_timestamp = $timeslot_date_time->getTimestamp() - ( $cutoff * 60 );

		$in_past = time() >= $timeslot_timestamp;

		return apply_filters( 'iconic_wds_is_timeslot_in_past', $in_past, $timeslot, $date );
	}

	/**
	 * Get cutoff.
	 *
	 * @param bool|array $timeslot Timeslot.
	 *
	 * @return string
	 */
	public function get_cutoff( $timeslot = false ) {
		$cutoff = ! empty( $timeslot['cutoff'] ) ? $timeslot['cutoff'] : $this->settings['timesettings_timesettings_cutoff'];

		return apply_filters( 'iconic_wds_get_cutoff', $cutoff, $timeslot, $this );
	}

	/**
	 * Check if a timeslot is allowed on a specific day of the week.
	 * Timestamp is converted to current timezone.
	 *
	 * @param int $timestamp GMT Timestamp.
	 * @param array  $timeslot  Timeslot.
	 *
	 * @return bool
	 */
	public function is_timeslot_available_on_day( $timestamp, $timeslot ) {
		$allowed = false;

		// Convert timestamp to current timezone.
		$timestamp = $timestamp + Iconic_WDS_Date_Helpers::get_timezone_offset( $timestamp );

		$ymd           = date_i18n( 'Ymd', $timestamp );
		$specific_date = Iconic_WDS_Settings::is_specific_date( $ymd );

		// If this is a specific date, check if there's a timeslot for it.
		if ( $specific_date ) {
			$specific_date_row_id = Iconic_WDS_Settings::get_row_id( $specific_date );
			$allowed              = in_array( $specific_date_row_id, $timeslot['days'], true );
		} elseif ( isset( $timeslot['days'] ) && is_array( $timeslot['days'] ) ) {
			$timeslot['days'] = array_map( 'strval', $timeslot['days'] );
			$day_number       = (int) date_i18n( 'w', $timestamp );
			$allowed          = in_array( strval( $day_number ), $timeslot['days'], true );
		}

		return apply_filters( 'iconic_wds_is_timeslot_available_on_day', $allowed, $timestamp, $timeslot );
	}

	/**
	 * Frontend: Generate the reservation table
	 */
	public function generate_reservation_table() {
		$return                 = '';
		$reservation_table_data = $this->get_reservation_table_data();

		ob_start();
		include 'templates/reservation-table.php';
		$return .= ob_get_clean();

		return $return;
	}

	/**
	 * Frontend: Reservation Table Shortcode
	 *
	 * @param array $atts Shortcode Attributes.
	 *
	 * @return string
	 */
	public function reservation_table_shortcode( $atts ) {
		return $this->generate_reservation_table();
	}

	/**
	 * Frontend scripts.
	 */
	public function frontend_scripts() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$this->load_file( self::$slug . '-script', '/assets/frontend/js/main' . $min . '.js', true, array( 'jquery-ui-datepicker', 'accounting' ), true );
		$this->load_file( self::$slug . '-style', '/assets/frontend/css/main' . $min . '.css' );

		$script_vars = array(
			'settings'   => $this->settings,
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( self::$slug ),
			'currency'   => array(
				'precision' => 2,
				'symbol'    => get_woocommerce_currency_symbol(),
				'decimal'   => esc_attr( wc_get_price_decimal_separator() ),
				'thousand'  => esc_attr( wc_get_price_thousand_separator() ),
				'format'    => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
			),
			'dates'      => array(
				'same_day' => Iconic_WDS_Date_Helpers::get_same_day_date( Iconic_WDS_Date_Helpers::date_format() ),
				'next_day' => Iconic_WDS_Date_Helpers::get_next_day_date( Iconic_WDS_Date_Helpers::date_format() ),
			),
			'strings'    => array(
				'selectslot'   => Iconic_WDS_Helpers::get_label( 'select_time_slot' ),
				'selectdate'   => Iconic_WDS_Helpers::get_label( 'select_date_first' ),
				'noslots'      => Iconic_WDS_Helpers::get_label( 'no_time_slots' ),
				'loading'      => apply_filters( 'iconic_wds_loading_text', __( 'Loading...', 'jckwds' ) ),
				'available'    => __( 'Available', 'jckwds' ),
				'unavailable'  => __( 'Unavailable', 'jckwds' ),
				'days'         => array(
					__( 'Sunday', 'jckwds' ),
					__( 'Monday', 'jckwds' ),
					__( 'Tuesday', 'jckwds' ),
					__( 'Wednesday', 'jckwds' ),
					__( 'Thursday', 'jckwds' ),
					__( 'Friday', 'jckwds' ),
					__( 'Saturday', 'jckwds' ),
				),
				'days_short'   => array(
					__( 'Su', 'jckwds' ),
					__( 'Mo', 'jckwds' ),
					__( 'Tu', 'jckwds' ),
					__( 'We', 'jckwds' ),
					__( 'Th', 'jckwds' ),
					__( 'Fr', 'jckwds' ),
					__( 'Sa', 'jckwds' ),
				),
				'months'       => array(
					__( 'January', 'jckwds' ),
					__( 'February', 'jckwds' ),
					__( 'March', 'jckwds' ),
					__( 'April', 'jckwds' ),
					__( 'May', 'jckwds' ),
					__( 'June', 'jckwds' ),
					__( 'July', 'jckwds' ),
					__( 'August', 'jckwds' ),
					__( 'September', 'jckwds' ),
					__( 'October', 'jckwds' ),
					__( 'November', 'jckwds' ),
					__( 'December', 'jckwds' ),
				),
				'months_short' => array(
					__( 'Jan', 'jckwds' ),
					__( 'Feb', 'jckwds' ),
					__( 'Mar', 'jckwds' ),
					__( 'Apr', 'jckwds' ),
					__( 'May', 'jckwds' ),
					__( 'Jun', 'jckwds' ),
					__( 'Jul', 'jckwds' ),
					__( 'Aug', 'jckwds' ),
					__( 'Sep', 'jckwds' ),
					__( 'Oct', 'jckwds' ),
					__( 'Nov', 'jckwds' ),
					__( 'Dec', 'jckwds' ),
				),
			),
		);

		if ( is_checkout() ) {
			$script_vars['bookable_dates'] = $this->get_upcoming_bookable_dates( Iconic_WDS_Date_Helpers::date_format() );
			$script_vars['reserved_slot']  = $this->get_reserved_slot();
			$script_vars['needs_shipping'] = Iconic_WDS_Helpers::needs_shipping();
			$script_vars['day_fees']       = Iconic_WDS_Settings::get_day_fees();
		}

		wp_localize_script( self::$slug . '-script', self::$slug . '_vars', $script_vars );
	}

	/**
	 * Admin scripts.
	 */
	public function admin_scripts() {
		$screen = get_current_screen();

		if ( empty( $screen->id ) || ! in_array( $screen->id, array( 'shop_order', 'woocommerce_page_jckwds-settings' ), true ) ) {
			return;
		}

		$this->load_file( 'iconic-wds-script', '/assets/admin/js/main.min.js', true, array( 'jquery-ui-datepicker' ), true );

		if ( 'shop_order' !== $screen->id ) {
			return;
		}

		$script_vars = array(
			'bookable_dates' => $this->get_upcoming_bookable_dates( Iconic_WDS_Date_Helpers::date_format() ),
			'settings'       => $this->settings,
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'     => wp_create_nonce( self::$slug ),
			'order_id'       => (int) filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT ),
			'strings'        => array(
				'selectslot'   => Iconic_WDS_Helpers::get_label( 'select_time_slot' ),
				'noslots'      => Iconic_WDS_Helpers::get_label( 'no_time_slots' ),
				'loading'      => apply_filters( 'iconic_wds_loading_text', __( 'Loading...', 'jckwds' ) ),
				'days'         => array(
					__( 'Sunday', 'jckwds' ),
					__( 'Monday', 'jckwds' ),
					__( 'Tuesday', 'jckwds' ),
					__( 'Wednesday', 'jckwds' ),
					__( 'Thursday', 'jckwds' ),
					__( 'Friday', 'jckwds' ),
					__( 'Saturday', 'jckwds' ),
				),
				'days_short'   => array(
					__( 'Su', 'jckwds' ),
					__( 'Mo', 'jckwds' ),
					__( 'Tu', 'jckwds' ),
					__( 'We', 'jckwds' ),
					__( 'Th', 'jckwds' ),
					__( 'Fr', 'jckwds' ),
					__( 'Sa', 'jckwds' ),
				),
				'months'       => array(
					__( 'January', 'jckwds' ),
					__( 'February', 'jckwds' ),
					__( 'March', 'jckwds' ),
					__( 'April', 'jckwds' ),
					__( 'May', 'jckwds' ),
					__( 'June', 'jckwds' ),
					__( 'July', 'jckwds' ),
					__( 'August', 'jckwds' ),
					__( 'September', 'jckwds' ),
					__( 'October', 'jckwds' ),
					__( 'November', 'jckwds' ),
					__( 'December', 'jckwds' ),
				),
				'months_short' => array(
					__( 'Jan', 'jckwds' ),
					__( 'Feb', 'jckwds' ),
					__( 'Mar', 'jckwds' ),
					__( 'Apr', 'jckwds' ),
					__( 'May', 'jckwds' ),
					__( 'Jun', 'jckwds' ),
					__( 'Jul', 'jckwds' ),
					__( 'Aug', 'jckwds' ),
					__( 'Sep', 'jckwds' ),
					__( 'Oct', 'jckwds' ),
					__( 'Nov', 'jckwds' ),
					__( 'Dec', 'jckwds' ),
				),
			),
		);

		wp_localize_script( 'iconic-wds-script', 'iconic_wds_vars', $script_vars );
	}

	/**
	 * Frontend: Add dynamic styles to head tag
	 */
	public function dynamic_css() {
		include_once ICONIC_WDS_PATH . 'assets/frontend/css/user.css.php';
	}

	/**
	 * Helper: Add reservation to database
	 *
	 * @param array $data Data.
	 *
	 * @return bool
	 */
	public function add_reservation( $data ) {
		global $wpdb;

		$insert = false;

		$defaults = array(
			'datetimeid' => false,
			'processed'  => 0,
			'date'       => false,
			'starttime'  => '',
			'endtime'    => '',
			'order_id'   => '',
			'asap'       => false,
		);

		$data = wp_parse_args( $data, $defaults );

		if ( $data['date'] ) {
			$expire = ( $data['processed'] ) ? null : strtotime( '+' . $this->settings['reservations_reservations_expires'] . ' minutes', time() );

			$this->remove_existing_reservation( $data['order_id'] );

			$insert = $wpdb->insert(
				$this->reservations_db_table_name,
				array(
					'datetimeid' => $data['datetimeid'],
					'processed'  => $data['processed'],
					'user_id'    => $this->user_id,
					'expires'    => $expire,
					'date'       => $data['date'],
					'starttime'  => $data['starttime'],
					'endtime'    => $data['endtime'],
					'order_id'   => $data['order_id'],
					'asap'       => $data['asap'],
				),
				array(
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
				)
			);
		}

		return $insert;
	}

	/**
	 * Remove existing reservation for order ID.
	 *
	 * @param int|bool $order_id Order ID.
	 */
	public function remove_existing_reservation( $order_id = false ) {
		global $wpdb;

		$has_reservation = $this->has_reservation();

		if ( ! $has_reservation && empty( $order_id ) ) {
			return;
		}

		$reservation_id = $has_reservation ? $has_reservation->id : false;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}jckwds WHERE order_id = %d OR id = %d",
				$order_id,
				$reservation_id
			)
		);
	}

	/**
	 * Helper: Set User ID
	 *
	 * If cookie is set, use that, otherwise use logged in user id,
	 * otherwise set cookie and use it.
	 */
	public function set_user_id() {
		// If the cookie is set.
		if ( isset( $_COOKIE[ $this->guest_user_id_cookie_name ] ) ) {
			// Set the cookie as the user id.
			$this->user_id = sanitize_key( $_COOKIE[ $this->guest_user_id_cookie_name ] );

			// If the user already has a reservation, we'll leave it there
			// this means if a user sets a reservation, then logs in
			// their reservation will be maintained.

			if ( $this->has_reservation() ) {
				return;
			}
		}

		// If they didn't have a reservation, we'll proceed here.
		if ( is_user_logged_in() ) {
			$this->user_id = get_current_user_id();
		} else {
			if ( isset( $_COOKIE[ $this->guest_user_id_cookie_name ] ) ) {
				$this->user_id = sanitize_key( $_COOKIE[ $this->guest_user_id_cookie_name ] );
			} else {
				if ( headers_sent() ) {
					return;
				}

				$this->user_id = uniqid( self::$slug );
				setcookie( $this->guest_user_id_cookie_name, $this->user_id, 0, '/', COOKIE_DOMAIN );
			}
		}
	}

	/**
	 * Helper: Update a reserved slot.
	 *
	 * @param string $slot_id  Slot id e.g: Ymd_0.
	 * @param int    $order_id Order ID.
	 *
	 * @return array.
	 */
	public function update_reservation( $slot_id, $order_id ) {
		global $wpdb;

		if ( ! $order_id ) {
			return;
		}

		$order_reservation          = Iconic_WDS_Reservations::get_reservation_for_order( $order_id );
		$reservation_already_exists = ! is_null( $order_reservation );

		// If the reservation already exists for this order, update it.
		$where_order_id = $reservation_already_exists ? $order_id : 0;
		$slot           = $this->get_slot_data_from_id( $slot_id );

		$update = $wpdb->update(
			$this->reservations_db_table_name,
			array(
				'processed'  => 1,
				'order_id'   => $order_id,
				'datetimeid' => $slot_id,
				'date'       => $slot['date']['database'],
				'starttime'  => $slot['time']['timefrom']['stripped'],
				'endtime'    => $slot['time']['timeto']['stripped'],
				'expires'    => null,
			),
			array(
				'user_id'  => $this->user_id,
				'order_id' => $where_order_id,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			),
			array(
				'%s',
				'%d',
			)
		);

		return $update;
	}

	/**
	 * Helper: Check if current user has a reservation
	 *
	 * @return [arr/bool]
	 */
	public function has_reservation() {
		global $wpdb;

		$reservation = $wpdb->get_row(
			$wpdb->prepare(
				"
                SELECT *
                FROM {$wpdb->prefix}jckwds
                WHERE processed = 0
                AND user_id = %s
                ",
				$this->user_id
			)
		);

		return ( $reservation ) ? $reservation : false;
	}

	/**
	 * Helper: Register and enqueue scripts and styles
	 *
	 * @param string $name      Script/style Name.
	 * @param string $file_path File path.
	 * @param bool   $is_script Is script or style.
	 * @param array  $deps      Dependencies.
	 * @param bool   $in_footer In footer.
	 */
	private function load_file( $name, $file_path, $is_script = false, $deps = array( 'jquery' ), $in_footer = true ) {
		$url  = plugins_url( $file_path, __FILE__ );
		$file = plugin_dir_path( __FILE__ ) . $file_path;

		if ( file_exists( $file ) ) {
			if ( $is_script ) {
				wp_register_script( $name, $url, $deps, self::$version, $in_footer ); // Depends on jQuery.
				wp_enqueue_script( $name );
			} else {
				wp_register_style( $name, $url, array(), self::$version );
				wp_enqueue_style( $name );
			}
		}
	}

	/**
	 * Get the number of orders booked/reserved on all upcoming dates.
	 *
	 * @return array|object|null
	 */
	private function get_future_orders_by_date( $max_order_calculation_method = false ) {
		static $booked_dates = null;

		if ( null !== $booked_dates ) {
			return $booked_dates;
		}

		if ( false === $max_order_calculation_method ) {
			$max_order_calculation_method = $this->settings['general_setup_max_order_calculation_method'];
		}

		global $wpdb;

		$booked_dates = array();

		/*
		If max order calculation method is 'orders' then we count orders per day,
		else if method is 'products' then we count number of products in orders, per day.
		*/
		if ( 'orders' === $max_order_calculation_method ) {
			$booked_dates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_FORMAT( date, '%%Y%%m%%d' ) as ymd, COUNT( date ) as count
					FROM {$wpdb->prefix}jckwds
					WHERE NOT ( user_id = %s AND processed = 0 )
					AND date >= %s
					GROUP BY ymd
					ORDER BY ymd",
					$this->user_id,
					current_time( 'Y-m-d 00:00:00' )
				),
				ARRAY_A
			);
		} else {
			$booked_dates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						DATE_FORMAT( wds.date, '%%Y%%m%%d' ) as ymd, sum( stats.num_items_sold ) as count
					FROM
						{$wpdb->prefix}jckwds wds, {$wpdb->prefix}wc_order_stats stats
					WHERE 
						wds.order_id = stats.order_id
						AND NOT ( wds.user_id = %s AND wds.processed = 0 )
						AND date >= %s
					GROUP BY ymd
					ORDER BY ymd",
					$this->user_id,
					current_time( 'Y-m-d 00:00:00' )
				),
				ARRAY_A
			);
		}

		if ( empty( $booked_dates ) || is_wp_error( $booked_dates ) ) {
			$booked_dates = false;
		}

		return $booked_dates;
	}

	/**
	 * Get number of orders remaining for a specific day.
	 *
	 * @param string $ymd Ymd string of date.
	 *
	 * @return bool|int True if unlimited orders remaining. False if none. Otherwise, actual number of orders remaining.
	 */
	public function get_orders_remaining_for_day( $ymd ) {
		$specific_date = Iconic_WDS_Settings::is_specific_date( $ymd );

		if ( ! $specific_date ) {
			$day_of_the_week   = absint( date_i18n( 'w', strtotime( $ymd ) ) );
			$max_orders_on_day = Iconic_WDS_Settings::get_delivery_days_max_orders( $day_of_the_week );
		} else {
			$max_orders_on_day = '' === $specific_date['lockout'] ? true : absint( $specific_date['lockout'] );
		}

		// If max orders is true (any # of orders allowed), or false (no orders allowed).
		if ( is_bool( $max_orders_on_day ) ) {
			return apply_filters( 'iconic_wds_get_orders_remaining_for_day', $max_orders_on_day, $ymd, null );
		}

		$future_orders_by_date = $this->get_future_orders_by_date();
		$booked_orders_on_day  = Iconic_WDS_Helpers::search_array_by_key_value( 'ymd', $ymd, $future_orders_by_date );

		// If there aren't any bookings on this day.
		if ( empty( $booked_orders_on_day ) ) {
			return apply_filters( 'iconic_wds_get_orders_remaining_for_day', $max_orders_on_day, $ymd, $future_orders_by_date );
		}

		$max_orders_on_day -= absint( $booked_orders_on_day['count'] );

		$max_orders_on_day = $max_orders_on_day <= 0 ? false : $max_orders_on_day;

		return apply_filters( 'iconic_wds_get_orders_remaining_for_day', $max_orders_on_day, $ymd, $future_orders_by_date );
	}

	/**
	 * Helper: Get number of of slots available for a specific date/time
	 *
	 * @param array  $timeslots Array of timeslots.
	 * @param string $ymd       Ymd string of date.
	 * @param string $max_order_calculation_method Weather the max order calculation is based on number of 'orders' or 'products'.
	 *
	 * @return array
	 */
	public function get_slots_available_count( $timeslots, $ymd, $max_order_calculation_method = false ) {
		static $counts = array();

		if ( ! empty( $counts[ $ymd ] ) ) {
			return $counts[ $ymd ];
		}

		if ( false === $max_order_calculation_method ) {
			$max_order_calculation_method = $this->settings['general_setup_max_order_calculation_method'];
		}

		$orders_remaining_for_day = $this->get_orders_remaining_for_day( $ymd );

		if ( ! $orders_remaining_for_day ) {
			$counts[ $ymd ] = 0;

			return $counts[ $ymd ];
		}

		global $wpdb;

		$timeslot_ids   = wp_list_pluck( $timeslots, 'id' );
		$counts[ $ymd ] = array_fill_keys( $timeslot_ids, 0 );

		$reserved_slots = array();

		/*
		If max order calculation method is 'orders' then we count orders per slot,
		else if method is 'products' then we count number of products in orders, per slot.
		*/
		if ( 'orders' === $max_order_calculation_method ) {
			$reserved_slots = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT datetimeid, COUNT(datetimeid) as count
					FROM {$wpdb->prefix}jckwds
					WHERE datetimeid LIKE %s
					AND NOT (user_id = %s AND processed = 0)
					GROUP BY datetimeid",
					$ymd . '_%',
					$this->user_id
				),
				ARRAY_A
			);
		} else {
			$reserved_slots = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT 
						datetimeid, SUM(num_items_sold) as count
					FROM 
						{$wpdb->prefix}jckwds wds, {$wpdb->prefix}wc_order_stats stats  
					WHERE 
						wds.order_id = stats.order_id
						AND datetimeid LIKE %s
						AND NOT (user_id = %s AND processed = 0)
					GROUP BY datetimeid",
					$ymd . '_%',
					$this->user_id
				),
				ARRAY_A
			);
		}

		foreach ( $counts[ $ymd ] as $slot_id => $count ) {
			$timeslot = Iconic_WDS_Helpers::search_array_by_key_value( 'id', $slot_id, $timeslots );

			if ( ! $timeslot ) {
				continue;
			}

			$counts[ $ymd ][ $slot_id ] = '' === trim( $timeslot['lockout'] ) ? true : absint( $timeslot['lockout'] );
		}

		if ( empty( $reserved_slots ) || is_wp_error( $reserved_slots ) ) {
			$counts[ $ymd ] = apply_filters( 'iconic_wds_slots_available_count', $counts[ $ymd ], $ymd, $timeslots );

			return $counts[ $ymd ];
		}

		foreach ( $reserved_slots as $reserved_slot ) {
			$slot_id = str_replace( $ymd . '_', '', $reserved_slot['datetimeid'] );

			if ( ! isset( $counts[ $ymd ][ $slot_id ] ) || true === $counts[ $ymd ][ $slot_id ] ) {
				continue;
			}

			$counts[ $ymd ][ $slot_id ] -= absint( $reserved_slot['count'] );
		}

		$counts[ $ymd ] = apply_filters( 'iconic_wds_slots_available_count', $counts[ $ymd ], $ymd, $timeslots );

		return $counts[ $ymd ];
	}

	/**
	 * Helper: Get all slots available on a specific date
	 *
	 * @param string $ymd Ymd string of date.
	 *
	 * @return array
	 */
	public function slots_available_on_date( $ymd ) {
		$available_timeslots      = array();
		$orders_remaining_for_day = $this->get_orders_remaining_for_day( $ymd );

		if ( ! $orders_remaining_for_day ) {
			return apply_filters( 'iconic_wds_slots_available_on_date', $available_timeslots, $ymd );
		}

		$timeslots      = $this->get_timeslot_data();
		$datetime       = DateTime::createFromFormat( 'Ymd H:i:s', $ymd . ' 00:00:00', wp_timezone() );
		$date_timestamp = $datetime->getTimestamp();

		if ( ! $timeslots ) {
			return apply_filters( 'iconic_wds_slots_available_on_date', $available_timeslots, $ymd );
		}

		$slots_available_count = $this->get_slots_available_count( $timeslots, $ymd );

		foreach ( $timeslots as $timeslot ) {
			$slot_id = sprintf( '%s_%s', $ymd, $timeslot['id'] );

			$slot_allowed_on_day     = $this->is_timeslot_available_on_day( $date_timestamp, $timeslot );
			$in_past                 = $this->is_timeslot_in_past( $timeslot, $ymd );
			$slot_allowed_for_method = $this->is_timeslot_allowed_for_method( $timeslot );

			if ( ! $slot_allowed_on_day || $in_past || ! $slot_allowed_for_method ) {
				continue;
			}

			if ( $slots_available_count[ $timeslot['id'] ] <= 0 ) {
				continue;
			}

			global $woocommerce;

			if (
				'products' === $this->settings['general_setup_max_order_calculation_method']
				&& ! is_bool( $slots_available_count[ $timeslot['id'] ] )
				&& intval( $slots_available_count[ $timeslot['id'] ] ) < $woocommerce->cart->cart_contents_count
			) {
				continue;
			}

			$timeslot['slot_id']               = $slot_id;
			$timeslot['slots_available_count'] = $slots_available_count[ $timeslot['id'] ];
			$available_timeslots[]             = $timeslot;
		}

		return apply_filters( 'iconic_wds_slots_available_on_date', $available_timeslots, $ymd );
	}

	/**
	 * ASAP slot data.
	 *
	 * @return array
	 */
	public function get_asap_slot_data() {
		$fee           = ! empty( $this->settings['timesettings_timesettings_asap_fee'] ) ? $this->settings['timesettings_timesettings_asap_fee'] : '0.00';
		$lockout       = isset( $this->settings['timesettings_timesettings_asap_lockout'] ) ? $this->settings['timesettings_timesettings_asap_lockout'] : '';
		$formatted_fee = wc_price( $fee );
		$label         = apply_filters( 'iconic_wds_asap_label', __( 'ASAP', 'jckwds' ) );

		return apply_filters(
			'iconic_wds_asap_slot_data',
			array(
				'id'                 => 'asap',
				'value'              => 'asap|' . $fee,
				'time_id'            => '00000000',
				'fee'                => array(
					'value'     => $fee,
					'formatted' => $formatted_fee,
				),
				'lockout'            => $lockout,
				'formatted'          => $label,
				'formatted_with_fee' => $fee > 0 ? sprintf( '%s (+%s)', $label, wp_strip_all_tags( $formatted_fee ) ) : $label,
				'days'               => array( 0, 1, 2, 3, 4, 5, 6 ),
				'timefrom'           => array(
					'time'     => '00:00',
					'stripped' => '0000',
				),
				'timeto'             => array(
					'time'     => '00:00',
					'stripped' => '0000',
				),
				'asap'               => true,
				'shipping_methods'   => array( 'any' ),
			)
		);
	}

	/**
	 * Helper: Format time
	 *
	 * Give a time id, format it according to the admin settings
	 *
	 * @param string $time_id      "Hi" format e.g. "0100" or "1430".
	 * @param string $start_format "Hi" by default - PHP time format.
	 * @param string $end_format   Defined in the admin settings - probably something like "H:i".
	 *
	 * @return string End formatted time.
	 */
	public function format_time( $time_id, $start_format = 'Hi', $end_format = false ) {
		$end_format = ( $end_format ) ? $end_format : $this->settings['timesettings_timesettings_setup_timeformat'];

		if ( $end_format ) {
			if ( 'Hi' === $start_format ) {
				$time_id = str_pad( $time_id, 4, '0', STR_PAD_LEFT );
			}

			$time = DateTime::createFromFormat( $start_format, $time_id, wp_timezone() );

			return $time->format( $end_format );
		}

		return $time;
	}

	/**
	 * Helper: Get reserved slot data.
	 *
	 * @return bool|array
	 */
	public function get_reserved_slot() {
		global $wpdb;

		$this->remove_outdated_reservations();

		$slot_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT datetimeid FROM {$wpdb->prefix}jckwds WHERE user_id = %s AND processed = '0'", $this->user_id )
		);

		if ( null !== $slot_id ) {
			return $this->get_slot_data_from_id( $slot_id );
		} else {
			return false;
		}
	}

	/**
	 * Get slot data from ID.
	 *
	 * @param string $slot_id Slot ID e.g. Ymd_0.
	 *
	 * @return array
	 */
	public function get_slot_data_from_id( $slot_id ) {
		$slot_id_exploded = explode( '_', $slot_id );
		$date             = DateTime::createFromFormat( 'Ymd', $slot_id_exploded[0], wp_timezone() );

		$slot = array(
			'id'   => $slot_id,
			'date' => array(
				'database'  => Iconic_WDS_Date_Helpers::convert_date_for_database( $slot_id_exploded[0] ),
				'formatted' => $date->format( Iconic_WDS_Date_Helpers::date_format() ),
				'id'        => $date->format( 'Ymd' ),
			),
			'time' => isset( $slot_id_exploded[1] ) ? $this->get_timeslot_data( $slot_id_exploded[1] ) : false,
		);

		return apply_filters( 'iconic_wds_slot_data', $slot );
	}

	/**
	 * Helper: Remove outdated pending slots
	 */
	public function remove_outdated_reservations() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}jckwds WHERE expires <= %d AND processed = 0", time() ) );
	}

	/**
	 * When shipping is updated, delete shipping method transient.
	 *
	 * @param string $transient  Transient.
	 * @param mixed  $value      Value.
	 * @param int    $expiration Expiration time.
	 */
	public static function on_update_shipping( $transient, $value, $expiration ) {
		if ( 'shipping-transient-version' !== $transient ) {
			return;
		}

		delete_transient( 'iconic-wds-shipping-methods' );
	}

	/**
	 * Helper: get shipping method options
	 *
	 * Also checks whether zones exist, as per the latest WooCommerce (2.6.0)
	 *
	 * @return array
	 */
	public function get_shipping_method_options() {
		if ( ! empty( $this->shipping_methods ) ) {
			return apply_filters( 'iconic_wds_shipping_method_options', $this->shipping_methods );
		}

		$transient_name         = 'iconic-wds-shipping-methods';
		$this->shipping_methods = get_transient( $transient_name );

		if ( false !== $this->shipping_methods ) {
			return apply_filters( 'iconic_wds_shipping_method_options', $this->shipping_methods );
		}

		$shipping_method_options = array(
			'any' => __( 'Any Method', 'jckwds' ),
		);

		if ( class_exists( 'WC_Shipping_Zones' ) ) {
			$shipping_zones = $this->get_shipping_zones();

			if ( ! empty( $shipping_zones ) ) {
				foreach ( $shipping_zones as $shipping_zone ) {
					$methods = $shipping_zone->get_shipping_methods( true );

					if ( ! $methods ) {
						continue;
					}

					foreach ( $methods as $method ) {
						$zone_based_shipping_method = apply_filters( 'iconic_wds_zone_based_shipping_method', array(), $method, $shipping_zone );

						if ( ! empty( $zone_based_shipping_method ) ) {
							$shipping_method_options = $shipping_method_options + $zone_based_shipping_method;
							continue;
						}

						$title = empty( $method->title ) ? ucfirst( $method->id ) : $method->title;
						$class = str_replace( 'wc_shipping_', '', strtolower( get_class( $method ) ) );

						if ( 'table_rate' === $class ) {
							$trs_methods = $this->get_trs_methods_zones( $method, $class, $shipping_zone );

							$shipping_method_options = $shipping_method_options + $trs_methods;
						} elseif ( 'be_cart_based_shipping' === $class ) {
							$value = sprintf( 'cart_based_rate%d', $method->instance_id );

							$shipping_method_options[ $value ] = esc_html( sprintf( '%s: %s', $shipping_zone->get_zone_name(), $title ) );
						} else {
							$value = sprintf( '%s:%d', $class, $method->instance_id );

							$shipping_method_options[ $value ] = esc_html( sprintf( '%s: %s', $shipping_zone->get_zone_name(), $title ) );
						}
					}
				}
			}
		}

		$shipping_methods = WC()->shipping->load_shipping_methods();

		foreach ( $shipping_methods as $method ) {
			if ( ! $method->has_settings() ) {
				continue;
			}

			$standard_shipping_method = apply_filters( 'iconic_wds_standard_shipping_method', array(), $method );

			if ( ! empty( $standard_shipping_method ) ) {
				$shipping_method_options = $shipping_method_options + $standard_shipping_method;
				continue;
			}

			$title = empty( $method->method_title ) ? ucfirst( $method->id ) : $method->method_title;
			$class = get_class( $method );

			if ( 'Wafs_Free_Shipping_Method' === $class ) {
				$wafs_methods = $this->get_wafs_methods();

				$shipping_method_options = $shipping_method_options + $wafs_methods;
			} elseif ( 'BE_Table_Rate_Shipping' === $class ) {
				$trs_methods = $this->get_trs_methods();

				$shipping_method_options = $shipping_method_options + $trs_methods;
			} elseif ( 'WC_Shipping_WooShip' === $class ) {
				$wooship_methods = $this->get_wooship_methods();

				$shipping_method_options = $shipping_method_options + $wooship_methods;
			} elseif ( 'MH_Table_Rate_Plus_Shipping_Method' === $class ) {
				$table_rate_plus_methods = $this->get_table_rate_plus_methods( $method );

				$shipping_method_options = $shipping_method_options + $table_rate_plus_methods;
			} elseif ( 'WC_Distance_Rate_Shipping' === $class || 'WC_Collection_Delivery_Rate' === $class || 'WC_Special_Delivery_Rate' === $class ) {
				$distance_rate_shipping_methods = $this->get_distance_rate_shipping_methods( $method );

				$shipping_method_options = $shipping_method_options + $distance_rate_shipping_methods;
			} else {
				$shipping_method_options[ strtolower( $class ) ] = esc_html( $title );
			}
		}

		$this->shipping_methods = apply_filters( 'iconic_wds_shipping_method_options', $shipping_method_options );

		set_transient( $transient_name, $this->shipping_methods, 30 * DAY_IN_SECONDS );

		return $this->shipping_methods;
	}

	/**
	 * Helper: Get all shipping zones
	 *
	 * @return array
	 */
	public function get_shipping_zones() {
		$shipping_zones = WC_Shipping_Zones::get_zones();

		if ( $shipping_zones ) {
			foreach ( $shipping_zones as $index => $shipping_zone ) {
				$shipping_zones[ $index ] = new WC_Shipping_Zone( $shipping_zone['zone_id'] );
			}
		}

		$shipping_zones[] = new WC_Shipping_Zone( 0 );

		return $shipping_zones;
	}

	/**
	 * Helper: Get "WooCommerce Advanced Free Shipping" methods
	 *
	 * @return arr
	 */
	public function get_wafs_methods() {
		$methods_array = array();
		$methods       = wafs_get_rates();

		if ( empty( $methods ) ) {
			return array();
		}

		foreach ( $methods as $method ) {
			$key = sprintf( '%d_advanced_free_shipping', $method->ID );

			$methods_array[ $key ] = sprintf( 'Advanced Free Shipping: %s', ! empty( $method->post_title ) ? $method->post_title : $method->ID );
		}

		return $methods_array;
	}

	/**
	 * Helper: Get "WooCommerce Table Rate Shipping" methods
	 *
	 * @return arr
	 */
	public function get_trs_methods() {
		$methods_array = array();
		$table_rates   = array_filter( (array) get_option( 'woocommerce_table_rates' ) );

		if ( $table_rates && ! empty( $table_rates ) ) {
			foreach ( $table_rates as $table_rate ) {
				$methods_array[ sprintf( 'table_rate_shipping_%s', $table_rate['identifier'] ) ] = esc_html( $table_rate['title'] );
			}
		}

		return $methods_array;
	}

	/**
	 * Helper: Get "WooCommerce Table Rate Shipping" methods for Zone based shipping.
	 *
	 * @param Object $method        Shipping method.
	 * @param string $class         Name of the method's class.
	 * @param Object $shipping_zone Shipping zone.
	 *
	 * @retrun arr
	 * @since  1.7.1
	 */
	public function get_trs_methods_zones( $method, $class, $shipping_zone ) {
		$methods_array = array();
		$rates         = $method->get_shipping_rates();

		if ( ! $rates || empty( $rates ) ) {
			return $methods_array;
		}

		$title = ! empty( $method->title ) ? $method->title : ucfirst( $method->id );

		foreach ( $rates as $rate ) {
			$value = sprintf( '%s:%d', $class, $method->instance_id );

			if ( isset( $methods_array[ $value ] ) ) {
				continue;
			}

			$methods_array[ $value ] = esc_html( sprintf( '%s: %s', $shipping_zone->get_zone_name(), $title ) );
		}

		return $methods_array;
	}

	/**
	 * Helper: Get "WooShip" methods
	 *
	 * @return arr
	 */
	public function get_wooship_methods() {
		$methods_array = array();
		$wooship       = WooShip::get_instance();

		if ( $wooship && ( ! empty( $wooship->config['shipping_methods'] ) && is_array( $wooship->config['shipping_methods'] ) ) ) {
			foreach ( $wooship->config['shipping_methods'] as $method_key => $method ) {
				$methods_array[ sprintf( 'wooship_%d', $method_key ) ] = esc_html( $method['title'] );
			}
		}

		return $methods_array;
	}

	/**
	 * Helper: Get "Table Rate Plus" methods
	 *
	 * @param MH_Table_Rate_Plus_Shipping_Method $method Shipping Method.
	 *
	 * @return arr
	 */
	public function get_table_rate_plus_methods( $method ) {
		$methods_array = array();
		$zones         = $method->zones;
		$services      = $method->services;
		$rates         = $method->table_rates;

		if ( $rates && ! empty( $rates ) ) {
			foreach ( $rates as $rate ) {
				$zone    = isset( $zones[ $rate['zone'] - 1 ]['name'] ) ? $zones[ $rate['zone'] - 1 ]['name'] : __( 'Everywhere Else', 'jckwds' );
				$service = $services[ $rate['service'] - 1 ]['name'];

				$title = sprintf( '%s: %s', $zone, $service );

				$methods_array[ sprintf( 'mh_wc_table_rate_plus_%d', $rate['id'] ) ] = esc_html( $title );
			}
		}

		return $methods_array;
	}

	/**
	 * Helper: Get Distance rate Shipping" methods
	 *
	 * @param WC_Distance_Rate_Shipping $method Shipping method.
	 *
	 * @return array
	 */
	public function get_distance_rate_shipping_methods( $method ) {
		$methods_array = array();

		if ( empty( $method->distance_rate_shipping_rates ) ) {
			return $methods_array;
		}

		$i = 1;
		foreach ( $method->distance_rate_shipping_rates as $rate ) {
			$value = sprintf( '%s:%d', $method->id, $i );

			if ( isset( $methods_array[ $value ] ) ) {
				continue;
			}

			$title = ! empty( $rate['title'] ) ? $rate['title'] : sprintf( '%s %d', __( 'Rule', 'jckwds' ), $i );

			$methods_array[ $value ] = esc_html( sprintf( '%s: %s', $method->method_title, $title ) );

			$i ++;
		}

		return $methods_array;
	}

	/**
	 * Check fee
	 *
	 * When WooCommerce runs the update_order_review AJAX function,
	 * check if our slot has a fee applied to it, then add/remove it
	 *
	 * @param string $post_data Post data.
	 */
	public function check_fee( $post_data ) {
		parse_str( $post_data, $checkout_fields );

		$allowed = Iconic_WDS_Checkout::is_delivery_slots_allowed();

		if ( ! $allowed ) {
			WC()->session->__unset( 'jckwds_timeslot_fee' );
			WC()->session->__unset( 'jckwds_same_day_fee' );
			WC()->session->__unset( 'jckwds_next_day_fee' );
			WC()->session->__unset( 'jckwds_day_fee' );

			return;
		}

		if ( isset( $checkout_fields['jckwds-delivery-time'] ) ) {
			$timeslot_fee = $this->extract_fee_from_option_value( $checkout_fields['jckwds-delivery-time'] );

			if ( $timeslot_fee > 0 ) {
				WC()->session->set( 'jckwds_timeslot_fee', $timeslot_fee );
			} else {
				WC()->session->__unset( 'jckwds_timeslot_fee' );
			}
		} else {
			WC()->session->__unset( 'jckwds_timeslot_fee' );
		}

		if ( $this->settings['datesettings_fees_same_day'] > 0 ) {
			$same_day = Iconic_WDS_Date_Helpers::get_same_day_date( Iconic_WDS_Date_Helpers::date_format() );
			if ( isset( $checkout_fields['jckwds-delivery-date'] ) && $checkout_fields['jckwds-delivery-date'] === $same_day ) {
				WC()->session->set( 'jckwds_same_day_fee', $this->settings['datesettings_fees_same_day'] );
			} else {
				WC()->session->__unset( 'jckwds_same_day_fee' );
			}
		} else {
			WC()->session->__unset( 'jckwds_same_day_fee' );
		}

		if ( $this->settings['datesettings_fees_next_day'] > 0 ) {
			$next_day = Iconic_WDS_Date_Helpers::get_next_day_date( Iconic_WDS_Date_Helpers::date_format() );
			if ( isset( $checkout_fields['jckwds-delivery-date'] ) && $checkout_fields['jckwds-delivery-date'] === $next_day ) {
				WC()->session->set( 'jckwds_next_day_fee', $this->settings['datesettings_fees_next_day'] );
			} else {
				WC()->session->__unset( 'jckwds_next_day_fee' );
			}
		} else {
			WC()->session->__unset( 'jckwds_next_day_fee' );
		}

		$day_fees = array_filter( Iconic_WDS_Settings::get_day_fees() );

		if ( ! empty( $day_fees ) ) {
			$ymd = ! empty( $checkout_fields['jckwds-delivery-date-ymd'] ) ? $checkout_fields['jckwds-delivery-date-ymd'] : false;

			if ( ! $ymd ) {
				WC()->session->__unset( 'jckwds_day_fee' );
			} else {
				$date = DateTime::createFromFormat( 'Ymd', $ymd, wp_timezone() );
				$day  = $date->format( 'w' );

				if ( isset( $day_fees[ $ymd ] ) ) {
					WC()->session->set( 'jckwds_day_fee', $day_fees[ $ymd ] );
				} elseif ( isset( $day_fees[ $day ] ) ) {
					WC()->session->set( 'jckwds_day_fee', $day_fees[ $day ] );
				} else {
					WC()->session->__unset( 'jckwds_day_fee' );
				}
			}
		} else {
			WC()->session->__unset( 'jckwds_day_fee' );
		}
	}

	/**
	 * Add timeslot fee at checkout
	 */
	public function add_timeslot_fee() {
		$fees = array(
			WC()->session->get( 'jckwds_timeslot_fee' ),
			WC()->session->get( 'jckwds_same_day_fee' ),
			WC()->session->get( 'jckwds_next_day_fee' ),
			WC()->session->get( 'jckwds_day_fee' ),
		);
		$fee  = array_sum( $fees );

		if ( $fee > 0 ) {
			WC()->cart->add_fee( apply_filters( 'iconic_wds_fee', __( 'Delivery Fee', 'jckwds' ) ), $fee, $this->settings['timesettings_timesettings_calculate_tax'] );
		}
	}

	/**
	 * Helper: Extract timeslot id from option value
	 *
	 * In order to add fees, timeslot options at checkout have a |fee added to their values
	 * This functions let's us extract the timeslot id from that string
	 *
	 * @param string|bool $option_value Option value.
	 *
	 * @return bool|string|int
	 */
	public function extract_timeslot_id_from_option_value( $option_value = false ) {
		if ( ! $option_value ) {
			return false;
		}

		$option_value_exploded = explode( '|', $option_value );

		return $option_value_exploded[0];
	}

	/**
	 * Helper: Extract fee from option value
	 *
	 * As above, but for the fee
	 *
	 * @param string|bool $option_value Option value.
	 *
	 * @return string
	 */
	public function extract_fee_from_option_value( $option_value = false ) {
		if ( ! $option_value ) {
			return false;
		}

		$option_value_exploded = explode( '|', $option_value );
		$fee                   = ( isset( $option_value_exploded[1] ) ) ? (float) $option_value_exploded[1] : 0;

		return $fee;
	}

	/**
	 * Get Woo Version Number
	 *
	 * @return mixed bool/string NULL or Woo version number
	 */
	public function get_woo_version_number() {
		// If get_plugins() isn't available, require it.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Create the plugins folder and file variables.
		$plugin_folder = get_plugins( '/woocommerce' );
		$plugin_file   = 'woocommerce.php';

		// If the plugin version number is set, return it.
		if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
			return $plugin_folder[ $plugin_file ]['Version'];
		} else {
			// Otherwise return null.
			return null;
		}
	}

	/**
	 * Get selected shipping method.
	 *
	 * @return string|null
	 */
	public static function get_chosen_shipping_method() {
		static $chosen_method = null;

		$chosen_method = filter_input(
			INPUT_POST,
			'selected_shipping_method',
			FILTER_SANITIZE_STRING,
			array(
				'default' => null,
			)
		);

		if ( ! is_null( $chosen_method ) ) {
			return apply_filters( 'iconic_wds_chosen_method', strval( $chosen_method ) );
		}

		$order_type = self::get_current_order_type();

		if ( ! $order_type ) {
			return apply_filters( 'iconic_wds_chosen_method', $chosen_method );
		}

		$method        = sprintf( 'get_chosen_shipping_method_for_%s', $order_type );
		$chosen_method = call_user_func( array( __CLASS__, $method ) );

		if ( function_exists( 'WAFS' ) && 'advanced_free_shipping' === $chosen_method ) {
			$wasf = WAFS();

			if ( ! isset( $wasf->was_method ) ) {
				$wasf->wafs_free_shipping();
			}

			$chosen_method = sprintf( '%s_%s', $wasf->was_method->wafs_match_methods(), $chosen_method );
		}

		if ( ( 'distance_rate_shipping' === $chosen_method || 'collection_delivery_shipping' === $chosen_method || 'special_delivery_shipping' === $chosen_method ) && function_exists( 'woocommerce_distance_rate_shipping_get_rule_number' ) ) {
			$rule_id       = woocommerce_distance_rate_shipping_get_rule_number( $chosen_method );
			$chosen_method = sprintf( '%s:%s', $chosen_method, $rule_id );
		}

		return apply_filters( 'iconic_wds_chosen_method', strval( $chosen_method ) );
	}

	/**
	 * Get current order type.
	 *
	 * @return bool|string
	 */
	public static function get_current_order_type() {
		$wc_session = WC()->session;
		if ( ! empty( $wc_session ) ) {
			return 'session';
		}

		if ( ! is_admin() ) {
			return false;
		}

		$selected_shipping_method = filter_input( INPUT_POST, 'selected_shipping_method', FILTER_SANITIZE_STRING );

		if ( $selected_shipping_method ) {
			return 'save_order';
		}

		return 'edit_order';
	}

	/**
	 * Get chosen shipping method for session.
	 *
	 * @return bool|string
	 */
	public static function get_chosen_shipping_method_for_session() {
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( empty( $chosen_methods ) ) {
			return false;
		}

		return strval( $chosen_methods[0] );
	}

	/**
	 * Get chosen shipping method when editing order (admin).
	 *
	 * @return bool|string
	 */
	public static function get_chosen_shipping_method_for_edit_order() {
		$admin_order_id = (int) filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( $admin_order_id <= 0 ) {
			return false;
		}

		$order = wc_get_order( $admin_order_id );

		return strval( Iconic_WDS_Order::get_shipping_method_id( $order ) );
	}

	/**
	 * Get chosen shipping method when saving order (admin).
	 *
	 * @return bool|string
	 */
	public static function get_chosen_shipping_method_for_save_order() {
		$selected_shipping_method = filter_input( INPUT_POST, 'selected_shipping_method', FILTER_SANITIZE_STRING );

		return $selected_shipping_method ? strval( wc_clean( wp_unslash( $selected_shipping_method ) ) ) : false;
	}

	/**
	 * Is timeslot allowed for selected shipping method.
	 *
	 * @param array $timeslot Timeslot.
	 *
	 * @return bool
	 */
	public function is_timeslot_allowed_for_method( $timeslot ) {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', true );
		}

		if ( ! $timeslot['shipping_methods'] ) {
			return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', false );
		}

		if ( in_array( 'any', $timeslot['shipping_methods'], true ) ) {
			return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', true );
		}

		$chosen_method = self::get_chosen_shipping_method();

		if ( in_array( $chosen_method, $timeslot['shipping_methods'], true ) ) {
			return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', true );
		}

		foreach ( $timeslot['shipping_methods'] as $timeslot_shipping_method ) {
			if ( $chosen_method && ( strpos( $timeslot_shipping_method, strval( $chosen_method ) ) !== false ) ) {
				return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', true );
			}
		}

		return apply_filters( 'iconic_wds_timeslot_shipping_method_allowed', false );
	}

	/**
	 * Update order review fragments
	 *
	 * @param array $fragments Fragments.
	 *
	 * @return array
	 */
	public function update_order_review_fragments( $fragments ) {
		$allowed = Iconic_WDS_Checkout::is_delivery_slots_allowed();
		$fragments['iconic_wds'] = array(
			'slots_allowed'          => $allowed,
			'chosen_shipping_method' => $this->get_chosen_shipping_method(),
			'labels'                 => Iconic_WDS_Helpers::get_label(),
			'bookable_dates'         => $allowed ? $this->get_upcoming_bookable_dates( Iconic_WDS_Date_Helpers::date_format() ) : false,
		);

		return $fragments;
	}
}

global $iconic_wds, $jckwds;

$iconic_wds = new Iconic_WDS();

// Backwards compatibility.
$jckwds = $iconic_wds;
