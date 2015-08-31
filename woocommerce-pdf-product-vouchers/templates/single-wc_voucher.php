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
 * The template for displaying voucher previews.  This isn't a page template in
 * the regular sense, instead it streams the voucher PDF to the client.  The
 * voucher is created with placeholder field data.  The voucher primary image
 * at least must be set.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;

$voucher = new WC_Voucher( $post->ID );

$lorem_ipsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nunc eu risus et sapien imperdiet ornare. Mauris eget libero at lorem tempor faucibus. Donec convallis auctor nibh, at laoreet ante iaculis eget. Sed scelerisque, dolor non porttitor ultrices, nisi risus blandit tortor, vel commodo est turpis eu lorem. Aliquam at orci lectus. Aenean tincidunt neque id nunc volutpat tincidunt. Integer mattis, lectus non aliquet dictum, nisl purus facilisis justo, quis malesuada magna orci venenatis diam. Nulla sem erat, pretium ultricies vestibulum posuere, semper eget elit. Maecenas lobortis bibendum odio, nec aliquet dolor dignissim ac. Fusce dapibus pharetra mauris sed placerat. Aliquam vitae est dolor. Vestibulum fermentum libero felis, non rhoncus orci. Maecenas fringilla, felis eget sodales ultricies, quam tortor consectetur est, vitae rhoncus lacus est cursus neque.";

if ( $voucher->get_image_id() ) {
	// if there is at least a voucher image set, set default values for all positioned fields
	//  ie, the field 'product_name' will have the value 'Product Name' set
	foreach ( $voucher->voucher_fields as $field_name => $field ) {

		if ( isset( $field['position'] ) && $field['position'] ) {

			$value_set = false;
			$value = ucwords( str_replace( '_', ' ', $field_name ) );

			if ( isset( $field['max_length'] ) && $field['max_length'] ) {
				while ( strlen( $value ) < $field['max_length'] ) {
					$value .= " " . substr( $lorem_ipsum, 0, $field['max_length'] - strlen( $value ) + 1 );
				}
			}

			if ( isset( $field['days_to_expiry'] ) && $field['days_to_expiry'] ) {
				// if there's an expiration date set provie some dummy data
				$voucher->set_expiration_date( strtotime( "+{$field['days_to_expiry']} day" ) );
				$value_set = true;
			}

			if ( ! $value_set )
				$voucher->$field_name = $value;
		}
	}

	// stream the example voucher pdf
	$voucher->generate_pdf();
	exit;

} else {
	wp_die( __( 'You must set a voucher primary image before you can preview', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) );
}
