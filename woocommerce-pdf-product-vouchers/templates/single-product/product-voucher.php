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
 * The frontend product page voucher fields
 *
 * @param array $fields array of user input voucher fields
 * @param array $images array of available voucher images
 *
 * @version 1.2
 * @since 1.2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<div class="voucher-fields-wrapper<?php echo $product->is_type( 'variation' ) ? '-variation' : ''; ?>" id="voucher-fields-wrapper-<?php echo $product_id; ?>">
<input type="hidden" name="voucher_id[<?php echo $product_id; ?>]" value="<?php echo $voucher->id; ?>" />
<table class="voucher-fields" cellspacing="0">
	<tbody>
		<?php foreach ( $fields as $field ) : ?>
			<tr>
				<td class="label"><label for="<?php echo sanitize_title( $field['name'] ); ?>"><?php echo __( $field['label'], WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></label></td>
				<td class="value">
				<?php if ( 'text' == $field['input_type'] ) : ?>
						<input type="text" name="<?php echo sanitize_title( $field['name'] ); ?>[<?php echo $product_id; ?>]" id="<?php echo sanitize_title( $field['name'] ) . '-' . $product_id; ?>" <?php echo isset( $field['max_length'] ) && $field['max_length'] ? 'maxlength="' . $field['max_length'] . '"' : ''; ?> value="<?php echo esc_attr( isset( $_POST[ $field['name'] ][ $product_id ] ) ? $_POST[ $field['name'] ][ $product_id ] : '' ); ?>" />
					<?php elseif ( 'textarea' == $field['input_type'] ) : ?>
						<textarea name="<?php echo sanitize_title( $field['name'] ); ?>[<?php echo $product_id; ?>]" id="<?php echo sanitize_title( $field['name'] ) . '-' . $product_id; ?>" <?php echo isset( $field['max_length'] ) && $field['max_length'] ? 'maxlength="' . $field['max_length'] . '"' : ''; ?>><?php echo esc_html( isset( $_POST[ $field['name'] ][ $product_id ] ) ? $_POST[ $field['name'] ][ $product_id ] : '' ); ?></textarea>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach;?>
		<tr><td colspan="2">
		<?php $i = 0; foreach ( $images as $image_id => $image ) : $i++;
			if ( count( $images > 1 ) ) $title = sprintf( esc_attr__( 'Voucher Option %d', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $i );
			else $title = __( 'Voucher Image', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
			?>
			<div class="voucher-image-option">
				<a href="<?php echo $image['image']; ?>" title="<?php echo $title; ?>" rel="prettyPhoto[voucher-<?php echo $product_id; ?>]" data-rel="prettyPhoto[voucher-<?php echo $product_id; ?>]"><img src="<?php echo $image['thumb']; ?>" title="<?php echo $title; ?>" alt="<?php echo $title; ?>" /></a>
				<?php if ( count( $images ) > 1 ) : ?>
					<input type="radio" name="voucher_image[<?php echo $product_id; ?>]" value="<?php echo $image_id; ?>" <?php checked( 1, $i ); ?> id="voucher-image-<?php echo $i ?>" />
				<?php else : ?>
					<input type="hidden" name="voucher_image[<?php echo $product_id; ?>]" value="<?php echo $image_id; ?>" />
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		</td></tr>
	</tbody>
</table>
</div>
