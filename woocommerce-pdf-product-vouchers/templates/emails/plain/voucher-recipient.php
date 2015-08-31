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
 * @package   WC-PDF-Product-Vouchers/Emails
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

/**
 * Voucher recipient plain email
 *
 * @param WC_Order $order the order object associated with this email
 * @param string $email_heading the configurable email heading
 * @param int $voucher_count the number of vouchers being attached
 * @param string $message optional customer-supplied message to display
 * @param string $recipient_name optional customer-supplied recipient name
 *
 * @version 1.2
 * @since 1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

printf( _n( "Hi there. You've been sent a voucher!", "Hi there. You've been sent %d vouchers!", $voucher_count, WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $voucher_count );

if ( $message ) {
	echo '&ldquo;' . esc_html( $message ) . '&rdquo;';
}

echo _n( "You can find your voucher attached to this email", "You can find your vouchers attached to this email", $voucher_count, WC_PDF_Product_Vouchers::TEXT_DOMAIN );

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
