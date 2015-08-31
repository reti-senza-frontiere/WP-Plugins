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
 * @package   WC-PDF-Product-Vouchers/Templates
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * Template functions
 *
 * @since 1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


if ( ! function_exists( 'wc_pdf_product_vouchers_render_product_voucher_fields' ) ) {

	/**
	 * Pluggable function to render the frontend product page voucher fields
	 *
	 * @since 1.2
	 * @param WC_Product $product the voucher product
	 */
	function wc_pdf_product_vouchers_render_product_voucher_fields( $product ) {

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_product_id ) {
				$products[] = wc_get_product( $variation_product_id );
			}
		} else {
			$products[] = $product;
		}

		foreach ( $products as $product ) {

			$voucher = WC_PDF_Product_Vouchers_Product::get_voucher( $product );

			if ( $voucher ) {
				$fields = $voucher->get_user_input_voucher_fields();
				$images = $voucher->get_image_urls();

				if ( $fields || $images ) {
					// load the template file
					wc_get_template(
						'single-product/product-voucher.php',
						array(
							'product'    => $product,
							'product_id' => isset( $product->variation_id ) ? $product->variation_id : $product->id,
							'voucher'    => $voucher,
							'fields'     => $fields,
							'images'     => $images,
						),
						'',
						wc_pdf_product_vouchers()->get_plugin_path() . '/templates/'
					);
				}
			}
		}
	}

}


if ( ! function_exists( 'wc_vouchers_locate_voucher_preview_template' ) ) {

	/**
	 * Locate the voucher preview template file, in this plugin's templates directory
	 *
	 * @since 1.0
	 * @param string $locate locate path
	 *
	 * @return string the location path for the voucher preview file
	 */
	function wc_vouchers_locate_voucher_preview_template( $locate ) {

		$post_type = get_query_var( 'post_type' );
		$preview   = get_query_var( 'preview' );

		if ( 'wc_voucher' == $post_type && 'true' == $preview ) {
			$locate = wc_pdf_product_vouchers()->get_plugin_path() . '/templates/single-wc_voucher.php';
		}

		return $locate;
	}

}
