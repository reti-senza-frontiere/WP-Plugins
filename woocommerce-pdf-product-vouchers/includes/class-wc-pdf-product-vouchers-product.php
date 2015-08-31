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
 * PDF Product Vouchers Product helper class.  Provides product utility methods
 * and handles aspectes of the plugin related to products.
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Product {


	/** @var object the parent plugin */
	private $plugin;


	/**
	 * PDF Product Vouchers Product constructor
	 *
	 * @since 1.2
	 * @param object $plugin the parent plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// add product page stylesheet
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// add product page voucher options
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_product_voucher_options' ) );
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'loop_add_to_cart_link' ), 10, 2 );
	}


	/**
	 * Enqueues the frontend product page stylesheet, if this is a voucher product
	 *
	 * @since 1.2
	 */
	public function enqueue_scripts() {

		global $post;

		if ( is_product() ) {

			$product = wc_get_product( $post->ID );

			if ( self::has_voucher( $product ) ) {
				wp_enqueue_style( 'wc-pdf-product-vouchers-product-styles', $this->plugin->get_plugin_url() . '/assets/css/frontend/wc-pdf-product-vouchers.min.css', array(), $this->plugin->get_version() );

				if ( $product->is_type( 'variable' ) ) {
					wp_enqueue_script( 'wc-pdf-product-vouchers-frontend-script', $this->plugin->get_plugin_url() . '/assets/js/frontend/wc-pdf-product-vouchers.min.js', array( 'jquery' ) );
				}
			}
		}
	}


	/**
	 * If this is a downloadable product with an attached voucher, display
	 * any user-input voucher fields, and any voucher layout image options
	 *
	 * @since 1.2
	 */
	public function render_product_voucher_options() {

		global $product;

		if ( self::has_voucher( $product ) ) {
			wc_pdf_product_vouchers_render_product_voucher_fields( $product );
		}
	}


	/**
	 * Modify the loop 'add to cart' button class for simple voucher products
	 * with required input fields to link directly to the product page like a
	 * variable product.
	 *
	 * @since 1.2
	 * @param string $tag the 'add to cart' button tag html
	 * @param WC_Product $product the product
	 * @return string the Add to Cart tag
	 */
	public function loop_add_to_cart_link( $tag, $product ) {

		if ( $product && $product->is_type( 'simple' ) && self::has_voucher( $product ) ) {

			$voucher = self::get_voucher( $product );

			if ( $voucher->has_required_input_fields() ) {

				// otherwise, for simple type products, the page javascript would take over and
				//  try to do an ajax add-to-cart, when really we need the customer to visit the
				//  product page to supply whatever input fields they require
				$tag = sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" class="button add_to_cart_button product_type_%s">%s</a>',
					get_permalink( $product->id ),
					esc_attr( $product->id ),
					esc_attr( $product->get_sku() ),
					'variable',
					__( 'Select options', WC_PDF_Product_Vouchers::TEXT_DOMAIN )
				);
			}
		}

		return $tag;
	}



	/** Utility/Helper methods ******************************************************/


	/**
	 * Returns true if the given product is downloadable with an attached
	 * voucher
	 *
	 * @since 1.2
	 * @param WC_Product $product the product to check for a voucher
	 * @return boolean true if $product has a voucher, false otherwise
	 */
	public static function has_voucher( $product ) {

		if ( ! $product->exists() ) {
			return false;
		}

		return null !== self::get_voucher( $product );
	}


	/**
	 * Returns the voucher attached to $product
	 *
	 * @since 1.2
	 * @param WC_Product $product the voucher product
	 * @return WC_Voucher the voucher attached to $product
	 */
	public static function get_voucher( $product ) {

		if ( $product->is_type( 'variable' ) ) {

			foreach ( $product->get_children() as $variation_product_id ) {

				$variation_product = wc_get_product( $variation_product_id );

				if ( $variation_product->is_downloadable() && $variation_product->voucher_id ) {
					// Note: this assumes that there is only one voucher attached to any variations for a product, which probably isn't a great assumption, but simplifies the frontend for now
					return new WC_Voucher( $variation_product->voucher_id );
				}
			}
		} elseif ( $product->is_downloadable() && $product->voucher_id ) {
			// simple product or product variation
			return new WC_Voucher( $product->voucher_id );
		}

		// aw, no voucher
		return null;
	}


	/**
	 * Returns the voucher id of the voucher attached to $product, if any
	 *
	 * @since 1.2
	 * @param WC_Product $product the product
	 * @return int voucher id of attached voucher, if any
	 */
	public static function get_voucher_id( $product ) {
		return $product->voucher_id;
	}


}
