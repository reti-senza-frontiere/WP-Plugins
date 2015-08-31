<?php
/**
 * WooCommerce PDF Product Vouchers
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce PDF Product Vouchers to newer
 * versions in the future. If you wish to customize WooCommerce PDF Product Vouchers for your
 * needs please refer to http://docs.woothemes.com/document/pdf-product-vouchers/ for more information.
 *
 * @package   WC-PDF-Product-Vouchers/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PDF Product Vouchers Voucher handler/helper class.  This class coordinates
 * the WC_Voucher class with the rest of WooCommerce and the plugin, for
 * instance attaching vouchers to order emails, handling download links, etc
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Voucher {

	/** @var array of published voucher post data */
	private $vouchers;

	/** @var WC_PDF_Product_Vouchers parent plugin */
	private $plugin;

	/** @var array of product id to item, used to associate items with products for a filter that does not provide the item */
	private $product_items = array();


	/**
	 * Initialize the taxonomy class
	 *
	 * @since 1.2
	 * @param WC_PDF_Product_Vouchers $plugin parent plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		add_action( 'woocommerce_order_status_completed',     array( $this, 'generate_voucher_pdf' ) );
		if ( 'yes' == get_option( 'woocommerce_downloads_grant_access_after_payment' ) ) {
			add_action( 'woocommerce_order_status_processing', array( $this, 'generate_voucher_pdf' ) );
		}
		add_filter( 'woocommerce_email_attachments',          array( $this, 'voucher_emails_attachments' ), 10, 3 );

		add_filter( 'woocommerce_file_download_path',         array( $this, 'voucher_file_download_path' ), 10, 3 );

		add_filter( 'woocommerce_get_item_downloads',         array( $this, 'get_item_downloads' ), 10, 3 );
		add_filter( 'woocommerce_product_files',              array( $this, 'product_files' ), 10, 2 );
		add_filter( 'woocommerce_product_file',               array( $this, 'product_file' ), 10, 3 );
		add_filter( 'woocommerce_get_product_from_item',      array( $this, 'add_item_to_product' ), 10, 3 );
		add_filter( 'woocommerce_product_file_download_path', array( $this, 'product_file_download_path' ), 10, 3 );

		// add voucher number to admin emails
		add_action( 'woocommerce_before_template_part', array( $this, 'add_admin_email_order_item_meta_actions' ) );
		add_action( 'woocommerce_after_template_part',  array( $this, 'remove_admin_email_order_item_meta_actions' ) );
	}


	/**
	 * Returns the path of the dynamically generated voucher file, if the
	 * identified download contains a voucher.
	 *
	 * This is called when the client visits the download file url, this is the
	 * file that will actually be streamed back to the client
	 *
	 * @since 1.2
	 * @see WC_Product::get_file_download_path()
	 * @param string $file_path file download path
	 * @param WC_Product $product the product object
	 * @param string $download_id the download identifier
	 * @return string the file download path
	 */
	public function product_file_download_path( $file_path, $product, $download_id ) {

		global $wpdb;

		if ( ! $file_path ) {
			// could be a dynamically generated voucher file, find the true product id (variation id if variation) and order id

			list( $product_id, $order_id ) = $wpdb->get_row( $wpdb->prepare( "
					SELECT product_id, order_id
					FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE download_id = %s", $download_id ), ARRAY_N );

			$product = wc_get_product( $product_id );

			// voucher files are dynamically generated per order
			if ( $order_id && WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {

				$order = wc_get_order( $order_id );

				$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download_id ) );

				if ( $voucher && $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {
					$file_path = $voucher->get_voucher_full_filename( $this->plugin->get_uploads_path() );

					if ( 'redirect' === get_option( 'woocommerce_file_download_method' ) ) {
						$file_path = $voucher->convert_path_to_url( $file_path );
					}
				}
			}
		}

		return $file_path;
	}


	/**
	 * Returns the path and displayed name of the dynamically generated voucher
	 * file, if this download is for a voucher.
	 *
	 * @since 1.2
	 * @see WC_Product::get_file()
	 * @param array $file associative array with 'file' and 'name' keys
	 * @param WC_Product $product the product
	 * @param mixed $download_id download identifier
	 */
	public function product_file( $file, $product, $download_id ) {

		global $wpdb;

		if ( ! $file['file'] ) {
			// could be a dynamically generated voucher file, find the true product id (variation id if variation) and order id

			list( $product_id, $order_id ) = $wpdb->get_row( $wpdb->prepare( "
					SELECT product_id, order_id
					FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE download_id = %s", $download_id ), ARRAY_N );

			$product = wc_get_product( $product_id );

			// voucher files are dynamically generated per order
			if ( $order_id && WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {

				$order = wc_get_order( $order_id );

				$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download_id ) );

				if ( $voucher && $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {
					$file = array( 'file' => $voucher->get_voucher_full_filename( $this->plugin->get_uploads_path() ), 'name' => $voucher->get_voucher_filename() );
				}
			}
		}

		return $file;
	}


	/**
	 * Adds the item to the product object, which wouldn't otherwise be
	 * available if all you have is the product.
	 *
	 * This is only really required while generating the voucher/download
	 * record and linking everything together
	 *
	 * @since 1.2
	 * @see WC_PDF_Product_Vouchers_Voucher::product_files()
	 * @param WC_Product $product the product object
	 * @param array $item the item array
	 * @param WC_Order the order object
	 */
	public function add_item_to_product( $product, $item, $order ) {

		// if the item contains a voucher
		if ( isset( $item['voucher_id'] ) ) {
			$this->product_items[ isset( $product->variation_id ) ? $product->variation_id : $product->id ] = $item;
		}

		return $product;
	}


	/**
	 * Returns the voucher number if the given product/order/item has a
	 * voucher attached.  The main purpose of this is to create the
	 * downloadable file permissions record with a download_id equal to
	 * wc_vouchers_{voucher number}
	 *
	 * @since 1.2
	 * @see WC_PDF_Product_Vouchers_Voucher::add_item_to_product()
	 * @see WC_Product::get_files()
	 * @param array $files associative array of download_id to array( 'file' => path, 'name' => name )
	 * @param WC_Product $product the product
	 * @return array downloadable files
	 */
	public function product_files( $files, $product ) {

		$product_id = isset( $product->variation_id ) ? $product->variation_id : $product->id;

		if ( isset( $this->product_items[ $product_id ] ) && $this->product_items[ $product_id ] &&
			isset( $this->product_items[ $product_id ]['voucher_number'] ) && $this->product_items[ $product_id ]['voucher_number'] ) {

			$files[ 'wc_vouchers_' . $this->product_items[ $product_id ]['voucher_number'] ] = array( 'file' => '', 'name' => '' );
		}

		return $files;
	}


	/**
	 * Without this, in the case of a single order containing the same product
	 * multiple times with different meta information (ie, with one Voucher
	 * Recipient on one, and a different Voucher Recipient on the other),
	 * each order item would contain all the download links for the product
	 * in the download links email and order summary pages.
	 * Here we make sure that the download links only pertain to the correct
	 * order item for voucher products, regular downloadable products are left
	 * alone.
	 *
	 * @since 1.2
	 * @param array $files associative array of download_id to array( 'name' => display name, 'file' => url or path to file, 'download_url' => download url )
	 * @param array $item the item array
	 * @param WC_Order $order the order object
	 * @return associative array of download_id to array( 'name' => display name, 'file' => url or path to file, 'download_url' => download url )
	 */
	public function get_item_downloads( $files, $item, $order ) {

		$new_files = array();

		foreach ( $files as $download_id => $file ) {

			if ( false !== strpos( $download_id, 'wc_vouchers_' ) && isset( $item['voucher_number'] ) ) {

				// voucher file belongs to the current item
				if ( 'wc_vouchers_' . $item['voucher_number'] == $download_id ) {

					$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download_id ) );

					if ( $voucher && $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {
						$new_files[ $download_id ] = $file;
					}
				}

			} else {
				// regular file download
				$new_files[ $download_id ] = $file;
			}

		}

		return $new_files;
	}


	/** Voucher download and attachment methods ******************************************************/


	/**
	 * Return our voucher file path as the download file
	 *
	 * @since 1.2
	 * @param string $download_file_path the download file path
	 * @param int $product the product
	 * @param int $download_id downloadable file identifier
	 *
	 * @return string the download file path
	 */
	public function voucher_file_download_path( $download_file_path, $product, $download_id ) {

		global $wpdb, $post;

		// if there is no download file path set, it means this might be an item voucher being requested
		if ( ! $download_file_path ) {

			$order_id = null;
			if ( $post && 'shop_order' == $post->post_type && $post->ID ) {
				$order_id = $post->ID;
			}

			// are we being called from the context of the has_file() checks?
			if ( ! $order_id && ( ! isset( $_GET['download_file'] ) || ! isset( $_GET['order'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['key'] ) ) ) {

				if ( WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {
					return $download_id;
				} else {
					// otherwise, non-voucher product, return the passed-in value
					return $download_file_path;
				}
			}

			if ( ! $order_id ) {
				// get the order id from the GET parameters when needed
				$order_key = urldecode( $_GET['order'] );
				$email     = str_replace( ' ', '+', urldecode( $_GET['email'] ) );

				$order_id = $wpdb->get_var( $wpdb->prepare( "
					SELECT order_id
					FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
					WHERE user_email = %s
					AND order_key = %s
					AND product_id = %s
					AND download_id = %s", $email, $order_key, isset( $product->variation_id ) ? $product->variation_id : $product->id, $download_id )
				);
			}
			// get and return the path to the requested voucher file
			if ( $order_id ) {

				$order = wc_get_order( $order_id );

				$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download_id ) );

				if ( $voucher && $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {

					$download_file_path = $voucher->get_voucher_full_filename( $this->plugin->get_uploads_path() );

					if ( 'redirect' === get_option( 'woocommerce_file_download_method' ) ) {
						$download_file_path = $voucher->convert_path_to_url( $file_path );
					}
				}
			}
		}

		return $download_file_path;
	}


	/**
	 * If there are any voucher items in this order, attach the relevant
	 * voucher files
	 *
	 * @since 1.2
	 * @param array $attachments array of file locations to attach to the email
	 * @param string $email_type the email type
	 * @param WC_Order|array $object the object associated with this email, either a WC_Order object, or array containing one
	 *
	 * @return array of file locations to attach to the email
	 */
	public function voucher_emails_attachments( $attachments, $email_type, $object ) {

		// if this is the completed order email, or the processing email and
		//  download access is granted after payment, or the customer_invoice
		//  email, and we have captured an order, attach any vouchers
		if ( in_array( $email_type, array( 'customer_completed_order', 'customer_invoice' ) ) ||
			( 'customer_processing_order' == $email_type && 'yes' == get_option( 'woocommerce_downloads_grant_access_after_payment' ) ) ) {

			$order = $object;

			foreach ( WC_PDF_Product_Vouchers_Order::get_vouchers( $order ) as $voucher ) {
				if ( $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {
					$attachments[] = $voucher->get_voucher_full_filename( $this->plugin->get_uploads_path() );
				}
			}

		} elseif ( 'wc_pdf_product_vouchers_voucher_recipient' == $email_type ) {
			// voucher recipient email type, only attach the vouchers that are destined for the recipient email

			$recipient_email = $object['recipient_email'];
			$order           = $object['order'];

			foreach ( WC_PDF_Product_Vouchers_Order::get_vouchers( $order ) as $voucher ) {
				if ( $recipient_email == $voucher->get_recipient_email() && $voucher->file_exists( $this->plugin->get_uploads_path() ) ) {
					$attachments[] = $voucher->get_voucher_full_filename( $this->plugin->get_uploads_path() );
				}
			}

		}

		return $attachments;
	}


	/** Voucher Creation ******************************************************/


	/**
	 * Invoked when an order status changes to 'completed', or 'processing'
	 * depending on how WooCommerce is configured.  If the order has any
	 * voucher items, the voucher PDF files are generated
	 *
	 * @since 1.2
	 * @param int $order_id newly created order identifier
	 * @param boolean $force use true to force the voucher to be re-generated regardless of whether it already exists
	 */
	public function generate_voucher_pdf( $order_id, $force = false ) {

		global $wpdb;

		$order = wc_get_order( $order_id );

		foreach ( WC_PDF_Product_Vouchers_Order::get_vouchers( $order ) as $voucher ) {

			// voucher has already been generated
			if ( $voucher->file_exists( $this->plugin->get_uploads_path() ) && ! $force ) {
				continue;
			}

			// NOTE: I could set the expiration (and voucher number) on the order item meta if I want it displayed in the frontend
			// set the expiration date if needed
			if ( ! $voucher->get_formatted_expiration_date() && $voucher->get_expiry() ) {
				$expiry_from_date = apply_filters( 'wc_pdf_product_vouchers_expiry_from_date', time(), $order_id, $voucher );
				$voucher->set_expiration_date( $expiry_from_date + $voucher->get_expiry() * 24 * 60 * 60 );

				wc_update_order_item_meta( $voucher->get_item_id(), '_voucher_expiration', date( 'Y-m-d', time() + $voucher->get_expiry() * 24 * 60 * 60 ) );
			}

			// ensure the path that will hold the voucher pdf exists
			$voucher_pdf_path = $this->plugin->get_uploads_path() . '/' . $voucher->get_voucher_path();

			if ( ! file_exists( $voucher_pdf_path ) ) {
				@mkdir( $voucher_pdf_path, 0777, true );
			}

			// is the output path writable?
			if ( ! is_writable( $voucher_pdf_path ) ) {
				$order->add_order_note( sprintf( __( "PDF Product Vouchers file permission error: unable to generate voucher %s in %s  Please fix the directory permissions and re-generate the voucher.", WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $voucher->get_voucher_number(), $voucher_pdf_path ) );
				continue;
			}

			// try to generate the voucher
			try {
				$voucher->generate_pdf( $this->plugin->get_uploads_path() );
			} catch ( Exception $exception ) {
				$order->add_order_note( sprintf( __( "PDF Product Vouchers: unable to generate voucher %s due to: %s", WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $voucher->get_voucher_number(), $exception->getMessage() ) );
			}
		}
	}


	/**
	 * Add the the required action needed to display the voucher number in the
	 * order items table of the admin new order email
	 *
	 * @since 2.3.0
	 * @param string $template_name The template name
	 */
	public function add_admin_email_order_item_meta_actions( $template_name ) {

		if ( 'emails/admin-new-order.php' === $template_name || 'emails/plain/admin-new-order.php' === $template_name ) {
			add_action( 'woocommerce_order_item_meta_end', array( $this, 'add_voucher_number_to_email_order_item_meta' ), 10, 3 );
		}
	}


	/**
	 * Remove the the required action needed to display the voucher number in the
	 * order items table of the admin new order email. This ensures the voucher
	 * number is only added to this email and not the front end or customer emails.
	 *
	 * @since 2.3.0
	 * @param string $template_name The template name
	 */
	public function remove_admin_email_order_item_meta_actions( $template_name ) {

		if ( 'emails/admin-new-order.php' === $template_name || 'emails/plain/admin-new-order.php' === $template_name ) {
			remove_action( 'woocommerce_order_item_meta_end', array( $this, 'add_voucher_number_to_email_order_item_meta' ), 10, 3 );
		}
	}


	/**
	 * Add the voucher number to the order items table of admin
	 *
	 * @since 2.3.0
	 * @param int $item_id The item ID
	 * @param array $item The order item
	 * @param WC_Order $order The order object
	 */
	public function add_voucher_number_to_email_order_item_meta( $item_id, $item, $order ) {

		if ( $item['product_id'] > 0 && isset( $item['voucher_id'] ) && $item['voucher_id'] ) {
			$voucher = new WC_Voucher( $item['voucher_id'], $order->id, $item );
			echo '<br/><small>' . __( 'Voucher Number: ', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . $voucher->get_voucher_number() . '</small>';
		}
	}


	/** Helper methods ******************************************************/


	/**
	 * Generate a new sequential voucher number
	 *
	 * @since 1.2
	 * @return int voucher number or null on failure
	 */
	public static function generate_voucher_number() {

		global $wpdb;
		$number = null;

		$success = false;
		// attempt the query up to 3 times for a much higher success rate if it fails (due to Deadlock)
		for ( $i = 0; $i < 3 && ! $success; $i++ ) {
			// do our best to compensate for mysql not having an UPDATE... RETURNING facility
			$success = $wpdb->query( "UPDATE {$wpdb->options} SET option_value = @option_value := CAST(option_value as unsigned) + 1 WHERE option_name='wc_vouchers_number_start'" );
		}

		if ( $success ) {
			// get our updated voucher number
			$number = (int) $wpdb->get_var( 'SELECT @option_value' );
		}

		do_action( 'wc_pdf_product_vouchers_generate_voucher_number', $number );
		$number = apply_filters( 'wc_pdf_product_vouchers_get_voucher_number', $number );

		return $number;
	}


	/**
	 * Get all live (private) vouchers
	 *
	 * @since 1.2
	 * @return array of voucher post objects
	 */
	public function get_vouchers() {

		if ( ! isset( $this->vouchers ) ) {
			$args = array( 'post_type' => 'wc_voucher', 'numberposts' => -1, 'order'=> 'ASC', 'orderby' => 'title', 'post_status' => 'private' );
			$this->vouchers = get_posts( $args );
		}

		return $this->vouchers;
	}

}
