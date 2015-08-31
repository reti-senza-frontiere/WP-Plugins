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
 * The PDF Product Vouchers My Account handler
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_My_Account {


	/**
	 * My Account constructor
	 *
	 * @since 1.2
	 */
	public function __construct() {

		// frontend: display voucher options on product page, and purchased vouchers in the My Account page
		add_filter( 'woocommerce_customer_get_downloadable_products', array( $this, 'customer_get_downloadable_products' ) );
	}


	/**
	 * Called from the customer account page, this adds the date to the
	 * voucher links so they are a little bit easier to distinguish from one
	 * another.  Also filter out links for voucher files that don't exist for
	 * whatever reason.
	 *
	 * @since 1.2
	 * @param array $downloads available downloads
	 *
	 * @return array available downloads
	 */
	public function customer_get_downloadable_products( $downloads ) {

		$new_downloads = array();

		foreach ( $downloads as $download ) {

			$product = wc_get_product( $download['product_id'] );

			// is the product a voucher product?
			if ( false !== strpos( $download['download_id'], 'wc_vouchers_' ) && WC_PDF_Product_Vouchers_Product::has_voucher( $product ) ) {

				$order = wc_get_order( $download['order_id'] );

				// download id looks like "wc_vouchers_{voucher number}"
				$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download['download_id'] ) );

				if ( $voucher && $voucher->file_exists( WC_PDF_Product_Vouchers::get_uploads_path() ) ) {

					$download['download_name'] .= ' (' . sprintf( _x( 'Voucher %s %s', 'Voucher number and date', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $voucher->get_voucher_number(), date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ) ) . ')';

					$new_downloads[] = $download;
				}

			} else {
				// regular file
				$new_downloads[] = $download;
			}
		}

		return $new_downloads;
	}


}
