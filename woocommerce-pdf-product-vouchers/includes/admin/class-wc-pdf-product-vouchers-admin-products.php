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
 * @package   WC-PDF-Product-Vouchers/Admin
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PDF Product Vouchers Products Admin
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Admin_Products {


	/**
	 * Initialize the voucher products admin
	 *
	 * @since 1.2
	 */
	public function __construct() {

		// assign voucher to downloadable product
		add_action( 'woocommerce_process_product_meta', array( $this, 'process_product_meta' ), 15, 2 );

		// assign voucher to downloadable variable product
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'process_product_meta_variable' ), 15 );

		// voucher select input in simple product data meta box
		add_action( 'woocommerce_product_options_downloads', array( $this, 'product_options_downloads' ) );

		// voucher select input in variable product data meta box
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'product_after_variable_attributes' ), 10, 3 );
	}


	/**
	 * Assign voucher to downloadable simple product, from the Admin Product
	 * edit page.
	 *
	 * @since 1.2
	 * @param int $post_id the product id
	 * @param object $post the product
	 */
	public function process_product_meta( $post_id, $post ) {

		$is_downloadable = isset( $_POST['_downloadable'] ) ? true : false;

		// set or remove the voucher id
		if ( $is_downloadable && isset( $_POST['_voucher_id'] ) && $_POST['_voucher_id'] ) {
			update_post_meta( $post_id, '_voucher_id', $_POST['_voucher_id'] );
		} else {
			update_post_meta( $post_id, '_voucher_id', '' );
		}
	}


	/**
	 * Assign voucher to downloadable product variation
	 *
	 * @since 1.2
	 * @param int $post_id the product id
	 */
	public function process_product_meta_variable( $post_id ) {

		if ( isset( $_POST['variable_sku'] ) ) {
			$variable_post_id    = $_POST['variable_post_id'];
			$variable_voucher_id = isset( $_POST['variable_voucher_id'] ) && $_POST['variable_voucher_id'] ? $_POST['variable_voucher_id'] : array();

			if ( isset( $_POST['variable_is_downloadable'] ) )
				$variable_is_downloadable = $_POST['variable_is_downloadable'];

			$max_loop = max( array_keys( $_POST['variable_post_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i++ ) {

				if ( ! isset( $variable_post_id[ $i ] ) ) continue;

				$variation_id = (int) $variable_post_id[ $i ];

				// Virtual/Downloadable
				if ( isset( $variable_is_downloadable[ $i ] ) ) $is_downloadable = true; else $is_downloadable = false;

				// set or remove the voucher id
				if ( $is_downloadable && isset( $variable_voucher_id[ $i ] ) && $variable_voucher_id[ $i ] ) {
					update_post_meta( $variation_id, '_voucher_id', $variable_voucher_id[ $i ] );
				} else {
					update_post_meta( $variation_id, '_voucher_id', '' );
				}
			}
		}
	}


	/**
	 * Display the voucher select box in the Product Data meta box on the
	 * product edit page for the Simple Product type
	 *
	 * @since 1.2
	 */
	public function product_options_downloads() {

		$options = array( '' => '' );

		// get all the published vouchers
		foreach ( wc_pdf_product_vouchers()->get_voucher_handler()->get_vouchers() as $voucher ) {
			$options[ $voucher->ID ] = $voucher->post_title;
		}

		woocommerce_wp_select( array( 'id' => '_voucher_id', 'label' => __( 'Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), 'options' => $options, 'desc_tip' => true, 'description' => __( 'Select a voucher template rather than supplying a File Path to make this into a voucher product.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) )  );

	}


	/**
	 * Display the voucher select box in the product variation meta box for
	 * downloadable variable products
	 *
	 * @since 1.2
	 * @param int $loop loop counter
	 * @param array $variation_data associative array of variation data
	 */
	public function product_after_variable_attributes( $loop, $variation_data, $variation ) {

		// WooCommerce 2.3 removed meta data from the $variation_data array, let's add it back
		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ) {
			$variation_data = array_merge( get_post_meta( $variation->ID ), $variation_data );
		}

		$options = array( '' => '' );

		// get all the published vouchers
		foreach ( wc_pdf_product_vouchers()->get_voucher_handler()->get_vouchers() as $voucher ) {
			$options[ $voucher->ID ] = $voucher->post_title;
		}

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte_2_3() ): ?>
			<div class="show_if_variation_downloadable" style="display:none;">
				<p class="form-row form-row-first">
					<label><?php _e( 'Voucher:', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Select a voucher rather than providing a file path', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?>" href="#">[?]</a></label><select class="variable_voucher" name="variable_voucher_id[<?php echo $loop; ?>]"><?php
					foreach ( $options as $voucher_id => $name ) {
						echo '<option value="' . $voucher_id . '" ';
						if ( isset( $variation_data['_voucher_id'][0] ) ) selected( $voucher_id, $variation_data['_voucher_id'][0] );
						echo '>' . $name . '</option>';
					}
				?></select>
				</p>
			</div>
		<?php else: ?>
			<tr class="show_if_variation_downloadable" style="display:none;">
				<td>
					<label><?php _e( 'Voucher:', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?> <a class="tips" data-tip="<?php _e( 'Select a voucher rather than providing a file path', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?>" href="#">[?]</a></label><select class="variable_voucher" name="variable_voucher_id[<?php echo $loop; ?>]"><?php
					foreach ( $options as $voucher_id => $name ) {
						echo '<option value="' . $voucher_id . '" ';
						if ( isset( $variation_data['_voucher_id'][0] ) ) selected( $voucher_id, $variation_data['_voucher_id'][0] );
						echo '>' . $name . '</option>';
					}
				?></select>
				</td>
				<td>&nbsp;</td>
			</tr>
		<?php endif;
	}


}
