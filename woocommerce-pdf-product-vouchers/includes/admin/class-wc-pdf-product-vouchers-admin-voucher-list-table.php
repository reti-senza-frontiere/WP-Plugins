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
 * PDF Product Vouchers Voucher List Table Admin
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Admin_Voucher_List_Table {


	/**
	 * Initialize the voucher admin
	 *
	 * @since 1.2
	 * @param WC_PDF_Product_Vouchers $plugin the parent plugin
	 */
	public function __construct() {

		add_filter( 'bulk_actions-edit-wc_voucher', array( $this, 'edit_voucher_bulk_actions' ) );

		add_filter( 'views_edit-wc_voucher', array( $this, 'edit_voucher_views' ) );

		add_filter( 'manage_edit-wc_voucher_columns', array( $this, 'edit_voucher_columns' ) );

		add_action( 'manage_wc_voucher_posts_custom_column', array( $this, 'custom_voucher_columns' ), 2 );
	}


	/**
	 * Remove the bulk edit action for vouchers, it really isn't useful
	 *
	 * @since 1.2
	 * @param array $actions associative array of action identifier to name
	 *
	 * @return array associative array of action identifier to name
	 */
	public function edit_voucher_bulk_actions( $actions ) {

		unset( $actions['edit'] );

		return $actions;
	}


	/**
	 * Modify the 'views' links, ie All (3) | Publish (1) | Draft (1) | Private (2) | Trash (3)
	 * shown above the vouchers list table, to hide the publish/private states,
	 * which are not important and confusing for voucher objects.
	 *
	 * @since 1.2
	 * @param array $views associative-array of view state name to link
	 *
	 * @return array associative array of view state name to link
	 */
	public function edit_voucher_views( $views ) {

		// publish and private are not important distinctions for vouchers
		unset( $views['publish'], $views['private'] );

		return $views;
	}


	/**
	 * Columns for Vouchers page
	 *
	 * @since 1.2
	 * @param array $columns associative-array of column identifier to header names
	 *
	 * @return array associative-array of column identifier to header names for the vouchers page
	 */
	public function edit_voucher_columns( $columns ){

		$columns = array();

		$columns['cb']             = '<input type="checkbox" />';
		$columns['thumb']          = __( 'Image', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		$columns['name']           = __( 'Name', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		$columns['days_to_expiry'] = __( 'Expiry Days', WC_PDF_Product_Vouchers::TEXT_DOMAIN );

		return $columns;
	}


	/**
	 * Custom Column values for Vouchers page
	 *
	 * @since 1.2
	 * @param string $column column identifier
	 */
	public function custom_voucher_columns( $column ) {

		global $post;

		$voucher = new WC_Voucher( $post->ID );

		switch ( $column ) {

			case 'thumb':
				$edit_link = get_edit_post_link( $post->ID );
				echo '<a href="' . $edit_link . '">' . $voucher->get_image() . '</a>';
			break;

			case 'name':
				$edit_link = get_edit_post_link( $post->ID );
				$title = _draft_or_post_title();

				$post_type_object = get_post_type_object( $post->post_type );
				$can_edit_post = current_user_can( $post_type_object->cap->edit_post, $post->ID );

				echo '<strong><a class="row-title" href="' . $edit_link . '">' . $title . '</a>';

				// display post states a little more selectively than _post_states( $post );
				if ( 'draft' == $post->post_status ) {
					echo " - <span class='post-state'>" . __( 'Draft', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</span>';
				}

				echo '</strong>';

				// Get actions
				$actions = array();

				$actions['id'] = 'ID: ' . $post->ID;

				if ( current_user_can( $post_type_object->cap->delete_post, $post->ID ) ) {
					if ( 'trash' == $post->post_status )
						$actions['untrash'] = "<a title='" . esc_attr( __( 'Restore this item from the Trash', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ) . "' href='" . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-' . $post->post_type . '_' . $post->ID ) . "'>" . __( 'Restore', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . "</a>";
					elseif ( EMPTY_TRASH_DAYS )
						$actions['trash'] = "<a class='submitdelete' title='" . esc_attr( __( 'Move this item to the Trash', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ) . "' href='" . get_delete_post_link( $post->ID ) . "'>" . __( 'Trash', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . "</a>";
					if ( 'trash' == $post->post_status || ! EMPTY_TRASH_DAYS )
						$actions['delete'] = "<a class='submitdelete' title='" . esc_attr( __( 'Delete this item permanently', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . "</a>";
				}

				// TODO: add a duplicate voucher action?

				$actions = apply_filters( 'post_row_actions', $actions, $post );

				echo '<div class="row-actions">';

				$i = 0;
				$action_count = count( $actions );

				foreach ( $actions as $action => $link ) {
					( $action_count - 1 == $i ) ? $sep = '' : $sep = ' | ';
					echo '<span class="' . $action . '">' . $link . $sep . '</span>';
					$i++;
				}
				echo '</div>';
			break;

			case "days_to_expiry":
				echo $voucher->get_expiry();
			break;
		}
	}


}
