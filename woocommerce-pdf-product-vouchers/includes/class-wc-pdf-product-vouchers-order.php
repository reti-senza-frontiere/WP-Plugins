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
 * PDF Product Vouchers Order handler/helper class
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Order {


	/**
	 * Returns the voucher identified by $voucher_number if it belongs to an
	 * item on the given order
	 *
	 * @since 1.2
	 * @param WC_Order $order the order object
	 * @param int $voucher_number the unique voucher number
	 */
	public static function get_voucher_by_voucher_number( $order, $voucher_number ) {

		$order_items = $order->get_items();

		if ( count( $order_items ) > 0 ) {

			foreach ( $order_items as $order_item_id => $item ) {

				if ( $item['product_id'] > 0 && isset( $item['voucher_id'] ) && $item['voucher_id'] && isset( $item['voucher_number'] ) && $item['voucher_number'] == $voucher_number ) {
					return new WC_Voucher( $item['voucher_id'], $order->id, $item, $order_item_id );
				}
			}
		}

		return null;
	}


	/**
	 * Returns any vouchers attached to this order
	 *
	 * @since 1.2
	 * @param WC_Order $order the order object
	 * @return array of WC_Voucher objects
	 */
	public static function get_vouchers( $order ) {

		$vouchers = array();

		$order_items = $order ? $order->get_items() : array();

		if ( count( $order_items ) > 0 ) {

			foreach ( $order_items as $order_item_id => $item ) {

				if ( $item['product_id'] > 0 && isset( $item['voucher_id'] ) && $item['voucher_id'] ) {
					$vouchers[] = new WC_Voucher( $item['voucher_id'], $order->id, $item, $order_item_id );
				}
			}
		}

		return $vouchers;
	}


	/**
	 * Returns true if $order has been marked as fully redeemed
	 *
	 * @since 1.2
	 * @param WC_Order $order the order object
	 * @return boolean true if the order is marked as redeemed
	 */
	public static function vouchers_redeemed( $order ) {
		return isset( $order->voucher_redeemed[0] ) && $order->voucher_redeemed[0];
	}


	/**
	 * Marks $order as having all vouchers redeemed
	 *
	 * @since 1.2
	 * @param WC_Order $order the order object
	 * @param int $voucher_count the number of redeemed vouchers
	 */
	public static function mark_vouchers_redeemed( $order, $voucher_count = 1 ) {

		$order->add_order_note( _n( 'Voucher redeemed.', 'All vouchers redeemed.', $voucher_count, WC_PDF_Product_Vouchers::TEXT_DOMAIN ) );
		update_post_meta( $order->id, '_voucher_redeemed', 1 );

		do_action( 'wc_pdf_product_vouchers_order_redeemed', $order );
	}

}
