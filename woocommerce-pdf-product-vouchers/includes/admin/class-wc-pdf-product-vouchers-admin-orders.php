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
 * PDF Product Vouchers Orders Admin
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Admin_Orders {


	/**
	 * Initialize the voucher orders admin
	 *
	 * @since 1.2
	 */
	public function __construct() {

		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'add_resend_voucher_recipient_email' ) );

		// complete the order once all vouchers have been redeemed
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'maybe_complete_order' ), 15, 2 );

		// hide voucher order item meta fields in the Edit Orders admin
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_itemmeta' ) );

		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'process_shop_order_meta' ), 15, 2 );

		add_filter( 'woocommerce_admin_download_permissions_title', array( $this, 'download_permissions_title' ), 10, 5 );

		add_action( 'add_meta_boxes', array( $this, 'add_vouchers_meta_box' ) );

	}


	/**
	 * Add the "Voucher Recipient" Admin edit Order Actions dropdown
	 *
	 * @since 1.2
	 * @param array $available_emails available action email ids
	 * @return array available action email ids
	 */
	public function add_resend_voucher_recipient_email( $available_emails ) {

		$voucher_recipient = false;

		// order contains any recipient emails addresses?
		$order = isset( $_GET['post'] ) ? wc_get_order( $_GET['post'] ) : null;

		foreach ( WC_PDF_Product_Vouchers_Order::get_vouchers( $order ) as $voucher ) {

			if ( $voucher->get_recipient_email() ) {
				$voucher_recipient = true;
				break;
			}

		}

		// add the action if there was a voucher recipient for the order
		if ( $voucher_recipient ) {
			$available_emails[] = 'wc_pdf_product_vouchers_voucher_recipient';
		}

		return $available_emails;
	}


	/**
	 * Mark the entire order as being redeemed if it contains all redeemed vouchers.
	 * Also, generate any voucher pdfs for items newly added from the admin
	 *
	 * @since 1.2
	 * @param int $post_id the order id
	 * @param object $post the order
	 */
	public function maybe_complete_order( $post_id, $post ) {

		// generate any vouchers as needed
		wc_pdf_product_vouchers()->get_voucher_handler()->generate_voucher_pdf( $post_id );

		$order = wc_get_order( $post_id );
		$voucher_count = 0;

		// if the order status is not completed, and the entire order has not already been marked as 'voucher redeemed'
		if ( ! WC_PDF_Product_Vouchers_Order::vouchers_redeemed( $order ) ) {

			foreach ( WC_PDF_Product_Vouchers_Order::get_vouchers( $order ) as $voucher ) {

				$voucher_count++;

				// an unredeemed voucher, bail
				if ( ! $voucher->is_redeemed() ) {
					return;
				}
			}

			if ( $voucher_count ) {
				// if we made it here, it means this order contains only voucher items, and they are all redeemed
				WC_PDF_Product_Vouchers_Order::mark_vouchers_redeemed( $order, $voucher_count );
			}
		}
	}


	/**
	 * Hide voucher core meta data fields from the order admin
	 *
	 * @since 1.2
	 * @param array $hidden_fields array of item meta data field names to hide from
	 *        the order admin
	 * @return array of item meta data field names to hide from the order admin
	 */
	public function hidden_order_itemmeta( $hidden_fields ) {
		return array_merge( $hidden_fields, array( '_voucher_id', '_voucher_image_id', '_voucher_number', '_voucher_redeem', '_voucher_expiration' ) );
	}


	/**
	 * Called when an order is updated from the admin, creates a new voucher if
	 * a voucher item was added, and updates the voucher expiration and redeem
	 * item meta.
	 *
	 * @since 1.2
	 * @param int $post_id the post identifier
	 * @param object $post the order post object
	 *
	 * @return array order item data to persist
	 */
	public function process_shop_order_meta( $post_id, $post ) {

		// get the order
		$order = wc_get_order( $post_id );
		$order_items = $order->get_items();

		// loop through any order items by id
		if ( isset( $_POST['order_item_id'] ) ) {

			foreach ( $_POST['order_item_id'] as $item_id ) {

				$item_id = absint( $item_id );

				if ( ! isset( $order_items[ $item_id ] ) || ! $order_items[ $item_id ] ) continue;

				$order_item = $order_items[ $item_id ];

				$product_id = $order_item['variation_id'] ? $order_item['variation_id'] : $order_item['product_id'];
				$product    = wc_get_product( $product_id );

				// if we have a voucher product, but no voucher set for the order item, this is likely an item newly added from the admin, so create a default voucher
				if ( $product && $product->is_downloadable() && WC_PDF_Product_Vouchers_Product::has_voucher( $product ) && ( ! isset( $order_item['voucher_id'] ) || ! $order_item['voucher_id'] ) ) {

					$voucher = WC_PDF_Product_Vouchers_Product::get_voucher( $product );

					$voucher_number = WC_PDF_Product_Vouchers_Voucher::generate_voucher_number();

					wc_add_order_item_meta( $item_id, '_voucher_image_id', $voucher->get_image_id() );
					wc_add_order_item_meta( $item_id, '_voucher_id',       $voucher->id );
					wc_add_order_item_meta( $item_id, '_voucher_redeem',   array_pad( array(), $order_item['qty'], null ) );  // TODO: need to handle the order item quantity being changed from the admin
					wc_add_order_item_meta( $item_id, '_voucher_number',   $voucher_number );

					// if download permissions have already been granted, grant permission to the newly created voucher
					if ( isset( $order->download_permissions_granted[0] ) && 1 == $order->download_permissions_granted[0] ) {
						wc_downloadable_file_permission( 'wc_vouchers_' . $voucher_number, $product_id, $order );
					}
				}

				if ( isset( $_POST['voucher_expiration'][ $item_id ] ) ) {
					wc_update_order_item_meta( $item_id, '_voucher_expiration', $_POST['voucher_expiration'][ $item_id ] );
				}

				if ( isset( $_POST['voucher_redeem'][ $item_id ] ) ) {
					wc_update_order_item_meta( $item_id, '_voucher_redeem', $_POST['voucher_redeem'][ $item_id ] );
				}
			}

		}
	}


	/**
	 * Add the voucher number (if any) onto the downloadable product title
	 * shown in the Downloadable Product Permissions box of the edit order
	 * page
	 *
	 * @since 1.2
	 * @param string $title the downloadable product title
	 * @param int $product_id the product identifier
	 * @param int $order_id the order identifier
	 * @param string $order_key the order key
	 * @param string $download_id the download id
	 *
	 * @return string the download permissions title
	 */
	public function download_permissions_title( $title, $product_id, $order_id, $order_key, $download_id ) {

		$order = wc_get_order( $order_id );

		$voucher = WC_PDF_Product_Vouchers_Order::get_voucher_by_voucher_number( $order, str_replace( 'wc_vouchers_', '', $download_id ) );

		if ( $voucher ) {
			$title .= ' (' . sprintf( __( 'Voucher %s', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $voucher->get_voucher_number() ) . ')';
		}

		return $title;
	}


	/**
	 * Adds the admin Edit Order Vouchers meta box
	 *
	 * @since 1.2
	 */
	public function add_vouchers_meta_box() {

		// Admin Edit Order Voucher Meta Box
		add_meta_box(
			'woocommerce-order-vouchers',
			__( 'Vouchers', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			array( $this, 'vouchers_meta_box' ),
			'shop_order',
			'normal',
			'default'
		);
	}


	/**
	 * Order vouchers meta box
	 *
	 * Displays the order vouchers meta box - for showing and modifying
	 * individual vouchers attached to the order
	 *
	 * @since 1.2
	 */
	public function vouchers_meta_box( $post ) {

		$order = wc_get_order( $post->ID );
		$order_items = $order->get_items();

		?>
		<div class="woocommerce_order_vouchers_wrapper">
			<table cellpadding="0" cellspacing="0" class="woocommerce_order_vouchers">
				<thead>
					<tr>
						<th class="thumb" width="1%"><?php _e( 'Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="voucher_number"><?php _e( 'Number', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="sku"><?php _e( 'SKU', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="data"><?php _e( 'Data', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="expires"><?php _e( 'Expires', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="qty"><?php _e( 'Qty', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></th>
						<th class="redeem" style="white-space:nowrap;"><?php _e( 'Mark Redeemed', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Mark the dates that any vouchers are redeemed.  Marking all vouchers redeemed will complete the order.', WC_PDF_Product_Vouchers::TEXT_DOMAIN); ?>" href="#">[?]</a></th>
					</tr>
				</thead>
				<tbody id="order_vouchers_list">

					<?php if ( count( $order_items ) > 0 ) foreach ( $order_items as $item_id => $item ) :

						// only voucher items
						if ( ! isset( $item['voucher_id'] ) ) continue;
						$item['voucher_redeem'] = maybe_unserialize( $item['voucher_redeem'] );

						$voucher = new WC_Voucher( $item['voucher_id'], $post->ID, $item, $item_id );

						if ( isset( $item['variation_id'] ) && $item['variation_id'] > 0 ) :
							$_product = wc_get_product( $item['variation_id'] );
						else :
							$_product = wc_get_product( $item['product_id'] );
						endif;

						// get any user-supplied voucher data (this includes product variation data and user-entered fields like recipient or message)
						$voucher_data = array();
						if ( isset( $_product->variation_data ) ) $voucher_data = $_product->variation_data;
						$voucher_data = array_merge( $voucher_data, $voucher->get_user_input_data() );

						?>
						<tr class="item" rel="<?php echo $item_id; ?>">
							<td class="thumb">
								<a href="<?php echo esc_url( admin_url( 'post.php?post='. $voucher->id . '&action=edit' ) ); ?>" class="tips" data-tip="<?php
									echo '<strong>' . __( 'Voucher ID:', WC_PDF_Product_Vouchers::TEXT_DOMAIN ).'</strong> ' . $voucher->id;
								?>"><?php echo $voucher->get_image(); ?></a>
								<?php if ( $voucher->file_exists( WC_PDF_Product_Vouchers::get_uploads_path() ) ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'download', 'product_id' => $item['product_id'], 'item_id' => $item_id, 'voucher_id' => $item['voucher_id'] ) ) ); ?>"><?php esc_html_e( 'Download', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></a>
								<?php endif; ?>
							</td>
							<td class="voucher_number" width="1%">
								<?php echo $voucher->get_voucher_number(); ?>
							</td>
							<td class="sku" width="1%">
								<?php if ( $_product->sku ) echo $_product->sku; else echo '-'; ?>
							</td>
							<td class="data">

								<?php echo $item['name']; ?>
								<?php
									if ( ! empty( $voucher_data ) ) echo '<br/>' . wc_get_formatted_variation( $voucher_data, true );
								?>
							</td>

							<td class="expires" style="width:auto;">
								<input type="text" name="voucher_expiration[<?php echo $item_id; ?>]" id="voucher_expiration_<?php echo $item_id; ?>" maxlength="10" value="<?php echo $voucher->expiration_date ? date( "Y-m-d", $voucher->expiration_date ) : ''; ?>" class="date-picker-field" />
							</td>

							<td class="qty" width="1%">
								<?php echo $item['qty']; ?>
							</td>

							<td class="redeem" width="1%">
								<?php
								foreach ( $item['voucher_redeem'] as $i => $redeem ) {
									?>
									<input type="text" name="voucher_redeem[<?php echo $item_id; ?>][<?php echo $i; ?>]" id="voucher_redeem_<?php echo $item_id . '_' . $i; ?>" class="voucher_redeem date-picker-field" maxlength="10" style="width:85px;" value="<?php echo $redeem; ?>" class="date-picker-field" />
									<?php
								}
								?>
							</td>

						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="buttons buttons-alt">
			<button type="button" class="button redeem_all_vouchers"><?php _e( 'Redeem All &uarr;', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></button>
		</p>
		<div class="clear"></div>
		<?php

	}


}
