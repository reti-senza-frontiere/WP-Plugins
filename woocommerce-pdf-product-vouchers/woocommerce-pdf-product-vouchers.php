<?php
/**
 * Plugin Name: WooCommerce PDF Product Vouchers
 * Plugin URI: http://www.woothemes.com/products/pdf-product-vouchers/
 * Description: Customize and sell PDF product vouchers with WooCommerce
 * Author: WooThemes / SkyVerge
 * Author URI: http://www.woothemes.com
 * Version: 2.4.0
 * Text Domain: woocommerce-pdf-product-vouchers
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2012-2015 SkyVerge (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-PDF-Product-Vouchers
 * @author    SkyVerge
 * @category  Plugin
 * @copyright Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), '76aaaee5ebc9fdab1847c6f3caf295b3', '201963' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library classss
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.0.0', __( 'WooCommerce PDF Product Vouchers', 'woocommerce-pdf-product-vouchers' ), __FILE__, 'init_woocommerce_pdf_product_vouchers', array( 'minimum_wc_version' => '2.2.5', 'backwards_compatible' => '4.0.0' ) );

function init_woocommerce_pdf_product_vouchers() {

/**
 * <h2>WooCommerce PDF Product Vouchers main plugin class</h2>
 *
 * <h3>Plugin Overview</h3>
 *
 * This plugin allows an admin to create and customize "Voucher Templates"
 * which can be assigned to Simple/Variable Downloadable products and purchased
 * by customers.  Once purchased the Voucher will be created when it's
 * available for download (order completed or processing with "Grant access to
 * downloadable products after payment" enabled) and can be downloaded like any
 * standard downloadable product.
 *
 * <h3>Terminology</h3>
 *
 * **Voucher Template** - the Voucher custom post type which is configured
 *  by the admin
 *
 * **Product Voucher** - a realization of a Voucher Template when attached to a
 *   product and purchased by a customer
 *
 * <h3>Admin Considerations</h3>
 *
 * This plugin adds a "Vouchers" menu item to the "WooCommerce" top level menu
 * where the Voucher Custom Post Type items are listed and created/edited.
 * Various images and fields can be added to a Voucher template and configured.
 *
 * Within the Order Admin a new admin panel named "Vouchers" is added and contains
 * any associated vouchers, displaying the voucher image, name, other identifying
 * fields, as well as an optional "Expires" datepicker field, and a redemption date
 * datepicker field.
 *
 * Any generated/available voucher files are displayed in the Downloadable
 * Product Permissions table.
 *
 * <h3>Frontend Considerations</h3>
 *
 * This plugin adds to the product page zoomable thumbnails of the primary
 * image(s) as well as Recipient Name/Recipient Message input fields (if
 * configured).  After adding to the cart, the product Voucher is available
 * like any other product download.
 *
 * <h3>Database</h3>
 *
 * <h4>Options table</h4>
 *
 * **wc_vouchers_db_version** - the current plugin version, set on install/upgrade
 *
 * **wc_vouchers_number_start** - the current voucher number, set to '0' on install.
 * This is used to generate a unique, sequential voucher number
 *
 * <h4>Voucher Custom Post Type</h4>
 *
 * **wc_voucher** - A Custom Post Type which represents a voucher layout/options
 *
 * <h4>Voucher CPT Postmeta</h4>
 *
 * **thumbnail_id** - (int) identifies the voucher default primary image
 *
 * **_image_ids** - (array) of voucher primary image options.  This should
 * have at least one member, equal to the _thumbnail_id
 *
 * **_additional_image_ids** - (array) optional array of additional (page 2)
 * images.  For now this can only contain one member
 *
 * **_voucher_font_color** - (string) default voucher font color hex value.
 * This can be overridden by an individual voucher field
 *
 * **_voucher_font_size** - (int) default voucher font size.  This can be
 * overridden by an individual voucher field
 *
 * **_voucher_font_family** - (string) default voucher font family.  This can be
 * overridden by an individual voucher field.
 *
 * **_voucher_font_style** - (string) default voucher font style (bold/italic).
 * This can be overridden by an individual voucher field.
 *
 * **_voucher_fields** - (array) voucher field definitions.  Consists of a field
 * identifier (one of 'product_name', 'product_sku', 'voucher_number',
 * 'expiration_date', 'recipient_name', or 'message') to the following datastructure:
 *
 * <pre>
 * Array(
 *   type => 'property'|'user_input',
 *   order => int admin ordering,
 *   font => Array(
 *     family => optional font family override,
 *     size => optional font size override,
 *     style => optional font style override,
 *     color => optional font color override,
 *   ),
 *   position => Array(
 *     x1 => field x position,
 *     y1 => field y position,
 *     width => field width,
 *     height => field height,
 *   ),
 *   days_to_expiry => int days to expiration available for the 'expiration_date' field,
 *   label => user_input type field name which is displayed on the frontend,
 *   input_type => 'text'|'textarea' available for the user_input type fields,
 *   max_length => maximum allowable user input length for the field and available for the user_input type fields,
 *   is_required => boolean whether the user_input type field is required,
 *   is_enabled => boolean whether the user_input type field is enabled though not necessarily printed to the voucher,
 *   multiline => boolean true if the field should be rendered as a text area on the voucher, false for single line
 * )
 * </pre>
 *
 * <h4>Product Postmeta</h4>
 *
 * **_voucher_id** - (int) identifies a voucher when a product is configured
 *  as a downloadable voucher product
 *
 * <h4>Order Postmeta</h4>
 *
 * **_voucher_redeemed** - (int) set to '1' when an order contains only vouchers
 * and all have been redeemed
 *
 * <h4>Order Item Meta Data</h4>
 *
 * **_voucher_id** - (int)  Identifies the voucher post
 *
 * **_voucher_number** - (int)  Unique, incrementing voucher number
 *
 * **_voucher_image_id** - (int)  Identifies the image media selected by the
 * customer during purchase
 *
 * **_voucher_expiration** - (date)  Optional voucher expiration date, based
 * off of the voucher being generated and available to customer, and the
 * Voucher days to expiry, if set.
 *
 * **_voucher_redeem** - (array)  Array of dates for which this voucher has
 * been redeemed.  Products can be purchased with multiple quantities, so a
 * single voucher could be redeemed twice, on different dates for instance.
 *
 * **Voucher Message** (string)  Optional visible voucher message for Vouchers
 * which have a configured voucher message option, and a customer who supplies a message
 *
 * **Voucher Recipient** (string)  Optional visible voucher recipient for Vouchers
 * which have a configured voucher recipient option, and a customer who supplies
 * a recipient name (this does not default to the purchasing customer)
 *
 * <h4>woocommerce_downloadable_product_permissions Table</h4>
 *
 * The download_id column for the downloadable product permissions table is used
 * to identify the particular product voucher, and is in the format
 * wc_vouchers{voucher_id} where {voucher_id} is the uniquely generated product
 * voucher identifier.
 */
class WC_PDF_Product_Vouchers extends SV_WC_Plugin {


	/** version number */
	const VERSION = '2.4.0';

	/** @var WC_PDF_Product_Vouchers single instance of this plugin */
	protected static $instance;

	/** string the plugin id */
	const PLUGIN_ID = 'pdf_product_vouchers';

	/** string plugin text domain */
	const TEXT_DOMAIN = 'woocommerce-pdf-product-vouchers';

	/** Voucher image thumbnail width */
	const VOUCHER_IMAGE_THUMB_WIDTH = 100;


	/** @var WC_PDF_Product_Vouchers_Product product class */
	private $product;

	/** @var WC_PDF_Product_Vouchers_Cart cart class */
	private $cart;

	/** @var WC_PDF_Product_Vouchers_My_Account My Account handler/helper */
	private $my_account;

	/** @var WC_PDF_Product_Vouchers_Taxonomy taxonomy helper class */
	private $taxonomy;

	/** @var WC_PDF_Product_Vouchers_Voucher voucher handler/helper */
	private $voucher_handler;

	/** @var WC_PDF_Product_Vouchers_Admin PDF product vouchers admin */
	private $admin;


	/**
	 * Setup main plugin class
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::__construct()
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			self::TEXT_DOMAIN
		);

		// include required files
		$this->includes();

		add_action( 'init', array( $this, 'include_template_functions' ), 25 );

		// generate voucher pdf, attach to emails, handle downloads
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ) );
	}


	/**
	 * Files required by both the admin and frontend
	 *
	 * @since 1.0
	 */
	private function includes() {

		if ( is_admin() ) {
			$this->admin_includes();
		}

		require_once( $this->get_plugin_path() . '/includes/class-wc-voucher.php' );

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-product.php' );
		$this->product = new WC_PDF_Product_Vouchers_Product( $this );

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-cart.php' );
		$this->cart = new WC_PDF_Product_Vouchers_Cart();

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-my-account.php' );
		$this->my_account = new WC_PDF_Product_Vouchers_My_Account();

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-taxonomy.php' );
		$this->taxonomy = new WC_PDF_Product_Vouchers_Taxonomy();

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-voucher.php' );
		$this->voucher_handler = new WC_PDF_Product_Vouchers_Voucher( $this );

		require_once( $this->get_plugin_path() . '/includes/class-wc-pdf-product-vouchers-order.php' );
	}


	/**
	 * Include required voucher admin files
	 *
	 * @since 1.0
	 */
	private function admin_includes() {

		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin.php' );
		$this->admin = new WC_PDF_Product_Vouchers_Admin( $this );
	}


	/**
	 * Include WooCommerce PDF Product Vouchers template functions
	 *
	 * @since 1.0
	 */
	public function include_template_functions() {
		require_once( $this->get_plugin_path() . '/includes/wc-pdf-product-vouchers-template.php' );
	}


	/**
	 * Load plugin text domain.
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::load_translation()
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-pdf-product-vouchers', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/**
	 * Adds PDF Product Vouchers email class
	 *
	 * @since 1.2
	 */
	public function add_email_classes( $email_classes ) {

		require_once( $this->get_plugin_path() . '/includes/emails/class-wc-pdf-product-vouchers-email-voucher-recipient.php' );

		$email_classes['WC_PDF_Product_Vouchers_Email_Voucher_Recipient'] = new WC_PDF_Product_Vouchers_Email_Voucher_Recipient();

		return $email_classes;
	}


	/** Admin methods ******************************************************/


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0-1
	 * @see SV_WC_Plugin::get_settings_url()
	 * @param string $plugin_id the plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {

		// link to the wc_voucher list table
		return admin_url( 'edit.php?post_type=wc_voucher' );
	}


	/**
	 * Returns true if on the Vouchers List Table/Edit screens
	 *
	 * @since 1.0-1
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the admin gateway settings page
	 */
	public function is_plugin_settings() {
		return isset( $_GET['post_type'] ) && 'wc_voucher' == $_GET['post_type'];
	}


	/**
	 * Checks if required PHP extensions are loaded and adds an admin notice
	 * for any missing extensions.  Also plugin settings can be checked
	 * as well.
	 *
	 * @since 2.1.1
	 * @see SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		parent::add_admin_notices();

		$this->add_file_permissions_notices();
	}


	/**
	 * Render an admin error if there's a directory permission that will prevent
	 * voucher files from being written
	 *
	 * @since 2.1.1
	 */
	private function add_file_permissions_notices() {

		// check for file permission errors
		$message    = __( '%s: non-writable path %s%s%s detected, please fix directory permissions or voucher files may not be able to be generated.', self::TEXT_DOMAIN );
		$message_id = null;
		$upload_dir = wp_upload_dir();

		if ( ! is_writable( $upload_dir['basedir'] ) ) {
			$message = sprintf( $message, $this->get_plugin_name(), $upload_dir['basedir'] );
			$message_id = 'bad-perms-1';
		} elseif ( ! is_writable( self::get_woocommerce_uploads_path() ) ) {
			$message = sprintf( $message, $this->get_plugin_name(), '<code>', self::get_woocommerce_uploads_path(), '</code>' );
			$message_id = 'bad-perms-2';
		} elseif ( file_exists( self::get_uploads_path() ) && ! is_writable( self::get_uploads_path() ) ) {
			$message = sprintf( $message, $this->get_plugin_name(), self::get_uploads_path() );
			$message_id = 'bad-perms-3';
		}

		if ( $message_id ) {
			$this->get_admin_notice_handler()->add_admin_notice( $message, $message_id );
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Main PDF Product Vouchers Instance, ensures only one instance is/can be loaded
	 *
	 * @since 2.2.0
	 * @see wc_pdf_product_vouchers()
	 * @return WC_PDF_Product_Vouchers
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce PDF Product Vouchers', self::TEXT_DOMAIN );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/**
	 * Returns the uploads path, which is used to store the generated PDF
	 * product voucher files
	 *
	 * @since 1.0
	 * @return string upload path for this plugin
	 */
	public static function get_uploads_path() {
		return self::get_woocommerce_uploads_path() . '/woocommerce_pdf_product_vouchers';
	}


	/**
	 * Returns the voucher helper/handler class
	 *
	 * @since 1.2
	 * @return WC_PDF_Product_Vouchers_Voucher voucher helper/handler class
	 */
	public function get_voucher_handler() {
		return $this->voucher_handler;
	}


	/**
	 * Gets the plugin documentation url
	 *
	 * @since 2.4.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {
		return 'http://docs.woothemes.com/document/woocommerce-pdf-product-vouchers/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 2.4.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'http://support.woothemes.com/';
	}

	/** Lifecycle methods ******************************************************/


	/**
	 * Plugin install method
	 *
	 * @since 1.0
	 * @see SV_WC_Plugin::install()
	 */
	protected function install() {
		add_option( 'wc_vouchers_number_start', '0' );
	}


	/**
	 * Plugin upgrade method
	 *
	 * @since 2.0.3-2
	 * @see SV_WC_Plugin::upgrade()
	 * @param string $installed version the currently installed version we are upgrading from
	 */
	protected function upgrade( $installed_version ) {

		global $wpdb;

		if ( version_compare( $installed_version, '2.0.3-2', '<' ) ) {
			// Actually in version 2.0 a field name changed 'display_name' -> 'label' that we forgot to update for existing shops

			$results = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='_voucher_fields'" );

			if ( is_array( $results ) ) {
				foreach ( $results as $row ) {
					$fixed = false;
					$voucher_fields = maybe_unserialize( $row->meta_value );

					if ( is_array( $voucher_fields ) ) {
						foreach ( $voucher_fields as $name => $field ) {
							if ( isset( $field['display_name'] ) && ( ! isset( $field['label'] ) || ! $field['label'] ) ) {
								// old-style
								if ( 'recipient_name' == $name ) {
									$voucher_fields[ $name ]['label'] = 'Recipient Name';
									unset( $voucher_fields[ $name ]['display_name'] );
								} elseif ( 'message' == $name ) {
									$voucher_fields[ $name ]['label'] = 'Message to recipient';
									unset( $voucher_fields[ $name ]['display_name'] );
								}
								$fixed = true;
							}
						}
					}

					if ( $fixed ) {
						update_post_meta( $row->post_id, '_voucher_fields', $voucher_fields );
					}
				}
			}
			unset( $results );
		}
	}


}


/**
 * Returns the One True Instance of PDF Product Vouchers
 *
 * @since 2.2.0
 * @return WC_PDF_Product_Vouchers
 */
function wc_pdf_product_vouchers() {
	return WC_PDF_Product_Vouchers::instance();
}


/**
 * The WC_PDF_Product_Vouchers global object
 *
 * @deprecated 2.2.0
 * @name $wc_pdf_product_vouchers
 * @global WC_PDF_Product_Vouchers $GLOBALS['wc_pdf_product_vouchers']
 */
$GLOBALS['wc_pdf_product_vouchers'] = wc_pdf_product_vouchers();

} // init_woocommerce_pdf_product_vouchers()
