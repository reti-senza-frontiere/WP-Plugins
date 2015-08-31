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
 * PDF Product Vouchers Voucher Admin
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Admin {

	/** @var WC_PDF_Product_Vouchers parent plugin */
	private $plugin;

	/** @var WC_PDF_Product_Vouchers_Admin_Orders the orders admin handler */
	private $admin_orders;

	/** @var WC_PDF_Product_Vouchers_Admin_Product the products admin handler */
	private $admin_products;

	/** @var WC_PDF_Product_Vouchers_Admin_Voucher_List_Table admin voucher list table */
	private $list_table;

	/** @var WC_PDF_Product_Vouchers_Admin_Vouchers admin vouchers handler */
	private $admin_vouchers;

	/**
	 * Initialize the voucher admin
	 *
	 * @since 1.2
	 * @param WC_PDF_Product_Vouchers $plugin the parent plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		require_once( $this->plugin->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin-voucher-list-table.php' );
		$this->list_table = new WC_PDF_Product_Vouchers_Admin_Voucher_List_Table();

		require_once( $this->plugin->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin-vouchers.php' );
		$this->list_table = new WC_PDF_Product_Vouchers_Admin_Vouchers();

		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_action( 'admin_init', array( $this, 'init' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'post_updated_messages', array( $this, 'product_updated_messages' ) );

		add_action( 'init', array( $this, 'download_voucher' ) );
	}


	/**
	 * Admin voucher download, which will work regardless of the download
	 * permissions/current user, and will not count towards the download
	 * count
	 *
	 * @since 2.1
	 */
	public function download_voucher() {

		if ( isset( $_GET['post'] ) && isset( $_GET['product_id'] ) && isset( $_GET['item_id'] ) && isset( $_GET['action'] ) && 'download' == $_GET['action'] && $_GET['voucher_id'] ) {

			$order = wc_get_order( $_GET['post'] );
			$items = $order->get_items();
			$voucher = new WC_Voucher( $_GET['voucher_id'], $_GET['post'], $items[ $_GET['item_id'] ], $_GET['item_id'] );

			$download_handler = new WC_Download_Handler();
			$file_path = $voucher->get_voucher_full_filename( WC_PDF_Product_Vouchers::get_uploads_path() );

			if ( 'redirect' === get_option( 'woocommerce_file_download_method' ) ) {
				$file_path = $voucher->convert_path_to_url( $file_path );
			}

			$download_handler->download( $file_path, $_GET['product_id'] );
			exit;
		}
	}


	/**
	 * Set the product updated messages so they're specific to the Vouchers
	 *
	 * @since 1.2
	 */
	public function product_updated_messages( $messages ) {

		global $post, $post_ID;

		$messages['wc_voucher'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Voucher updated.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			2 => __( 'Custom field updated.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			3 => __( 'Custom field deleted.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			4 => __( 'Voucher updated.', WC_PDF_Product_Vouchers::TEXT_DOMAIN),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Voucher restored to revision from %s', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Voucher updated.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			7 => __( 'Voucher saved.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			8 => __( 'Voucher submitted.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			9 => sprintf( __( 'Voucher scheduled for: <strong>%1$s</strong>.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			  date_i18n( __( 'M j, Y @ G:i', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), strtotime( $post->post_date ) ) ),
			10 => __( 'Voucher draft updated.', WC_PDF_Product_Vouchers::TEXT_DOMAIN),
		);

		return $messages;
	}


	/**
	 * Enqueue the vouchers admin scripts
	 *
	 * @since 1.2
	 */
	public function enqueue_scripts() {

		global $post, $wp_version;

		// Get admin screen id
		$screen = get_current_screen();

		// TODO: check for $screen->id == 'edit-wc_voucher' and enqueue woocommerce_admin_styles?

		// WooCommerce admin pages
		if ( 'wc_voucher' == $screen->id ) {

			// color picker script/styles
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_media();

			// image area select, for selecting the voucher fields
			wp_enqueue_script( 'imgareaselect' );
			wp_enqueue_style( 'imgareaselect' );

			// make sure the woocommerce admin styles are available for both the voucher edit page, and list page
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
		}

		if ( in_array( $screen->id, array( 'shop_order', 'wc_voucher' ) ) ) {

			// default javascript params
			$woocommerce_vouchers_params = array( 'primary_image_width' => '', 'primary_image_height' => '' );

			if ( 'wc_voucher' == $screen->id ) {
				// get the primary image dimensions (if any) which are needed for the page script
				$attachment = null;
				$image_ids = get_post_meta( $post->ID, '_image_ids', true );

				if ( is_array( $image_ids ) && isset( $image_ids[0] ) && $image_ids[0] ) {
					$attachment = wp_get_attachment_metadata( $image_ids[0] );
				}

				// pass parameters into the javascript file
				$woocommerce_vouchers_params = array(
					'done_label'           => __( 'Done', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
					'set_position_label'   => __( 'Set Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
					'post_id'              => $post->ID,
					'primary_image_width'  => isset( $attachment['width']  ) && $attachment['width']  ? $attachment['width']  : '0',
					'primary_image_height' => isset( $attachment['height'] ) && $attachment['height'] ? $attachment['height'] : '0',
				 );
			}

			wp_enqueue_script( 'woocommerce_vouchers_admin', $this->plugin->get_plugin_url() . '/assets/js/admin/wc-pdf-product-vouchers.min.js', array( 'jquery' ) );
			wp_localize_script( 'woocommerce_vouchers_admin', 'woocommerce_vouchers_params', $woocommerce_vouchers_params );

			wp_enqueue_style( 'woocommerce_vouchers_admin_styles', $this->plugin->get_plugin_url() . '/assets/css/admin/wc-pdf-product-vouchers.min.css' );
		}
	}


	/**
	 * Initialize the admin, adding actions to properly display and handle
	 * the Voucher custom post type add/edit page
	 *
	 * @since 1.2
	 */
	public function init() {
		global $pagenow;

		require_once( $this->plugin->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin-products.php' );
		$this->admin_products = new WC_PDF_Product_Vouchers_Admin_Products();

		if ( 'post-new.php' == $pagenow || 'post.php' == $pagenow || 'edit.php' == $pagenow ) {

			require_once( $this->plugin->get_plugin_path() . '/includes/admin/class-wc-pdf-product-vouchers-admin-orders.php' );
			$this->admin_orders = new WC_PDF_Product_Vouchers_Admin_Orders();

			// add voucher list/edit pages contextual help
			add_action( 'admin_print_styles', array( $this, 'help_tab' ) );
		}
	}


	/**
	 * Adds the Vouchers Admin Help tab to the Vouchers admin screens
	 *
	 * @since 1.2
	 */
	public function help_tab() {

		$screen = get_current_screen();

		if ( 'edit-wc_voucher' != $screen->id && 'wc_voucher' != $screen->id ) return;

		$screen->add_help_tab( array(
			'id'      => 'wc_vouchers_overview_help_tab',
			'title'   => __( 'Overview', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			'content' => '<p>' . __( 'The WooCommerce PDF Product Vouchers plugin allows you to create and configure customizable vouchers which can be attached to simple/variable downloadable products and purchased by your customers.  You can give your customers the ability to set a recipient name/message and purchase these vouchers as a gift, and allow them to choose from among a set of voucher images.  Once the purchase is made the customer will have access to a custom-generated PDF voucher in the same manner as a standard downloadable product.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'       => 'wc_vouchers_voucher_help_tab',
			'title'    => __( 'Editing a Voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			'callback' => array( $this, 'help_tab_content' ),
		) );

		$screen->add_help_tab( array(
			'id'      => 'wc_vouchers_list_help_tab',
			'title'   => __( 'Vouchers List', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			'content' => '<p>' . __( 'From the list view you can review all your voucher templates, quickly see the name, primary default image and optional expiry days, and trash a voucher template.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</p>'
		) );

		$screen->add_help_tab( array(
			'id'       => 'wc_vouchers_how_to_help_tab',
			'title'    => __( 'How To', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			'callback' => array( $this, 'how_to_help_tab_content' ),
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</strong></p>' .
			'<p><a href="http://docs.woothemes.com/document/pdf-product-vouchers/" target="_blank">' . __( 'Vouchers Docs', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</a></p>'
		);
	}


	/**
	 * Renders the Voucher help tab content for the contextual help menu
	 *
	 * @since 1.2
	 */
	public function help_tab_content() {
		?>
		<p><strong><?php _e( 'Voucher Name', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'All voucher templates must be given a name.  This will be used to identify the voucher within the admin; from the frontend the voucher will be identified to the customer by a unique voucher number.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><strong><?php _e( 'Primary Voucher Image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'This is the main image for your voucher, and will be used to configure the layout of the various text fields defined in the Voucher Data panel.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><strong><?php _e( 'Voucher Data', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'These configuration options allow you to specify exactly where various text fields will be displayed on your voucher, as well as the font used.  For instance, if you want the product name displayed on your voucher, click the "Set Position" button next to "Product Name Position".  Then select the area of the Voucher Image where you want the product name to be displayed.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><?php _e( 'You can define a default font, size, style and color to be used for the voucher text fields.  For each individual text field, you can override these defaults by setting a specific font/style, size or color.  Note that the default font style (Italic/Bold) will only be used if a font is not selected at the field level.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><strong><?php _e( 'Alternative Images', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'You can add alternative voucher images so your customers can choose from multiple backgrounds when purchasing a voucher.  Just make sure all the images have the same layout so the voucher text fields are put in the correct position.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><strong><?php _e( 'Additional Image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'You can add a second page to the voucher with this option, containing for instance voucher instructions or policies.  As with the alternative images, ensure that this additional image has the same dimensions as the primary voucher image.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<p><strong><?php _e( 'Previewing', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong> - <?php _e( 'You must update the voucher to see any changes in the voucher Preview.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></p>
		<?php
	}


	/**
	 * Renders the "How To" help tab content for the contextual help menu
	 *
	 * @since 1.2
	 */
	public function how_to_help_tab_content() {
		?>
		<p><strong><?php _e( 'How to Create Your First Voucher Product', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></strong></p>
		<ol>
			<li><?php _e( 'First go to WooCommerce &gt; Vouchers and click "Add Voucher" to add a voucher template', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
			<li><?php _e( 'Set a Voucher Name, and Primary Voucher Image.  Optionally configure and add some Voucher Data fields (see the "Editing a Voucher" section for more details)', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
			<li><?php _e( 'Next click "Publish" to save your voucher template.  You can also optionally "Preview" the voucher to check your work and field layout.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
			<li><?php _e( 'Next go to WooCommerce &gt; Products and either create a new product or edit an existing one, being sure to make it either Simple or Variable, and checking the "Downloadable" option (probably also checking the "Virtual" option, unless you plan on mailing hard copies of the product Vouchers).', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
			<li><?php _e( 'With the "Downloadable" option checked on a Simple type product you should see a field named "Voucher" in the General Product Data tab with a select box containing your newly created voucher template.  With a "Variable" type product the field will appear in the Variations area.  Select your voucher and save the product.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
			<li><?php _e( 'Your product voucher is now available for purchase!  Run a test transaction from the frontend, you should see the voucher primary image(s) displayed on the product page, along with the optional Recipient Name/Messag fields if you added them.  The voucher PDF will be available via downloadable link the same as any standard downloadable produt.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ); ?></li>
		</ol>
		<?php
	}


	/**
	 * Highlight the correct top level admin menu item for the voucher post type add screen
	 *
	 * @since 1.2
	 */
	public function menu_highlight() {

		global $menu, $submenu, $parent_file, $submenu_file, $self, $post_type, $taxonomy;

		if ( isset( $post_type ) && 'wc_voucher' == $post_type ) {
			$submenu_file = 'edit.php?post_type=' . $post_type;
			$parent_file  = 'woocommerce';
		}
	}
}
