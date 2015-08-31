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
 * PDF Product Vouchers Admin
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Admin_Vouchers {


	/**
	 * Initialize the voucher orders admin
	 *
	 * @since 1.2
	 */
	public function __construct() {

		add_action( 'woocommerce_process_wc_voucher_meta', array( $this, 'process_voucher_meta' ), 10, 2 );

		add_action( 'add_meta_boxes',     array( $this, 'add_meta_boxes' ) );

		add_filter( 'enter_title_here',   array( $this, 'enter_title_here' ), 1, 2 );

		add_action( 'save_post',          array( $this, 'meta_boxes_save' ), 1, 2 );

		add_action( 'publish_wc_voucher', array( $this, 'wc_voucher_private' ), 10, 2 );

		add_action( 'admin_print_styles', array( $this, 'print_styles' ) );

	}


	/**
	 * Print any styles for the Voucher Add/Edit admin page
	 *
	 * @since 1.2
	 */
	public function print_styles() {

		global $post_type;

		if ( 'wc_voucher' == $post_type ) {
			?>
			<style type="text/css">
				#minor-publishing #minor-publishing-actions { padding-bottom:10px; }
			</style>
			<?php
		}
	}


	/**
	 * Set a more appropriate placeholder text for the New Voucher title field
	 *
	 * @since 1.2
	 * @param string $text "Enter Title Here" string
	 * @param object $post post object
	 *
	 * @return string "Voucher Name" when the post type is wc_voucher
	 */
	public function enter_title_here( $text, $post ) {
		if ( 'wc_voucher' == $post->post_type ) return __( 'Voucher Name', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		return $text;
	}


	/**
	 * Automatically make the voucher posts private when they are published.
	 * That way we can have them be publicly_queryable for the purposes of
	 * generating a preview pdf for the admin user, while having them always
	 * hidden on the frontend (draft posts are not visible by definition)
	 *
	 * @since 1.2
	 * @param int $post_id the voucher identifier
	 * @param object $post the voucher object
	 */
	public function wc_voucher_private( $post_id, $post ) {
		global $wpdb;

		$wpdb->update( $wpdb->posts, array( 'post_status' => 'private' ), array( 'ID' => $post_id ) );
	}


	/** Helper methods ******************************************************/


	/**
	 * Rendres a custom admin input field to select a font which includes font
	 * family, size and style (bold/italic)
	 *
	 * @since 1.2
	 */
	public static function wc_vouchers_wp_font_select( $field ) {

		global $thepostid, $post;

		if ( ! $thepostid ) $thepostid = $post->ID;

		// values
		$font_family_value = $font_size_value = $font_style_value = '';

		if ( '_voucher' == $field['id'] ) {
			// voucher defaults
			$font_family_value = get_post_meta( $thepostid, $field['id'] . '_font_family', true );
			$font_size_value   = get_post_meta( $thepostid, $field['id'] . '_font_size',   true );
			$font_style_value  = get_post_meta( $thepostid, $field['id'] . '_font_style',  true );
		} else {
			// field-specific overrides
			$voucher_fields = get_post_meta( $thepostid, '_voucher_fields', true );
			$field_name = ltrim( $field['id'], '_' );

			if ( is_array( $voucher_fields ) ) {
				if ( isset( $voucher_fields[ $field_name ]['font']['family'] ) ) $font_family_value = $voucher_fields[ $field_name ]['font']['family'];
				if ( isset( $voucher_fields[ $field_name ]['font']['size'] ) )   $font_size_value   = $voucher_fields[ $field_name ]['font']['size'];
				if ( isset( $voucher_fields[ $field_name ]['font']['style'] ) )  $font_style_value  = $voucher_fields[ $field_name ]['font']['style'];
			}
		}

		// defaults
		if ( ! $font_size_value && isset( $field['font_size_default'] ) ) $font_size_value = $field['font_size_default'];

		echo '<p class="form-field ' . $field['id'] . '_font_family_field"><label for="' . $field['id'] . '_font_family">' . $field['label'] . '</label><select id="' . $field['id'] . '_font_family" name="' . $field['id'] . '_font_family" class="select short">';

		foreach ( $field['options'] as $key => $value ) {
			echo '<option value="' . $key . '" ';
			selected( $font_family_value, $key );
			echo '>' . $value . '</option>';
		}

		echo '</select> ';

		echo '<input type="text" style="width:auto;margin-left:10px;" size="2" name="' . $field['id'] . '_font_size" id="' . $field['id'] . '_font_size" value="' . esc_attr( $font_size_value ) . '" placeholder="' . __( 'Size', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '" /> ';

		echo '<label for="' . $field['id'] . '_font_style_b" style="width:auto;margin:0 5px 0 10px;">' . __( 'Bold', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;float:left;" name="' . $field['id'] . '_font_style_b" id="' . $field['id'] . '_font_style_b" value="yes" ';
		checked( false !== strpos( $font_style_value, 'B' ), true );
		echo ' /> ';

		echo '<label for="' . $field['id'] . '_font_style_i" style="width:auto;margin:0 5px 0 10px;">' . __( 'Italic', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '</label><input type="checkbox" class="checkbox" style="margin-top:4px;" name="' . $field['id'] . '_font_style_i" id="' . $field['id'] . '_font_style_i" value="yes" ';
		checked( false !== strpos( $font_style_value, 'I' ), true );
		echo ' /> ';

		echo '</p>';
	}


	/**
	 * Add inline javascript to activate the farbtastic color picker element.
	 * Must be called in order to use the wc_vouchers_wp_color_picker() method
	 *
	 * @since 1.2
	 */
	public static function wc_vouchers_wp_color_picker_js() {

		ob_start();
		?>
		$( ".colorpick" ).wpColorPicker();

		$( document ).mousedown( function( e ) {
			if ( $( e.target ).parents( ".wp-picker-holder" ) )
				return;
			if ( $( e.target ).parents( "mark" ) )
				return;
			$( ".wp-picker-holder" ).each( function() {
				$( this ).fadeOut();
			} );
		} );
		<?php
		$javascript = ob_get_clean();
		wc_enqueue_js( $javascript );
	}


	/**
	 * Renders a custom admin control used on the voucher edit page to Set/Remove
	 * the position via two buttons
	 *
	 * @since 1.2
	 */
	public static function wc_vouchers_wp_position_picker( $field ) {

		if ( ! isset( $field['value'] ) ) $field['value'] = '';

		echo '<p class="form-field"><label>' . $field['label'] . '</label><input type="button" id="' . $field['id'] . '" class="set_position button" value="' . esc_attr__( 'Set Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '" style="width:auto;" /> <input type="button" id="remove_' . $field['id'] . '" class="remove_position button" value="' . esc_attr__( 'Remove Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) . '" style="width:auto;' . ( $field['value'] ? '' : 'display:none' ) . ';margin-left:7px;" />';

		if ( isset( $field['description'] ) && $field['description'] ) {

			if ( isset( $field['desc_tip'] ) ) {
				echo '<img class="help_tip" data-tip="' . esc_attr( $field['description'] ) . '" src="' . WC()->plugin_url() . '/assets/images/help.png" />';
			} else {
				echo '<span class="description">' . $field['description'] . '</span>';
			}
		}
		echo '</p>';
	}


	/** Meta Boxes ******************************************************/


	/**
	 * Add and remove meta boxes from the Voucher edit page and Order edit page
	 *
	 * @since 1.2
	 */
	public function add_meta_boxes() {

		// Voucher Primary Image box
		add_meta_box(
			'woocommerce-voucher-image',
			__( 'Primary Voucher Image <small>&ndash; Used to lay out the voucher fields found in the Voucher Data box.</small>', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			array( $this, 'image_meta_box' ),
			'wc_voucher',
			'normal',
			'high'
		);

		// Voucher Data box
		add_meta_box(
			'woocommerce-voucher-data',
			__( 'Voucher Data', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			array( $this, 'data_meta_box' ),
			'wc_voucher',
			'normal',
			'high'
		);

		// Voucher alternative images box
		add_meta_box(
			'woocommerce-voucher-alternative-images',
			__( 'Alternative Images <small>&ndash; Optional alternative images with the same layout and dimensions as the primary image, which your customers may choose from.</small>', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			array( $this, 'alternative_images_meta_box' ),
			'wc_voucher',
			'normal',
			'high'
		);

		// Voucher additional image box
		add_meta_box(
			'woocommerce-voucher-additional-images',
			__( 'Additional Image <small>&ndash; Optional image with the same dimensions as the primary voucher image, that will be added as a second page to the voucher.</small>', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
			array( $this, 'additional_images_meta_box' ),
			'wc_voucher',
			'normal',
			'high'
		);

		// remove unnecessary meta boxes
		remove_meta_box( 'woothemes-settings', 'wc_voucher', 'normal' );
		remove_meta_box( 'commentstatusdiv',   'wc_voucher', 'normal' );
		remove_meta_box( 'slugdiv',            'wc_voucher', 'normal' );
	}


	/**
	 * Render the voucher additional (second page) image meta box.  This box
	 * allows a second page, image to be added or removed.
	 *
	 * @since 1.2
	 */
	public function additional_images_meta_box() {

		global $post;

		$image_src = '';
		$image_id = '';
		$image_ids = get_post_meta( $post->ID, '_additional_image_ids', true );

		if ( is_array( $image_ids ) && count( $image_ids ) > 0 ) {
			$image_id = $image_ids[0];
			$image_src = wp_get_attachment_url( $image_id );
		}

		?>
		<div>
			<img id="voucher_additional_image" src="<?php echo $image_src ?>" style="max-width:100px;max-height:100px;" />
		</div>
		<input type="hidden" name="upload_additional_image_id[0]" id="upload_additional_image_id_0" value="<?php echo $image_id; ?>" />
		<p>
			<a title="<?php esc_attr_e( 'Set additional image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?>" href="#" id="set-additional-image" style="<?php echo ( $image_id ? 'display:none;' : '' ); ?>"><?php _e( 'Set additional image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></a>
			<a title="<?php esc_attr_e( 'Remove additional image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?>" href="#" id="remove-additional-image" style="<?php echo ( ! $image_id ? 'display:none;' : '' ); ?>"><?php _e( 'Remove additional image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></a>
		</p>
		<?php
	}


	/**
	 * Render the voucher alternative images meta box.  This box allows alternative
	 * images, with the same dimensions/layout as the main voucher image, to be added,
	 * removed, and displayed in a little gallery
	 *
	 * @since 1.2
	 */
	public function alternative_images_meta_box() {

		global $post;

		$image_ids = get_post_meta( $post->ID, '_image_ids', true );

		?>
		<?php

		echo '<ul id="voucher_alternative_images">';
		if ( is_array( $image_ids ) ) {
			for ( $i = 1, $ix = count( $image_ids ); $i < $ix; $i++ ) {

				$image_src = wp_get_attachment_url( $image_ids[ $i ] );
				?>
				<li class="alternative_image"><a href="#" class="remove-alternative-voucher-image"><img style="max-width:100px;max-height:100px;" src="<?php echo $image_src ?>" /><input type="hidden" name="upload_image_id[<?php echo $i ?>]" class="upload_image_id" value="<?php echo $image_ids[ $i ] ?>" /><span class="overlay"></span></a></li>
				<?php
			}
		}
		echo '</ul>';

		?>
		<p style="clear:left;">
			<a title="<?php esc_attr_e( 'Add an alternative voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?>" href="#" id="add-alternative-voucher-image"><?php _e( 'Add an alternative voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></a>
		</p>
		<?php
	}


	/**
	 * Voucher data meta box
	 *
	 * @since 1.2
	 */
	public function data_meta_box( $post ) {

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

		$voucher = new WC_Voucher( $post->ID );

		$default_fonts = array(
			'Helvetica' => 'Helvetica',
			'Courier'   => 'Courier',
			'Times'     => 'Times',
			'Roboto'     => 'Roboto',
			'Merriweather'     => 'Merriweather',
		);
		$available_fonts = array_merge( array( '' => '' ), $default_fonts );

		// since this little snippet of css applies only to the voucher post page, it's easier to have inline here
		?>
		<style type="text/css">
			#misc-publishing-actions { display:none; }
			#edit-slug-box { display:none }
			.imgareaselect-outer { cursor: crosshair; }
		</style>
		<div id="voucher_options" class="panel woocommerce_options_panel">
			<div class="options_group">
				<?php

					// Text color
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'                => '_voucher',
							'label'             => __( 'Default Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options'           => $default_fonts,
							'font_size_default' => 11,
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_voucher_font_color',
							'label'       => __( 'Default Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'default'     => '#000000',
							'description' => __( 'The default text color for the voucher.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'class'       => 'colorpick',
						) );
						woocommerce_wp_select( array(
							'id'          => '_voucher_text_align',
							'label'       => __( 'Default Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The default text alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options'     => array(
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Product name position
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'product_name_pos',
							'label'       => __( 'Product Name Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'product_name' ) ),
							'description' => __( 'Optional position of the product name', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_product_name_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'product_name' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_product_name',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_product_name_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['product_name']['font']['color'] ) ? $voucher->voucher_fields['product_name']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_select( array(
							'id'          => '_product_name_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['product_name']['text_align'] ) ? $voucher->voucher_fields['product_name']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// SKU position
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'product_sku_pos',
							'label'       => __( 'SKU Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'product_sku' ) ),
							'description' => __( 'Optional position of the product SKU', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_product_sku_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'product_sku' ) )
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_product_sku',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_product_sku_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['product_sku']['font']['color'] ) ? $voucher->voucher_fields['product_sku']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_select( array(
							'id'          => '_product_sku_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['product_sku']['text_align'] ) ? $voucher->voucher_fields['product_sku']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Voucher number position
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'voucher_number_pos',
							'label'       => __( 'Voucher Number Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'voucher_number' ) ),
							'description' => __( 'Optional position of the voucher number', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_voucher_number_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'voucher_number' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_voucher_number',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_voucher_number_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['voucher_number']['font']['color'] ) ? $voucher->voucher_fields['voucher_number']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_select( array(
							'id'          => '_voucher_number_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['voucher_number']['text_align'] ) ? $voucher->voucher_fields['voucher_number']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Product price
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'voucher_product_price_pos',
							'label'       => __( 'Product Price Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'voucher_product_price' ) ),
							'description' => __( 'Optional position of the product price', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_voucher_product_price_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'voucher_product_price' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_voucher_product_price',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_voucher_product_price_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['voucher_product_price']['font']['color'] ) ? $voucher->voucher_fields['voucher_product_price']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_select( array(
							'id'          => '_voucher_product_price_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['voucher_product_price']['text_align'] ) ? $voucher->voucher_fields['voucher_product_price']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Days to expiration
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'expiration_date_pos',
							'label'       => __( 'Expiration Date Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'expiration_date' ) ),
							'description' => __( 'Optional position of the voucher expiration date', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id' => '_expiration_date_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'expiration_date' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_expiration_date',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_expiration_date_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['expiration_date']['font']['color'] ) ? $voucher->voucher_fields['expiration_date']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_days_to_expiry',
							'label'       => __( 'Days to Expiration', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Optional number of days after purchase until the voucher expires', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'placeholder' => __( 'days', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->get_expiry(),
						) );
						woocommerce_wp_select( array(
							'id'          => '_expiration_date_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['expiration_date']['text_align'] ) ? $voucher->voucher_fields['expiration_date']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Voucher recipient position
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'recipient_name_pos',
							'label'       => __( 'Recipient Name Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'recipient_name' ) ),
							'description' => __( 'Optional position of the name of the receiving party.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_recipient_name_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'recipient_name' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_recipient_name',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_recipient_name_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['recipient_name']['font']['color'] ) ? $voucher->voucher_fields['recipient_name']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_recipient_name_max_length',
							'label'       => __( 'Max Length', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The maximum length of the recipient name field', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'placeholder' => __( 'No Limit', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->get_user_input_field_max_length( 'recipient_name' ),
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_recipient_name_label',
							'label'       => __( 'Label', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The field label to show on the frontend/emails', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['recipient_name']['label'] ) ? $voucher->voucher_fields['recipient_name']['label'] : 'Recipient Name',
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_recipient_name_is_enabled',
							'label'       => __( 'Enabled', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Display this field on the product page (useful if you want the Recipient Name option without printing it to the voucher)', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_enabled( 'recipient_name' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_recipient_name_is_required',
							'label'       => __( 'Required', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Make this field required in order to add a voucher product to the cart', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_required( 'recipient_name' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_select( array(
							'id'          => '_recipient_name_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['recipient_name']['text_align'] ) ? $voucher->voucher_fields['recipient_name']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Voucher recipient email option
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'recipient_email_pos',
							'label'       => __( 'Recipient Email Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'recipient_email_pos' ) ),
							'description' => __( 'Optional position of the user-supplied recipient email', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_recipient_email_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'recipient_email_pos' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_recipient_email',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_recipient_email_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['recipient_email']['font']['color'] ) ? $voucher->voucher_fields['recipient_email']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_recipient_email_label',
							'label'       => __( 'Label', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The field label to show on the frontend/emails', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['recipient_email']['label'] ) ? $voucher->voucher_fields['recipient_email']['label'] : 'Recipient Email',
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_recipient_email_is_enabled',
							'label'       => __( 'Enabled', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Display this field on the product page (useful if you want the Recipient Email option without printing it to the voucher)', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_enabled( 'recipient_email' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_recipient_email_is_required',
							'label'       => __( 'Required', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Make this field required in order to add a voucher product to the cart', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_required( 'recipient_email' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_select( array(
							'id'          => '_message_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['message']['text_align'] ) ? $voucher->voucher_fields['message']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

					// Voucher message position
					echo '<div class="options_group">';
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_position_picker( array(
							'id'          => 'message_pos',
							'label'       => __( 'Message Position', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => implode( ',', $voucher->get_field_position( 'message' ) ),
							'description' => __( 'Optional position of the user-supplied message', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
						) );
						woocommerce_wp_hidden_input( array(
							'id'    => '_message_pos',
							'class' => 'field_pos',
							'value' => implode( ',', $voucher->get_field_position( 'message' ) ),
						) );
						WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_font_select( array(
							'id'      => '_message',
							'label'   => __( 'Font', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'options' => $available_fonts,
						) );
						woocommerce_wp_text_input( array(
							'id'    => '_message_font_color',
							'label' => __( 'Font color', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value' => isset( $voucher->voucher_fields['message']['font']['color'] ) ? $voucher->voucher_fields['message']['font']['color'] : '',
							'class' => 'colorpick',
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_message_label',
							'label'       => __( 'Label', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The field label to show on the frontend/emails', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['message']['label'] ) ? $voucher->voucher_fields['message']['label'] : 'Message to Recipient',
						) );
						woocommerce_wp_text_input( array(
							'id'          => '_message_max_length',
							'label'       => __( 'Max Length', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'The maximum length of the message field', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'placeholder' => __( 'No Limit', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->get_user_input_field_max_length( 'message' ),
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_message_is_enabled',
							'label'       => __( 'Enabled', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Display this field on the product page (useful if you want the customer to be able to add a personalized message that will be included in the recipient email without printing it to the voucher)', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_enabled( 'message' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_checkbox( array(
							'id'          => '_message_is_required',
							'label'       => __( 'Required', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'description' => __( 'Make this field required in order to add a voucher product to the cart', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => $voucher->user_input_field_is_required( 'message' ) ? 'yes' : 'no',
						) );
						woocommerce_wp_select( array(
							'id'          => '_message_text_align',
							'label'       => __( 'Text Alignment', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							'value'       => isset( $voucher->voucher_fields['message']['text_align'] ) ? $voucher->voucher_fields['message']['text_align'] : '',
							'options' => array(
								''  => '',
								'L' => _x( 'Left', 'left justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'C' => _x( 'Center', 'center justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
								'R' => _x( 'Right', 'right justified', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
							),
						) );
					echo '</div>';

				?>
			</div>
		</div>
		<?php

		WC_PDF_Product_Vouchers_Admin_Vouchers::wc_vouchers_wp_color_picker_js();
	}


	/**
	 * Display the voucher image meta box
	 * Fluid image reference: http://unstoppablerobotninja.com/entry/fluid-images
	 *
	 * @since 1.2
	 */
	public function image_meta_box() {

		global $post;

		$image_src = '';
		$image_id  = '';

		$image_ids = get_post_meta( $post->ID, '_image_ids', true );

		if ( is_array( $image_ids ) && count( $image_ids ) > 0 ) {
			$image_id = $image_ids[0];
			$image_src = wp_get_attachment_url( $image_id );
		}

		$attachment = wp_get_attachment_metadata( $image_id );

		?>
		<div id="voucher_image_wrapper" style="position:relative;">
			<img id="voucher_image_0" src="<?php echo $image_src ?>" style="max-width:100%;" />
		</div>
		<input type="hidden" name="upload_image_id[0]" id="upload_image_id_0" value="<?php echo $image_id; ?>" />
		<p>
			<a title="<?php esc_attr_e( 'Set voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?>" href="#" id="set-voucher-image"><?php _e( 'Set voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></a>
			<a title="<?php esc_attr_e( 'Remove voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?>" href="#" id="remove-voucher-image" style="<?php echo ( ! $image_id ? 'display:none;' : '' ); ?>"><?php _e( 'Remove voucher image', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ?></a>
		</p>
		<?php
	}


	/**
	 * Runs when a post is saved and does an action which the write panel save scripts can hook into.
	 *
	 * @since 1.2
	 * @param int $post_id post identifier
	 * @param object $post post object
	 */
	public function meta_boxes_save( $post_id, $post ) {

		if ( empty( $post_id ) || empty( $post ) || empty( $_POST ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( is_int( wp_is_post_revision( $post ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post ) ) ) return;
		if ( empty($_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		if ( 'wc_voucher' != $post->post_type ) return;

		do_action( 'woocommerce_process_wc_voucher_meta', $post_id, $post );
	}


	/**
	 * Voucher Additional Images Data Save
	 * Voucher Alternative Images Data Save
	 *
	 * Function for processing and storing voucher additional images
	 *
	 * @since 1.2
	 * @param int $post_id the voucher id
	 * @param object $post the voucher post object
	 */
	public function process_voucher_meta( $post_id, $post ) {

		// handle the additional images
		$additional_image_ids = $_POST['upload_additional_image_id'][0] ? $_POST['upload_additional_image_id'] : array();
		update_post_meta( $post_id, '_additional_image_ids', $additional_image_ids );

		// handle the special image_ids meta, which will always have at least an index 0 for the main template image, even if the value is empty
		$image_ids = array();
		foreach ( $_POST['upload_image_id'] as $i => $image_id ) {
			if ( 0 == $i || $image_id ) {
				$image_ids[] = $image_id;
			}
		}
		update_post_meta( $post_id, '_image_ids', $image_ids );

		if ( $image_ids[0] ) {
			set_post_thumbnail( $post_id, $image_ids[0] );
		} else {
			delete_post_thumbnail( $post_id );
		}

		// voucher font defaults
		update_post_meta( $post_id, '_voucher_font_color',  $_POST['_voucher_font_color'] ? $_POST['_voucher_font_color'] : '#000000' );  // provide a default
		update_post_meta( $post_id, '_voucher_font_size',   $_POST['_voucher_font_size'] ? $_POST['_voucher_font_size'] : 11 );  // provide a default
		update_post_meta( $post_id, '_voucher_font_family', $_POST['_voucher_font_family']  );
		update_post_meta( $post_id, '_voucher_font_style',  ( isset( $_POST['_voucher_font_style_b'] ) && 'yes' == $_POST['_voucher_font_style_b'] ? 'B' : '' ) .
		                                                    ( isset( $_POST['_voucher_font_style_i'] ) && 'yes' == $_POST['_voucher_font_style_i'] ? 'I' : '' ) );
		update_post_meta( $post_id, '_voucher_text_align',  $_POST['_voucher_text_align'] );

		// original sizes: default 11, product name 16, sku 8
		// create the voucher fields data structure
		$fields = array();
		foreach ( array( '_product_name', '_product_sku', '_voucher_number', '_voucher_product_price', '_expiration_date', '_recipient_name', '_recipient_email', '_message' ) as $i => $field_name ) {
			// set the field defaults
			$field = array(
				'type'       => 'property',
				'font'       => array( 'family' => '', 'size' => '', 'style' => '', 'color' => '' ),
				'text_align' => null,
				'position'   => array(),
				'order'      => $i,
			);

			// get the field position (if set)
			if ( $_POST[ $field_name . '_pos' ] ) {
				$position = explode( ',', $_POST[ $field_name . '_pos' ] );
				$field['position'] = array( 'x1' => $position[0], 'y1' => $position[1], 'width' => $position[2], 'height' => $position[3] );
			}

			// get the field font settings (if any)
			if ( $_POST[ $field_name . '_font_family' ] )  $field['font']['family'] = $_POST[ $field_name . '_font_family' ];
			if ( $_POST[ $field_name . '_font_size' ] )    $field['font']['size']   = $_POST[ $field_name . '_font_size' ];
			if ( isset( $_POST[ $field_name . '_font_style_b' ] ) && $_POST[ $field_name . '_font_style_b' ] ) $field['font']['style']  = 'B';
			if ( isset( $_POST[ $field_name . '_font_style_i' ] ) && $_POST[ $field_name . '_font_style_i' ] ) $field['font']['style'] .= 'I';
			if ( $_POST[ $field_name . '_font_color' ] )   $field['font']['color']  = $_POST[ $field_name . '_font_color' ];

			// get the text alignment
			if ( isset( $_POST[ $field_name . '_text_align' ] ) ) $field['text_align'] = $_POST[ $field_name . '_text_align' ];

			// special cases
			switch ( $field_name ) {
				case '_expiration_date':
					$field['days_to_expiry'] = $_POST['_days_to_expiry'] ? absint( $_POST['_days_to_expiry'] ) : '';
				break;

				case '_recipient_name':
					$field['label']       = $_POST['_recipient_name_label'];  // this is translated upon display
					$field['type']        = 'user_input';
					$field['input_type']  = 'text';
					$field['max_length']  = $_POST['_recipient_name_max_length'] ? absint( $_POST['_recipient_name_max_length'] ) : '';
					$field['is_enabled']  = isset( $_POST['_recipient_name_is_enabled'] ) && 'yes' == $_POST['_recipient_name_is_enabled'] ? 'yes' : 'no';
					$field['is_required'] = isset( $_POST['_recipient_name_is_required'] ) && 'yes' == $_POST['_recipient_name_is_required'] ? 'yes' : 'no';
				break;

				case '_message':
					$field['label']       = $_POST['_message_label']; // this is translated upon display
					$field['type']        = 'user_input';
					$field['input_type']  = 'textarea';
					$field['max_length']  = $_POST['_message_max_length'] ? absint( $_POST['_message_max_length'] ) : '';
					$field['is_enabled']  = isset( $_POST['_message_is_enabled'] ) && 'yes' == $_POST['_message_is_enabled'] ? 'yes' : 'no';
					$field['is_required'] = isset( $_POST['_message_is_required'] ) && 'yes' == $_POST['_message_is_required'] && ( $field['position'] || 'yes' == $field['is_enabled'] ) ? 'yes' : 'no';
				break;

				case '_recipient_email':
					$field['label']       = $_POST['_recipient_email_label']; // this is translated upon display
					$field['type']        = 'user_input';
					$field['input_type']  = 'text';
					$field['is_enabled']  = isset( $_POST['_recipient_email_is_enabled'] ) && 'yes' == $_POST['_recipient_email_is_enabled'] ? 'yes' : 'no';
					$field['is_required'] = isset( $_POST['_recipient_email_is_required'] ) && 'yes' == $_POST['_recipient_email_is_required'] && ( $field['position'] || 'yes' == $field['is_enabled'] ) ? 'yes' : 'no';
				break;
			}

			// cut off the leading '_' to create the field name
			$fields[ ltrim( $field_name, '_' ) ] = $field;
		}

		update_post_meta( $post_id, '_voucher_fields', $fields );
	}


}
