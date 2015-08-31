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
 * PDF Product Vouchers Taxonomy handler/helper class
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Taxonomy {


	/**
	 * Initialize the taxonomy class
	 *
	 * @since 1.2
	 */
	public function __construct() {

		add_image_size( 'wc-pdf-product-vouchers-voucher-thumb', WC_PDF_Product_Vouchers::VOUCHER_IMAGE_THUMB_WIDTH );

		// load the wc_voucher custom post type single template, used to generate a preview voucher from the admin
		add_filter( 'single_template', 'wc_vouchers_locate_voucher_preview_template' );

		add_action( 'init',    array( $this, 'init' ) );
	}


	/**
	 * Initialize Vouchers taxonomy
	 *
	 * @since 1.2
	 */
	public function init() {

		// Init user roles
		$this->init_user_roles();

		// Init WooCommerce PDF Product Vouchers taxonomy
		$this->init_taxonomy();

	}


	/**
	 * Init WooCommerce PDF Product Vouchers user role
	 *
	 * @since 1.2
	 */
	private function init_user_roles() {
		global $wp_roles;

		if ( class_exists( 'WP_Roles' ) ) if ( ! isset( $wp_roles ) ) $wp_roles = new WP_Roles();

		if ( is_object( $wp_roles ) ) {
			$wp_roles->add_cap( 'shop_manager',  'manage_woocommerce_vouchers' );
			$wp_roles->add_cap( 'administrator', 'manage_woocommerce_vouchers' );
		}
	}


	/**
	 * Init WooCommerce taxonomies
	 *
	 * @since 1.2
	 */
	private function init_taxonomy() {

		if ( current_user_can( 'manage_woocommerce' ) ) $show_in_menu = 'woocommerce'; else $show_in_menu = true;

		register_post_type( 'wc_voucher',
			array(
				'labels' => array(
						'name'               => __( 'Vouchers', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'singular_name'      => __( 'Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'menu_name'          => _x( 'Vouchers', 'Admin menu name', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'add_new'            => __( 'Add Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'add_new_item'       => __( 'Add New Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'edit'               => __( 'Edit', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'edit_item'          => __( 'Edit Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'new_item'           => __( 'New Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'view'               => __( 'View Vouchers', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'view_item'          => __( 'View Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'search_items'       => __( 'Search Vouchers', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'not_found'          => __( 'No Vouchers found', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						'not_found_in_trash' => __( 'No Vouchers found in trash', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
					),
				'description'     => __( 'This is where you can add new vouchers that you can attach to products and sell.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'public'          => true,
				'show_ui'         => true,
				'capability_type' => 'post',
				'capabilities' => array(
					'publish_posts'       => 'manage_woocommerce_vouchers',
					'edit_posts'          => 'manage_woocommerce_vouchers',
					'edit_others_posts'   => 'manage_woocommerce_vouchers',
					'delete_posts'        => 'manage_woocommerce_vouchers',
					'delete_others_posts' => 'manage_woocommerce_vouchers',
					'read_private_posts'  => 'manage_woocommerce_vouchers',
					'edit_post'           => 'manage_woocommerce_vouchers',
					'delete_post'         => 'manage_woocommerce_vouchers',
					'read_post'           => 'manage_woocommerce_vouchers',
				),
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_in_menu'        => $show_in_menu,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'show_in_nav_menus'   => false,
			)
		);
	}


}
