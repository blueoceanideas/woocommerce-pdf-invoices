<?php
/**
 * General settings
 *
 * Handling general settings.
 *
 * @author      Bas Elbers
 * @category    Admin
 * @package     BE_WooCommerce_PDF_Invoices/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BEWPI_General_Settings' ) ) {
	/**
	 * Class BEWPI_General_Settings.
	 */
	class BEWPI_General_Settings extends BEWPI_Abstract_Setting {
		/**
		 * Constant template settings key
		 *
		 * @var string
		 */
		const SETTINGS_KEY = self::PREFIX . 'general_settings';

		/**
		 * Initializes the template settings.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_notices', array( $this, 'show_settings_notices' ) );
		}

		/**
		 * Initialize settings within admin.
		 */
		public function admin_init() {
			$this->load_settings();
			$this->create_settings();
		}

		/**
		 * Settings configuration.
		 *
		 * @return array
		 */
		private function the_settings() {
			$settings = array(
				array(
					'id'       => 'bewpi-email-types',
					'name'     => self::PREFIX . 'email_types',
					'title'    => __( 'Attach to Email', 'woocommerce-pdf-invoices' ),
					'callback' => array( $this, 'multiple_checkbox_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'email',
					'desc'     => '',
					'options'  => array(
						array(
							'name'    => __( 'New order', 'woocommerce-pdf-invoices' ),
							'value'   => 'new_order',
							'default' => 0,
						),
						array(
							'name'    => __( 'Order on-hold', 'woocommerce-pdf-invoices' ),
							'value'   => 'customer_on_hold_order',
							'default' => 0,
						),
						array(
							'name'    => __( 'Processing order', 'woocommerce-pdf-invoices' ),
							'value'   => 'customer_processing_order',
							'default' => 0,
						),
						array(
							'name'    => __( 'Completed order', 'woocommerce-pdf-invoices' ),
							'value'   => 'customer_completed_order',
							'default' => 0,
						),
						array(
							'name'    => __( 'Customer invoice', 'woocommerce-pdf-invoices' ),
							'value'   => 'customer_invoice',
							'default' => 0,
						),
					),
				),
				array(
					'id'       => 'bewpi-view-pdf',
					'name'     => self::PREFIX . 'view_pdf',
					'title'    => __( 'View PDF', 'woocommerce-pdf-invoices' ),
					'callback' => array( &$this, 'select_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'download',
					'type'     => 'text',
					'desc'     => '',
					'options'  => array(
						array(
							'name'  => __( 'Download', 'woocommerce-pdf-invoices' ),
							'value' => 'download',
						),
						array(
							'name'  => __( 'Open in new browser tab/window', 'woocommerce-pdf-invoices' ),
							'value' => 'browser',
						),
					),
					'default'  => 'download',
				),
				array(
					'id'       => 'bewpi-download-invoice-account',
					'name'     => self::PREFIX . 'download_invoice_account',
					'title'    => '',
					'callback' => array( &$this, 'input_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'download',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable download from account', 'woocommerce-pdf-invoices' )
					              . __( '<br/><div class="bewpi-notes">Let customers download invoice from account page.</div>', 'woocommerce-pdf-invoices' ),
					'class'    => 'bewpi-checkbox-option-title',
					'default'  => 1,
				),
				array(
					'id'       => 'bewpi-email-it-in',
					'name'     => self::PREFIX . 'email_it_in',
					'title'    => '',
					'callback' => array( &$this, 'input_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'cloud_storage',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable Email It In', 'woocommerce-pdf-invoices' ),
					'class'    => 'bewpi-checkbox-option-title',
					'default'  => 0,
				),
				array(
					'id'       => 'bewpi-email-it-in-account',
					'name'     => self::PREFIX . 'email_it_in_account',
					'title'    => __( 'Email It In account', 'woocommerce-pdf-invoices' ),
					'callback' => array( &$this, 'input_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'cloud_storage',
					'type'     => 'text',
					'desc'     => sprintf( __( 'Get your account from your Email It In <a href="%1$s">user account</a>.', 'woocommerce-pdf-invoices' ), 'https://www.emailitin.com/user_account' ),
					'default'  => '',
				),
				array(
					'id'       => 'bewpi-mpdf-debug',
					'name'     => self::PREFIX . 'mpdf_debug',
					'title'    => '',
					'callback' => array( &$this, 'input_callback' ),
					'page'     => self::SETTINGS_KEY,
					'section'  => 'debug',
					'type'     => 'checkbox',
					'desc'     => __( 'Enable mPDF debugging' )
					              . '<br/><div class="bewpi-notes">' . __( 'Enable mPDF debugging if you aren\'t able to create an invoice.', 'woocommerce-pdf-invoices' ) . '</div>',
					'class'    => 'bewpi-checkbox-option-title',
					'default'  => 0,
				),
			);

			return apply_filters( 'bewpi_general_settings', $settings );
		}

		/**
		 * Get all default values from the settings array.
		 *
		 * @return array
		 */
		private function get_defaults() {
			$settings = $this->the_settings();

			// defaults of email types are within a lower hierarchy.
			$email_types = $settings[0];
			$defaults    = wp_list_pluck( $email_types['options'], 'default', 'value' );

			// remove email types settings.
			array_shift( $settings );
			$defaults = array_merge( $defaults, wp_list_pluck( $settings, 'default', 'name' ) );

			return $defaults;
		}

		/**
		 * Load all settings into settings var and merge with defaults.
		 */
		public function load_settings() {
			$defaults = $this->get_defaults();
			$options  = get_option( self::SETTINGS_KEY );
			$options  = array_merge( $defaults, $options );
			update_option( self::SETTINGS_KEY, $options );
		}

		/**
		 * Adds all the different settings sections
		 */
		private function add_settings_sections() {
			add_settings_section( 'email', __( 'Email Options', 'woocommerce-pdf-invoices' ), null, self::SETTINGS_KEY );
			add_settings_section( 'download', __( 'Download Options', 'woocommerce-pdf-invoices' ), null, self::SETTINGS_KEY );
			add_settings_section( 'cloud_storage', __( 'Cloud Storage Options', 'woocommerce-pdf-invoices' ), array( &$this, 'cloud_storage_desc_callback' ), self::SETTINGS_KEY );
			add_settings_section( 'debug', __( 'Debug Options', 'woocommerce-pdf-invoices' ), null, self::SETTINGS_KEY );
		}

		/**
		 * Validate settings.
		 *
		 * @param array $input settings.
		 *
		 * @return mixed|void
		 */
		public function validate_input( $input ) {
			$output = get_option( self::SETTINGS_KEY );
			foreach ( $input as $key => $value ) {
				// strip all html tags and properly handle quoted strings.
				$output[ $key ] = stripslashes( $input[ $key ] );
			}

			// sanitize email it in account.
			if ( isset( $input['email_it_in_account'] ) ) {
				$sanitized_email = sanitize_email( $input['email_it_in_account'] );
				$output['email_it_in_account'] = $sanitized_email;
			}

			return apply_filters( 'validate_input', $output, $input );
		}

		/**
		 * Adds settings fields
		 */
		private function add_settings_fields() {
			$the_settings = $this->the_settings();
			foreach ( $the_settings as $setting ) {
				add_settings_field( $setting['name'], $setting['title'], $setting['callback'], $setting['page'], $setting['section'], $setting );
			};
		}

		/**
		 * Register all settings fields.
		 */
		public function create_settings() {
			$this->add_settings_sections();
			register_setting( self::SETTINGS_KEY, self::SETTINGS_KEY, array( $this, 'validate_input' ) );
			$this->add_settings_fields();
		}

		/**
		 * Cloud Storage section callback.
		 */
		public function cloud_storage_desc_callback() {
			printf( __( 'Sign-up at <a href="%1$s">Email It In</a> to send invoices to your Dropbox, OneDrive, Google Drive or Egnyte and enter your account below.', 'woocommerce-pdf-invoices' ), 'https://emailitin.com' ); // WPCS: XSS OK.
		}

		/**
		 * Show all settings notices.
		 */
		public function show_settings_notices() {
			settings_errors( self::SETTINGS_KEY );
		}
	}
}
