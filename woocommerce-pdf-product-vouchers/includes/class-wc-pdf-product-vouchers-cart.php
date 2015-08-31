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
 * PDF Product Vouchers Cart handler/helper class
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Cart {


	/**
	 * Initialize the cart handler
	 *
	 * @since 1.2
	 */
	public function __construct() {

		add_filter( 'woocommerce_add_to_cart_validation',     array( $this, 'add_to_cart_validation'), 10, 6 );
		add_filter( 'woocommerce_add_cart_item_data',         array( $this, 'add_cart_item_voucher_data'), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session'), 10, 2 );
		add_filter( 'woocommerce_get_item_data',              array( $this, 'display_voucher_data_in_cart'), 10, 2 );
		add_action( 'woocommerce_add_order_item_meta',        array( $this, 'add_order_item_meta'), 10, 2 );
	}


	/**
	 * Filter to check whether a product is valid to be added to the cart.
	 * This is used to ensure any required user input fields are supplied
	 *
	 * @since 1.2
	 * @param boolean $valid whether the product as added is valid
	 * @param int $product_id the product identifier
	 * @param int $quantity the amount being added
	 * @param int $variation_id optional variation id
	 * @param array $variations optional variation configuration
	 * @param array $cart_item_data optional cart item data.  This will only be
	 *        supplied when an order is being-ordered, in which case the
	 *        required fields will not be available from the REQUEST array
	 * @return true if the product is valid to add to the cart
	 */
	public function add_to_cart_validation( $valid, $product_id, $quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {

		$_product_id = $variation_id ? $variation_id : $product_id;
		$product = wc_get_product( $_product_id );

		// is this a voucher product?
		if ( WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {

			$voucher = WC_PDF_Product_Vouchers_Product::get_voucher( $product );

			// set any user-input fields, which will end up in the order item meta data (which can be displayed on the frontend)
			$fields = $voucher->get_user_input_voucher_fields();

			foreach ( $fields as $field ) {

				if ( $voucher->user_input_field_is_required( $field['name'] ) ) {

					if ( ! isset( $_POST[ $field['name'] ][ $_product_id ] ) || ! $_POST[ $field['name'] ][ $_product_id ] ) {
						wc_add_notice( sprintf( __( "Field '%s' is required.", WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $field['label'] ), 'error' );
						$valid = false;
					}
				}
			}
		}

		return $valid;
	}


	/**
	 * Display any user-input voucher data in the cart
	 *
	 * @since 1.2
	 * @param array $data array of name/display pairs of data to display in the cart
	 * @param array $item associative array of a cart item (product)
	 *
	 * @return array of name/display pairs of data to display in the cart
	 */
	public function display_voucher_data_in_cart( $data, $item ) {

		if ( isset( $item['voucher_item_meta_data'] ) && isset( $item['voucher_id'] ) ) {
			// voucher data to display

			$voucher = new WC_Voucher( $item['voucher_id'] );

			foreach ( $item['voucher_item_meta_data'] as $name => $value ) {
				if ( $voucher->is_user_input_type_field( $name ) && $value ) {
					$data[] = array(
						'name'    => __( $voucher->get_field_label( $name ), WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'display' => stripslashes( $value ),
						'hidden'  => false,
					);
				}
			}
		}

		return $data;
	}


	/**
	 * Persist our cart item voucher data to the session, if any
	 *
	 * @since 1.2
	 * @param array $cart_item associative array of data representing a cart item (product)
	 * @param array $values associative array of data for the cart item, currently in the session
	 *
	 * @return associative array of data representing a cart item (product)
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {

		if ( isset( $values['voucher_item_meta_data'] ) ) {
			$cart_item['voucher_item_meta_data'] = $values['voucher_item_meta_data'];
		}
		if ( isset( $values['voucher_image_id'] ) ) {
			$cart_item['voucher_image_id'] = $values['voucher_image_id'];
		}
		if ( isset( $values['voucher_id'] ) ) {
			$cart_item['voucher_id'] = $values['voucher_id'];
		}
		if ( isset( $values['voucher_random'] ) ) {
			$cart_item['voucher_random'] = $values['voucher_random'];
		}

		return $cart_item;
	}


	/**
	 * Add any user-supplied voucher field data to the cart item data, to
	 * set in the session
	 *
	 * @since 1.2
	 * @param array $cart_item_data associative-array of name/value pairs of cart item data
	 * @param int $product_id the product identifier
	 * @param int $variation_id optional product variation identifer
	 *
	 * @return array associative array of name/value pairs of cart item
	 *         data to set in the session
	 */
	public function add_cart_item_voucher_data( $cart_item_data, $product_id, $variation_id ) {

		$_product_id = $variation_id ? $variation_id : $product_id;
		$product = wc_get_product( $_product_id );

		// is this a voucher product?
		if ( WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {

			$voucher = WC_PDF_Product_Vouchers_Product::get_voucher( $product );

			// record the voucher id
			$cart_item_data['voucher_id'] = $voucher->id;

			// set the selected voucher image id, or default to the main one if the voucher was added from the catalog
			$cart_item_data['voucher_image_id'] = isset( $_POST['voucher_image'][ $_product_id ] ) ? $_POST['voucher_image'][ $_product_id ] : $voucher->image_id;

			// set any user-input fields, which will end up in the order item meta data (which can be displayed on the frontend)
			$fields = $voucher->get_user_input_voucher_fields();

			foreach ( $fields as $field ) {
				if ( isset( $_POST[ $field['name'] ][ $_product_id ] ) ) {
					$cart_item_data['voucher_item_meta_data'][ $field['name'] ] = $_POST[ $field['name'] ][ $_product_id ];
				}
			}

			// add a random so that multiple of the same product can be added to the cart when "sold individually" is enabled
			$cart_item_data['voucher_random'] = uniqid( 'voucher_' );
		}

		return $cart_item_data;
	}


	/**
	 * Make sure the user-input voucher fields are persisted to the database in the
	 * order item meta.  This is called during the checkout process for each
	 * cart item added to the order.  Requires WooCommerce 2.0+
	 *
	 * @since 1.2
	 * @param int $item_id item identifier
	 * @param array $values array of data representing a cart item
	 */
	public function add_order_item_meta( $item_id, $values ) {

		// is this a voucher product?
		if ( isset( $values['voucher_id'] ) ) {

			$voucher = new WC_Voucher( $values['voucher_id'] );

			wc_add_order_item_meta( $item_id, '_voucher_image_id', $values['voucher_image_id'] );
			wc_add_order_item_meta( $item_id, '_voucher_id',       $values['voucher_id'] );
			wc_add_order_item_meta( $item_id, '_voucher_redeem',   array_pad( array(), $values['quantity'], null ) );
			wc_add_order_item_meta( $item_id, '_voucher_number',   WC_PDF_Product_Vouchers_Voucher::generate_voucher_number() );

			// set any user-input fields to the order item meta data (which can be displayed on the frontend)
			// ie recipient_name, message
			if ( isset( $values['voucher_item_meta_data'] ) ) {
				foreach ( $values['voucher_item_meta_data'] as $name => $value ) {
					if ( $voucher->is_user_input_type_field( $name ) && $value ) {

						// make sure any max length rules are imposed
						if ( $voucher->get_user_input_field_max_length( $name ) ) {
							$value = substr( $value, 0, $voucher->get_user_input_field_max_length( $name ) );
						}

						wc_add_order_item_meta( $item_id, __( $voucher->get_field_label( $name ), WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $value );
					}
				}
			}
		}
	}


}
