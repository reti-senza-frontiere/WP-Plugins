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
 * WooCommerce voucher
 *
 * The WooCommerce PDF Product Vouchers class gets voucher data from storage.  This class
 * represents two different concepts:  a "voucher template" and a "product voucher".
 * The voucher template can be thought of as the blueprint for a voucher, it
 * contains everything needed to create a voucher (one or more images, the
 * coordinates for a number of fields, expiry days, etc).  The "product voucher"
 * is an instantiation of a voucher template, it also contains the voucher data
 * from the order item meta.
 *
 * @since 1.0
 */
class WC_Voucher {

	/**
	 * @var int voucher post_id
	 */
	public $id;

	private $voucher_custom_fields;
	/**
	 * @var string voucher name (post title)
	 */
	private $name;
	/**
	 * @var string Default voucher font color
	 */
	private $voucher_font_color;
	/**
	 * @var int Default voucher font size
	 */
	private $voucher_font_size;
	/**
	 * @var string Default voucher font style (one of 'B', 'I' or 'BI')
	 */
	private $voucher_font_style;
	/**
	 * @var string Default voucher font family
	 */
	private $voucher_font_family;
	/**
	 * @var string Default voucher text align, one of 'L', 'C', 'R'
	 */
	private $voucher_text_align = null;
	/**
	 * @var array Voucher fields (text which is written on top of the voucher image to create the final pdf)
	 */
	public $voucher_fields;
	/**
	 * @var array of voucher image ids (attachment ids)
	 */
	private $image_ids;
	/**
	 * @var int Voucher main image id (attachment id)
	 */
	public $image_id;
	/**
	 * @var array Optional voucher 'reverse' or second page image
	 */
	private $additional_image_ids;

	/** Product Voucher Fields ******************************************************/

	/**
	 * @var int Order id when this is a product voucher
	 */
	private $order_id;
	/**
	 * @var WC_Order Order object when this is a product voucher
	 */
	private $order;
	/**
	 * @var array Array of item data when this is a product voucher
	 */
	private $item;

	/** @var int optional order item id */
	private $item_id;

	/**
	 * @var string Product voucher number
	 */
	public $voucher_number;
	/**
	 * @var string product voucher price
	 */
	public $voucher_product_price;
	/**
	 * @var int Expiration date of this product voucher, mesured in number of seconds since the Unix Epoch
	 */
	public $expiration_date;

	/**
	 * @var string The product name for this voucher
	 */
	public $product_name;

	/**
	 * @var string The product sku for this voucher
	 */
	public $product_sku;

	/**
	 * @var string The recipient name for this voucher
	 */
	public $recipient_name;

	/**
	 * @var string The recipient message for this voucher
	 */
	public $message;

	/**
	 * @var string The optional recipient email for this voucher
	 */
	public $recipient_email;


	/**
	 * Construct voucher with $id
	 *
	 * @since 1.0
	 * @param int $id Voucher id
	 * @param int $order_id optional order id when this is a product voucher
	 * @param array $item optional item data when this is a product voucher
	 * @param int $order_item_id optional item id
	 */
	function __construct( $id, $order_id = null, $item = array(), $order_item_id = null ) {

		$this->id       = (int) $id;
		$this->order_id = $order_id;
		$this->item     = $item;
		$this->item_id  = $order_item_id;

		// load data from the item if this is a product voucher
		if ( $this->item ) {
			$this->voucher_number = $item['voucher_number'];
			if ( isset( $item['voucher_expiration'] ) ) {
				$this->expiration_date = strtotime( $item['voucher_expiration'] );
			}
		}

		$this->voucher_custom_fields = get_post_custom( $this->id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'image_ids'            => array(),
			'additional_image_ids' => array(),
			'voucher_font_color'   => '',
			'voucher_font_size'    => '',
			'voucher_font_style'   => '',
			'voucher_font_family'  => '',
			'voucher_text_align'   => '',
			'voucher_fields'       => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {

			// set value from db (unserialized if needed) or use default
			if ( isset( $this->voucher_custom_fields[ '_' . $key ][0] ) && '' !== $this->voucher_custom_fields[ '_' . $key ][0] ) {
				$value = maybe_unserialize( $this->voucher_custom_fields[ '_' . $key ][0] );
			} else {
				$value = $default;
			}

			$this->$key = $value;

		}

		// set the voucher main template image, if any
		if ( count( $this->image_ids ) > 0 ) {
			$this->image_id = $this->image_ids[0];
		}

		// allow custom fields to be added
		$this->voucher_fields = apply_filters( 'wc_pdf_product_vouchers_voucher_fields', $this->voucher_fields, $this );

		// load order item meta values for any custom fields
		foreach ( $this->voucher_fields as $field_name => $field ) {

			// one of the non-core fields
			if ( ! in_array( $field_name, array( 'message', 'recipient_name', 'recipient_email', 'expiration_date', 'voucher_product_price', 'voucher_number', 'product_sku', 'product_name' ) ) ) {

				$value = '';

				if ( isset( $item[ $field_name ] ) ) {
					$value = $item[ $field_name ];
				}

				$this->$field_name = $value;

			}

		}

		/**
		 * Filter the voucher image DPI
		 *
		 * @since 2.3.0
		 * @param int The voucher image DPI
		 */
		$this->dpi = apply_filters( 'wc_pdf_product_vouchers_voucher_image_dpi', 72, $this );;

	}


	/** Getter/Setter methods ******************************************************/


	/**
	 * Returns true if this item voucher is completely redeemed
	 *
	 * @since 1.0
	 * @return boolean|null true if the voucher is completely redeemd, false
	 *         otherwise.  null is returned if the redeem state can't be determined
	 */
	public function is_redeemed() {

		if ( $this->item && isset( $this->item['voucher_redeem'] ) ) {

			$voucher_redeem = maybe_unserialize( $this->item['voucher_redeem'] );

			foreach ( $voucher_redeem as $date ) {
				if ( ! $date ) return false;
			}

			return true;
		}

		// unknown
		return null;
	}


	/**
	 * Returns the formatted product voucher number, which consists of the
	 * order number - voucher number
	 *
	 * @since 1.0
	 * @return string if a voucher number has been created, or null otherwise
	 */
	public function get_voucher_number() {
		// normally the order object should be available, but check for it in order to support the voucher preview functionality
		$voucher_number = $this->voucher_number;

		if ( $this->order_id ) {
			$voucher_number = ltrim( $this->get_order()->get_order_number(), _x( '#', 'hash before order number', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) ) . '-' . $voucher_number;
		}

		return apply_filters( 'woocommerce_voucher_number', $voucher_number, $this );
	}


	/**
	 * Get the number of days this voucher is valid for
	 *
	 * @since 1.0
	 * @return int expiry days
	 */
	public function get_expiry() {

		$expiry = '';

		if ( isset( $this->voucher_fields['expiration_date']['days_to_expiry'] ) ) {
			$expiry = $this->voucher_fields['expiration_date']['days_to_expiry'];
		}

		/**
		 * Filter the number of days this voucher is valid for
		 *
		 * @since 2.1.4
		 * @param int $expiry expiry days
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_expiry', $expiry, $this );
	}


	/**
	 * Set the expiration date for this product voucher
	 *
	 * @since 1.0
	 * @param int $expiration_date expiration date of this product voucher,
	 *        mesured in number of seconds since the Unix Epoch
	 */
	public function set_expiration_date( $expiration_date ) {
		$this->expiration_date = $expiration_date;
	}


	/**
	 * Get the expiration date (if any) in the user-defined WordPress format,
	 * or the empty string.  Product voucher method.
	 *
	 * @since 1.0
	 * @return string formatted expiration date, if any, otherwise the empty string
	 */
	public function get_formatted_expiration_date() {

		$formatted_expiration_date = '';

		if ( isset( $this->expiration_date ) && $this->expiration_date ) {

			if ( is_int( $this->expiration_date ) ) {
				$formatted_expiration_date = date_i18n( wc_date_format(), $this->expiration_date );
			} else {
				$formatted_expiration_date = $this->expiration_date;
			}
		}

		/**
		 * Filter the formatted expiration date
		 *
		 * @since 2.1.4
		 * @param string $formatted_expiration_date formatted expiration date
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_formatted_expiration_date', $formatted_expiration_date, $this );
	}


	/**
	 * Get the recipient name if any for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher recipient name or empty string
	 */
	public function get_recipient_name() {

		if ( ! isset( $this->recipient_name ) ) {
			$this->recipient_name = $this->get_item_meta_value( $this->voucher_fields['recipient_name']['label'] );
		}

		/**
		 * Filter the recipient name
		 *
		 * @since 2.1.4
		 * @param string $recipient_name the recipient name
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_recipient_name', $this->recipient_name, $this );
	}


	/**
	 * Get the recipient email if any for this product voucher
	 *
	 * @since 1.2
	 * @return string voucher recipient email or empty string
	 */
	public function get_recipient_email() {

		if ( ! isset( $this->recipient_email ) ) {
			$this->recipient_email = $this->get_item_meta_value( $this->voucher_fields['recipient_email']['label'] );
		}

		/**
		 * Filter the recipient email
		 *
		 * @since 2.1.4
		 * @param string $recipient_email the recipient email
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_recipient_email', $this->recipient_email, $this );
	}


	/**
	 * Get the voucher message if any for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher message or empty string
	 */
	public function get_message() {

		if ( ! isset( $this->message ) ) {
			$this->message = $this->get_item_meta_value( $this->voucher_fields['message']['label'] );
		}

		/**
		 * Filter the voucher message
		 *
		 * @since 2.1.4
		 * @param string $message the voucher message
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_message', $this->message, $this );
	}


	/**
	 * Get the product name, if available
	 *
	 * @since 1.0
	 * @return string product name if this is a product voucher, or the empty string
	 */
	public function get_product_name() {

		if ( ! isset( $this->product_name ) ) {
			$this->product_name = html_entity_decode( isset( $this->item['name'] ) ? $this->item['name'] : '' );
		}

		/**
		 * Filter the product name
		 *
		 * @since 2.1.4
		 * @param string $product_name the voucher message
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_product_name', $this->product_name, $this );
	}


	/**
	 * Get the product sku, if available
	 *
	 * @since 1.0
	 * @return string product sku if this is a product voucher, or the empty string
	 */
	public function get_product_sku() {

		if ( ! isset( $this->product_sku ) ) {
			if ( $this->order_id && $this->item ) {
				// get product (this works for simple and variable products)
				$order = $this->get_order();
				$product = $order->get_product_from_item( $this->item );

				$this->product_sku = $product->get_sku();
			} else {
				$this->product_sku = '';
			}
		}

		/**
		 * Filter the product sku
		 *
		 * @since 2.1.4
		 * @param string $product_sku the product sku if this is a product voucher, or the empty string
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_product_sku', $this->product_sku, $this );
	}


	/**
	 * Gets the product price, if available
	 *
	 * @since 2.0
	 * @return string formatted product price
	 */
	public function get_product_price() {

		$product_price = '';

		if ( $this->get_order() && $this->item ) {
			$product_price = $this->get_order()->get_line_subtotal( $this->item, 'incl' == get_option( 'woocommerce_tax_display_shop' ) );
		}

		/**
		 * Filter the product price
		 *
		 * @since 2.1.4
		 * @param string $product_price formatted product price or the empty string
		 * @param WC_Voucher $this, voucher instance
		 */
		return apply_filters( 'wc_pdf_product_vouchers_get_product_price', $product_price, $this );
	}


	/**
	 * Returns $price formatted with currency symbol and decimals, as
	 * configured within WooCommerce settings
	 *
	 * Annoyingly, WC doesn't seem to offer a function to format a price string
	 * without HTML tags, so this method is adapted from the core wc_price()
	 * function.
	 *
	 * @since 2.0
	 * @see wc_price()
	 * @param string $price the price
	 * @return string price formatted
	 */
	private function wc_price( $price ) {

		if ( 0 == $price ) {
			return __( 'Free!', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		}

		$return          = '';
		$num_decimals    = absint( get_option( 'woocommerce_price_num_decimals' ) );
		$currency_pos    = get_option( 'woocommerce_currency_pos' );
		// Don't ask me how, but somehow this works for stuff like '&euro;' when nothing else would
		$currency_symbol = utf8_encode( iconv( 'UTF-8', 'windows-1252', html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, "UTF-8" ) ) );
		$decimal_sep     = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
		$thousands_sep   = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );

		$price           = apply_filters( 'raw_woocommerce_price', floatval( $price ) );
		$price           = apply_filters( 'formatted_woocommerce_price', number_format( $price, $num_decimals, $decimal_sep, $thousands_sep ), $price, $num_decimals, $decimal_sep, $thousands_sep );

		if ( apply_filters( 'woocommerce_price_trim_zeros', true ) && $num_decimals > 0 ) {
			$price = wc_trim_zeros( $price );
		}

		$return = sprintf( str_replace( '&nbsp;', ' ', get_woocommerce_price_format() ), $currency_symbol, $price );

		return $return;
	}


	/**
	 * Gets the main voucher image, or a placeholder
	 *
	 * @since 1.0
	 * @return string voucher primary img tag
	 */
	public function get_image( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {

		$image = '';

		if ( has_post_thumbnail( $this->id ) ) {
			$image = get_the_post_thumbnail( $this->id, $size );
		} else {
			$image = '<img src="' . wc_placeholder_img_src() . '" alt="Placeholder" width="' . WC_PDF_Product_Vouchers::VOUCHER_IMAGE_THUMB_WIDTH . '" />';
		}

		return $image;
	}


	/**
	 * Gets the voucher image id: the selected image id if this is a voucher product
	 * otherwise the voucher template primary image id
	 *
	 * @since 1.0
	 * @return int voucher image id
	 */
	public function get_image_id() {
		// if this is a voucher product, return the selected image id
		if ( isset( $this->item['voucher_image_id'] ) ) return $this->item['voucher_image_id'];

		// otherwise return the template primary image id
		return $this->image_id;
	}


	/**
	 * Get the all available images for this voucher
	 *
	 * @since 1.0
	 * @return array of img tags
	 */
	public function get_image_urls( $size = 'wc-pdf-product-vouchers-voucher-thumb' ) {

		$images = array();

		foreach ( $this->image_ids as $image_id ) {
			$image_src = wp_get_attachment_url( $image_id );
			$thumb_src = wp_get_attachment_image_src( $image_id, $size );

			if ( $image_src ) {
				$images[ $image_id ]['image'] = $image_src;
				$images[ $image_id ]['thumb'] = $thumb_src[0];
			}
		}

		return $images;
	}


	/**
	 * Returns any user-supplied voucher field data in an associative array of
	 * data display name to value.  These values are taken from the order item
	 * meta
	 *
	 * @since 1.0
	 * @param int $cut_textarea the number of characters to limit a returned
	 *        textarea value to.  0 indicates to return the entire value regardless
	 *        of length
	 *
	 * @return array associative array of input field name to value
	 */
	public function get_user_input_data( $limit_textarea = 25 ) {
		$data = array();

		// get any meta data from the order item meta

		foreach ( $this->voucher_fields as $field ) {
			if ( 'user_input' == $field['type'] ) {
				foreach ( $this->item as $meta_name => $meta_value ) {
					if ( __( $field['label'], WC_PDF_Product_Vouchers::TEXT_DOMAIN ) == $meta_name ) {

						// limit the textarea value?
						if ( 'textarea' == $field['input_type'] && $limit_textarea && strlen( $meta_value ) > $limit_textarea ) {
							list( $value ) = explode( "\n", wordwrap( $meta_value, $limit_textarea, "\n" ) );
							$meta_value = $value . '...';
						}

						$data[ $field['label'] ] = $meta_value;
						break;
					}
				}
			}
		}

		return $data;
	}


	/**
	 * Returns the type for the field identified by name.
	 *
	 * @since 1.2
	 * @param string $name the field name
	 * @return string the field type, one of 'property' or 'user_input'
	 */
	public function get_field_type( $name ) {
		return isset( $this->voucher_fields[ $name ]['type'] ) ? $this->voucher_fields[ $name ]['type'] : null;
	}


	/**
	 * Returns the label for the field identified by name.
	 *
	 * @since 1.2
	 * @param string $name the field name
	 * @return string the field label
	 */
	public function get_field_label( $name ) {
		return isset( $this->voucher_fields[ $name ]['label'] ) ? $this->voucher_fields[ $name ]['label'] : null;
	}


	/**
	 * Returns true if the field identified by name is a 'user_input' type
	 *
	 * @since 1.2
	 * @param string $name the field name
	 * @return boolean true if the field identified by $name is a 'user_input' type
	 */
	public function is_user_input_type_field( $name ) {
		return 'user_input' == $this->get_field_type( $name );
	}


	/**
	 * Return an array of user-input voucher fields
	 *
	 * @since 1.0
	 * @return array of user-input voucher fields
	 */
	public function get_user_input_voucher_fields() {

		$fields = array();
		foreach ( $this->voucher_fields as $name => $voucher_field ) {

			if ( 'user_input' == $voucher_field['type'] && ( ! empty( $voucher_field['position'] ) || ( isset( $voucher_field['is_enabled'] ) && 'yes' == $voucher_field['is_enabled'] ) ) ) {
				$voucher_field['name'] = $name;
				$fields[ (int) $voucher_field['order'] ] = $voucher_field;
			}
		}
		// make sure they're ordered properly (ie for the frontend)
		ksort( $fields );

		return $fields;
	}


	/**
	 * Get the maximum length for the user input field named $name.  This is
	 * enforced on the frontend so that the voucher text doesn't overrun the
	 * field area
	 *
	 * @since 1.0
	 * @param string $name the field name
	 * @return int the max length of the field, or empty string if there is no
	 *         limit
	 */
	public function get_user_input_field_max_length( $name ) {

		if ( isset( $this->voucher_fields[ $name ]['max_length'] ) ) {
			return $this->voucher_fields[ $name ]['max_length'];
		}

		return '';
	}


	/**
	 * Returns true if the user input field named $name is required, false otherwise
	 *
	 * @since 1.1
	 * @param string $name the field name
	 * @return boolean true if $name is required, false otherwise
	 */
	public function user_input_field_is_required( $name ) {

		if ( isset( $this->voucher_fields[ $name ]['is_required'] ) ) {
			return 'yes' == $this->voucher_fields[ $name ]['is_required'];
		}

		return '';
	}


	/**
	 * Returns true if the user input field named $name is enabled, regardless
	 * of whether it is printed to the voucher
	 *
	 * @since 2.0.2
	 * @param string $name the field name
	 * @return boolean true if $name is enabled, false otherwise
	 */
	public function user_input_field_is_enabled( $name ) {

		if ( isset( $this->voucher_fields[ $name ]['is_enabled'] ) ) {
			return 'yes' == $this->voucher_fields[ $name ]['is_enabled'];
		}

		return '';
	}


	/**
	 * Returns true if this voucher has any user input fields that are required
	 *
	 * @since 1.1
	 * @return boolean true if there is a required field
	 */
	public function has_required_input_fields() {

		foreach ( $this->voucher_fields as $field ) {
			if ( isset( $field['is_required'] ) && 'yes' == $field['is_required'] ) return true;
		}

		return false;
	}


	/**
	 * Returns the font definition for the field $field_name, using the voucher
	 * font defaults if not provided
	 *
	 * @since 1.0
	 * @param string $field_name name of the field
	 *
	 * @return array with optional members 'family', 'size', 'style', 'color'
	 */
	public function get_field_font( $field_name ) {

		$default_font = array( 'family' => $this->voucher_font_family, 'size' => $this->voucher_font_size, 'color' => $this->voucher_font_color );

		// only use the default font style if there is no specific font family set
		if ( ! isset( $this->voucher_fields[ $field_name ]['font']['family'] ) || ! $this->voucher_fields[ $field_name ]['font']['family'] ) {
			$default_font['style'] = $this->voucher_font_style;
		}

		// get rid of any empty fields so the defaults can take precedence
		foreach ( $this->voucher_fields[ $field_name ]['font'] as $key => $value ) {
			if ( ! $value ) unset( $this->voucher_fields[ $field_name ]['font'][ $key ] );
		}

		$merged = array_merge( $default_font, $this->voucher_fields[ $field_name ]['font'] );

		// handle style specially
		if ( ! isset( $merged['style'] ) ) $merged['style'] = '';

		return $merged;
	}


	/**
	 * Returns the field position for the field $field_name
	 *
	 * @since 1.0
	 * @return array associative array with position members 'x1', 'y1', 'width'
	 *         and 'height'
	 */
	public function get_field_position( $field_name ) {

		return isset( $this->voucher_fields[ $field_name ]['position'] ) ? $this->voucher_fields[ $field_name ]['position'] : array();
	}


	/**
	 * Returns the file name for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher pdf file name
	 */
	public function get_voucher_filename() {
		// we want the unadulterated voucher number so as to avoid file name clashes
		return apply_filters( 'wc_pdf_product_vouchers_voucher_filename', 'voucher-' . $this->strip_unicode( $this->get_voucher_number() ) . '.pdf', $this );
	}


	/**
	 * Return $str with any unicode characters removed.  Ogone seems to totally
	 * barf on unicode characters passed to the processor, so for now we'll
	 * just strip them out as needed.  Down the line it might be worth trying
	 * to get an answer from them on whether this should be possible.
	 *
	 * @since 2.0.3
	 * @param string $str possible unicode-containing string
	 * @return string free of all dangerous unicode characters!
	 */
	private function strip_unicode( $str ) {
		return preg_replace( '/[^(\x20-\x7F)]*/', '', $str );
	}


	/**
	 * Returns the relative voucher pdf file path for this product voucher
	 *
	 * @since 1.0
	 * @return string voucher pdf file path
	 */
	public function get_voucher_path() {
		// hash the pdfs by the least 3 sig digits of the order id, this will give us no more than 1000 files per directory until we hit 1 million pdfs generated
		return str_pad( substr( $this->voucher_number, -3 ), 3, 0, STR_PAD_LEFT );
	}


	/**
	 * Returns the full path and voucher file name
	 *
	 * @since 1.2
	 * @param string $path path to the voucher directory
	 * @return string $path plus voucher file
	 */
	public function get_voucher_full_filename( $path ) {

		return $path . '/' . $this->get_voucher_path() . '/' . $this->get_voucher_filename();
	}


	/**
	 * Returns true if the voucher file has been generated and exists
	 *
	 * @since 1.2
	 * @param string $path path to the voucher directory
	 * @return boolean true if the voucher file exists
	 */
	public function file_exists( $path ) {

		return file_exists( $this->get_voucher_full_filename( $path ) );
	}


	/**
	 * Convert voucher file path to url
	 *
	 * @since 2.2.1
	 * @param string $path path to the voucher
	 * @return string $url voucher download url
	 */
	public function convert_path_to_url( $path ) {

		wc_pdf_product_vouchers()->log( $path );

		$wp_uploads     = wp_upload_dir();
		$wp_uploads_dir = $wp_uploads['basedir'];
		$wp_uploads_url = $wp_uploads['baseurl'];

		// Replace uploads dir with uploads url
		$url = str_replace( $wp_uploads_dir, $wp_uploads_url, $path );

		return $url;
	}


	/**
	 * Get the order that this voucher is attached to, when it is a product voucher.
	 *
	 * @since 1.0
	 * @return WC_Order the order, or null
	 */
	public function get_order() {

		if ( $this->order ) {
			return $this->order;
		}

		if ( $this->order_id ) {
			$this->order = wc_get_order( $this->order_id );
			return $this->order;
		}

		return null;
	}


	/**
	 * Returns the item associated with this voucher, if any is available
	 *
	 * @since 1.1.1
	 * @return array order item
	 */
	public function get_item() {

		if ( $this->item ) {
			return $this->item;
		}

		return null;
	}


	/**
	 * Returns the item id associatd with this voucher, if any is avaialble
	 *
	 * @since 1.2
	 * @return int item id
	 */
	public function get_item_id() {
		return $this->item_id;
	}


	/** PDF Generation methods ******************************************************/


	/**
	 * Generate and save or stream a PDF file for this product voucher
	 *
	 * @since 1.0
	 * @param string $path optional absolute path to the voucher directory, if
	 *        not supplied the PDF will be streamed as a downloadable file (used
	 *        for admin previewing of the PDF)
	 *
	 * @return mixed nothing if a $path is supplied, otherwise a PDF download
	 * @throws Exception if the voucher image is not available
	 */
	public function generate_pdf( $path = '' ) {

		// include the pdf library
		define('FPDF_FONTPATH', wc_pdf_product_vouchers()->get_plugin_path() . '/lib/fpdf/font');
		require_once( wc_pdf_product_vouchers()->get_plugin_path() . '/lib/fpdf/fpdf.php' );

		$upload_dir = wp_upload_dir();

		$image = wp_get_attachment_metadata( $this->get_image_id() );

		// make sure the image hasn't been deleted through the media editor
		if ( ! $image ) {
			throw new Exception( __( "Voucher image not found", WC_PDF_Product_Vouchers::TEXT_DOMAIN ) );
		}

		// make sure the file exists and is readable
		if ( ! is_readable( $upload_dir['basedir'] . '/' . $image['file'] ) ) {
			throw new Exception( sprintf( __( "Voucher image file missing or not readable: %s", WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $upload_dir['basedir'] . '/' . $image['file'] ) );
		}

		// determine orientation: landscape or portrait
		if ( $image['width'] > $image['height'] ) {
			$orientation = 'L';
		} else {
			$orientation = "P";
		}

		// get the width and height in points
		$width_pt  = $this->convert_pixels_to_points( $image['width'] );
		$height_pt = $this->convert_pixels_to_points( $image['height'] );

		// Create the pdf
		// When writing text to a Cell, the text is vertically-aligned in the middle
		$fpdf = new FPDF( $orientation, 'pt', array( $width_pt, $height_pt ) );
		$fpdf->AddPage();
		$fpdf->SetAutoPageBreak( false );

		// set the voucher image
		$fpdf->Image( $upload_dir['basedir'] . '/' . $image['file'], 0, 0, $width_pt, $height_pt );

		// this is useful for displaying the text cell borders when debugging the PDF layout,
		//  though keep in mind that we translate the box position to align the text to bottom
		//  edge of what the user selected, so if you want to see the originally selected box,
		//  display that prior to the translation
		$show_border = 0;

		foreach ( $this->voucher_fields as $field_name => $field ) {
			switch ( $field_name ) {
				case 'message':
					// voucher message text, this is multi-line, so it's handled specially
					$this->textarea_field( $fpdf, 'message', $this->get_message(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'product_name':
					// product name (allow optional wrapping)
					if ( apply_filters( 'wc_pdf_product_vouchers_product_name_multi_line', false, $this ) ) {
						$this->textarea_field( $fpdf, 'product_name', strtoupper($this->get_product_name()), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
					} else {
						$this->text_field( $fpdf, 'product_name', strtoupper($this->get_product_name()), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
					}
				break;

				case 'product_sku':
					// product sku
					$this->text_field( $fpdf, 'product_sku', $this->get_product_sku(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'recipient_name':
					// recepient name
					$this->text_field( $fpdf, 'recipient_name', $this->get_recipient_name(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'recipient_email':
					// recepient email
					$this->text_field( $fpdf, 'recipient_email', $this->get_recipient_email(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'expiration_date':
					// expiry date
					$this->text_field( $fpdf, 'expiration_date', $this->get_formatted_expiration_date(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'voucher_number':
					// voucher number
					$this->text_field( $fpdf, 'voucher_number', $this->get_voucher_number(), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;

				case 'voucher_product_price':
					// voucher number
					$this->text_field( $fpdf, 'voucher_product_price', $this->wc_price( $this->get_product_price() ), $show_border, isset( $field['text_align'] ) && $field['text_align'] ? $field['text_align'] : $this->voucher_text_align );
				break;
				
				case 'qr-code':
				case 'qrcode':
				case 'qr':
					// Voucher QR-code
					require_once(wc_pdf_product_vouchers()->get_plugin_path() . '/lib/fpdf/qrcode/qrcode.class.php');
					$qrcode = new QRcode(str_replace("{X}", $this->get_voucher_number(), $this->voucher_fields[strtolower($this->$field_name)]["label"]), 'L'); // error level : L, M, Q, H
					$qrcode->disableBorder();
	//header("Content-type: text/plain");
	//print_r($this->voucher_fields[strtolower($this->$field_name)]["position"]["color"]);
	//exit();
	//$qrcode->displayPNG();
	//exit();

					$qrcode->displayFPDF(
							     $fpdf,
							     $this->voucher_fields[strtolower($this->$field_name)]["position"]["x"],
							     $this->voucher_fields[strtolower($this->$field_name)]["position"]["y"],
							     $this->voucher_fields[strtolower($this->$field_name)]["position"]["width"],
							     $this->voucher_fields[strtolower($this->$field_name)]["position"]["background"],
							     $this->voucher_fields[strtolower($this->$field_name)]["position"]["color"]
					);
				break;
				
				default:
	//header("Content-type: text/plain");
	//print $this->$field_name . ":\n";
	//print_r($this->voucher_fields);
	//print "\n\n";
	//exit();
					// TODO: allowing custom fields in this manner could lead to name clashes if they use a reserved field name.  have to deal with that later
					if ( isset( $field['multiline'] ) && $field['multiline'] ) {
						$this->textarea_field( $fpdf, $field_name, apply_filters( 'wc_pdf_product_vouchers_voucher_field_value', $this->voucher_fields[strtolower($this->$field_name)]["label"], $this, $field_name, $field ), $show_border, isset( $field['text_align'] ) ? $field['text_align'] : $this->voucher_text_align );
					} else {
						$this->text_field( $fpdf, $field_name, apply_filters( 'wc_pdf_product_vouchers_voucher_field_value', $this->voucher_fields[strtolower($this->$field_name)]["label"], $this, $field_name, $field ), $show_border, isset( $field['text_align'] ) ? $field['text_align'] : $this->voucher_text_align );
					}
				break;
			}
		}

		// has additional pages?
		foreach ( $this->additional_image_ids as $additional_image_id ) {
			$fpdf->AddPage();
			$additional_image = wp_get_attachment_metadata( $additional_image_id );
			$fpdf->Image( $upload_dir['basedir'] . '/' . $additional_image['file'],
						  0,
						  0,
						  $this->convert_pixels_to_points( $additional_image['width']  < $image['width']  ? $additional_image['width']  : $image['width'] ),
						  $this->convert_pixels_to_points( $additional_image['height'] < $image['height'] ? $additional_image['height'] : $image['height'] ) );
		}

		if ( $path ) {
			// save the pdf as a file
			$fpdf->Output( $this->get_voucher_full_filename( $path ), 'F' );
		} else {
			// download file
			$fpdf->Output( 'voucher-preview-' . $this->id . '.pdf', 'I' );
		}
	}


	/**
	 * Render a multi-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 * @param string $align optional text alignment.  Defaults to 'J' for FPDF default justification, but can also be 'L' for left-justified, 'R' for right-justified, or 'C' for center-justified
	 */
	public function textarea_field( $fpdf, $field_name, $value, $show_border, $align = 'L' ) {
		if ( $this->get_field_position( $field_name ) && $value ) {

			// enforce default
			if ( ! $align ) {
				$align = 'J';
			}

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array_map( array( $this, 'convert_pixels_to_points' ), array_values( $this->get_field_position( $field_name ) ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$font_path = "/" . strtolower($font['family'] . $font['style'] . ".php");
			$fpdf->AddFont( $font['family'], $font['style'], $font_path );
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Multicell( $w, $font['size'], utf8_decode( $value ), $show_border, $align );
		}
	}


	/**
	 * Render a single-line text field to the PDF
	 *
	 * @since 1.0
	 * @param FPDF $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed $value string or int value to display
	 * @param int $show_border a debugging/helper option to display a border
	 *        around the position for this field
	 * @param string $align optional text alignment.  Defaults to 'L' for left-justified, but can also be 'R' for right-justified or 'C' for center-justified
	 */
	public function text_field( $fpdf, $field_name, $value, $show_border, $align = 'L' ) {

		if ( $this->get_field_position( $field_name ) && $value ) {

			$font = $this->get_field_font( $field_name );

			// get the field position
			list( $x, $y, $w, $h ) = array_map( array( $this, 'convert_pixels_to_points' ), array_values( $this->get_field_position( $field_name ) ) );

			// font color
			$font['color'] = $this->hex2rgb( $font['color'] );
			$fpdf->SetTextColor( $font['color'][0], $font['color'][1], $font['color'][2] );

			// set the field text styling
			$font_path = "/" . strtolower($font['family'] . $font['style'] . ".php");
			$fpdf->AddFont( $font['family'], $font['style'], $font_path );
			$fpdf->SetFont( $font['family'], $font['style'], $font['size'] );

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y = $font['size'] > $h ? $y - ( $font['size'] - $h ) / 2 : $y + ( $h - $font['size'] ) / 2;

			// handle right/center justification, it's left-justified by default so nothing to do in that case
			if ( 'R' == $align ) {
				$x = max( 0, $x + ( $w - $fpdf->GetStringWidth( $value ) ) );
			} elseif ( 'C' == $align ) {
				$x = max( 0, $x + ( $w - $fpdf->GetStringWidth( $value ) ) / 2 );
			}

			$fpdf->setXY( $x, $y );

			// and write out the value
			$fpdf->Cell( $w, $h, utf8_decode( $value ) );  // can try iconv('UTF-8', 'windows-1252', $value); if this doesn't work correctly for accents
		}
	}


	/**
	 * Taxes a hex color code and returns the RGB components in an array
	 *
	 * @since 1.0
	 * @param string $hex hex color code, ie #EEEEEE
	 *
	 * @return array rgb components, ie array( 'EE', 'EE', 'EE' )
	 */
	public function hex2rgb( $hex ) {

		if ( ! $hex ) return '';

		$hex = str_replace( "#", "", $hex );

		if ( 3 == strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return array( $r, $g, $b );
	}


	/** Helper methods ******************************************************/


	/**
	 * Returns the value for $meta_name, or empty string
	 *
	 * @since 1.0
	 * @param string $meta_name untranslated meta name
	 *
	 * @return string value for $meta_name or empty string
	 */
	public function get_item_meta_value( $meta_name ) {

		// no item set
		if ( ! $this->item ) return '';

		// use the raw item_meta field thanks to this change https://github.com/woothemes/woocommerce/commit/913cc42c06cbd321ae2e2a5f439b4cefd6c1385e#diff-9b4164165828b26c4b7aec01c7b17884R1078
		foreach ( $this->item['item_meta'] as $name => $value ) {
			if ( __( $meta_name, WC_PDF_Product_Vouchers::TEXT_DOMAIN ) == $name ) {
				return $value[0];
			}
		}

		// not found
		return '';
	}


	/**
	 * Convert a pixel value to points
	 *
	 * @since 2.3.0
	 * @param int $pixels The pixel value
	 *
	 * @return float The point value
	 */
	public function convert_pixels_to_points( $pixels ) {

		return ( (int) $pixels * 72 ) / $this->dpi;
	}
}
