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

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Voucher Recipient Email
 *
 * Voucher recipient emails are sent to any voucher recipient email addresses
 * that were provided by the customer when configuring the voucher/adding to
 * cart
 *
 * @since 1.2
 */
class WC_PDF_Product_Vouchers_Email_Voucher_Recipient extends WC_Email {


	/** @var string optional voucher recipient message */
	private $message;

	/** @var string optional voucher recipient name */
	private $recipient_name;

	/** @var string heading for email containing multiple vouchers */
	private $heading_multiple;

	/** @var string subject for email containing multiple vouchers */
	private $subject_multiple;


	/**
	 * Constructor
	 *
	 * @since 1.2
	 */
	public function __construct() {

		$this->id          = 'wc_pdf_product_vouchers_voucher_recipient';
		$this->title       = __( 'Voucher Recipient', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		$this->description = __( 'Sent to a voucher recipient email address provided by the customer when adding a voucher product to the cart.', WC_PDF_Product_Vouchers::TEXT_DOMAIN );

		$this->heading     = __( 'You have received a voucher', WC_PDF_Product_Vouchers::TEXT_DOMAIN );
		$this->subject     = __( 'You have received a voucher from {billing_first_name} {billing_last_name}', WC_PDF_Product_Vouchers::TEXT_DOMAIN );

		$this->template_html  = 'emails/voucher-recipient.php';
		$this->template_plain = 'emails/plain/voucher-recipient.php';

		$this->template_base  = wc_pdf_product_vouchers()->get_plugin_path() . '/templates/';

		// Triggers for this email
		if ( 'yes' == get_option( 'woocommerce_downloads_grant_access_after_payment' ) ) {
			add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
		}
		add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ) );

		// Other settings
		$this->heading_multiple = $this->get_option( 'heading_multiple', __( 'You have received vouchers', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) );
		$this->subject_multiple = $this->get_option( 'subject_multiple', __( 'You have received vouchers from {billing_first_name} {billing_last_name}', WC_PDF_Product_Vouchers::TEXT_DOMAIN ) );

		parent::__construct();
	}


	/**
	 * Dispatch the email(s)
	 *
	 * @since 1.2
	 * @param int $order_id order identifier
	 */
	public function trigger( $order_id ) {

		// nothingtodohere
		if ( ! $order_id || ! $this->is_enabled() ) {
			return;
		}

		// only dispatch the voucher recipient email once, unless we're being called from the Voucher Recipient email order action
		if ( get_post_meta( $order_id, '_wc_pdf_product_vouchers_voucher_recipient_email_sent', true ) &&
			! ( isset( $_POST['wc_order_action'] ) && 'send_email_wc_pdf_product_vouchers_voucher_recipient' == $_POST['wc_order_action'] ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$this->find[] = '{billing_first_name}';
		$this->replace[] = $order->billing_first_name;

		$this->find[] = '{billing_last_name}';
		$this->replace[] = $order->billing_last_name;

		// foreach voucher item in this order, if it contains a recipient email,
		//  add the voucher to those being sent to that recipient.
		// foreach voucher recipient, send an email with any and all vouchers

		$recipient_emails = array();

		$order_items = $order->get_items();

		if ( count( $order_items ) > 0 ) {
			foreach ( $order_items as $order_item_id => $item ) {

				if ( $item['product_id'] > 0 && isset( $item['voucher_id'] ) && $item['voucher_id'] ) {

					$voucher = new WC_Voucher( $item['voucher_id'], $order->id, $item, $order_item_id );

					if ( $voucher->get_recipient_email() && $voucher->file_exists( wc_pdf_product_vouchers()->get_uploads_path() ) ) {

						if ( ! isset( $recipient_emails[ $voucher->get_recipient_email() ] ) ) {
							$recipient_emails[ $voucher->get_recipient_email() ] = array( 'count' => 0, 'message' => '', 'recipient_name' => $voucher->get_recipient_name() );
						}

						$recipient_emails[ $voucher->get_recipient_email() ]['count']++;

						// message to the recipient?
						if ( $voucher->get_message() ) {

							if ( '' === $recipient_emails[ $voucher->get_recipient_email() ]['message'] ) {
								$recipient_emails[ $voucher->get_recipient_email() ]['message'] = $voucher->get_message();
							} elseif ( $recipient_emails[ $voucher->get_recipient_email() ]['message'] != $voucher->get_message() ) {

								// guard against the admitedly edge case of multiple vouchers with different messages
								//  being sent to the same recipient, by just not displaying a message.  Cause it would
								//  probably look odd to have a bunch of different messages in the same email
								$recipient_emails[ $voucher->get_recipient_email() ]['message'] = null;
							}

						}
					}

				}
			}
		}

		foreach ( $recipient_emails as $recipient_email => $data ) {

			$this->object         = array( 'order' => $order, 'recipient_email' => $recipient_email, 'voucher_count' => $data['count'] );
			$this->message        = $data['message'];
			$this->recipient_name = $data['recipient_name'];
			$this->recipient      = $recipient_email;

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		// record the fact that the vouchers have been sent
		update_post_meta( $order_id, '_wc_pdf_product_vouchers_voucher_recipient_email_sent', true );
	}


	/**
	 * Gets the email subject
	 *
	 * @since 1.2
	 * @see WC_Email::get_subject()
	 * @return string email subject
	 */
	public function get_subject() {
		if ( $this->object['voucher_count'] == 1 ) {
			return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject_multiple ), $this->object );
		}
	}


	/**
	 * Gets the email heading
	 *
	 * @since 1.2
	 * @see WC_Email::get_heading()
	 * @return string email heading
	 */
	public function get_heading() {
		if ( $this->object['voucher_count'] == 1 ) {
			return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading_multiple ), $this->object );
		}
	}


	/**
	 * Gets the email HTML content
	 *
	 * @since 1.2
	 * @return string the email HTML content
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'order'          => $this->object,
				'email_heading'  => $this->get_heading(),
				'voucher_count'  => $this->object['voucher_count'],
				'message'        => $this->message,
				'recipient_name' => $this->recipient_name,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}


	/**
	 * Gets the email plain content
	 *
	 * @since 1.2
	 * @return string the email plain content
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'order'          => $this->object,
				'email_heading'  => $this->get_heading(),
				'voucher_count'  => $this->object['voucher_count'],
				'message'        => $this->message,
				'recipient_name' => $this->recipient_name,
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 1.2
	 */
	public function init_form_fields() {

		$this->form_fields = array(

			'enabled' => array(
				'title'   => __( 'Enable/Disable', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'default' => 'yes',
			),

			'subject' => array(
				'title'       => __( 'Subject', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),

			'subject_multiple' => array(
				'title'       => __( 'Subject Multiple', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line when the email contains more than one voucher. Leave blank to use the default subject: <code>%s</code>.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $this->subject_multiple ),
				'placeholder' => '',
				'default'     => '',
			),

			'heading' => array(
				'title'       => __( 'Email Heading', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),

			'heading_multiple' => array(
				'title'       => __( 'Email Heading Multiple', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification when the email contains more than one voucher. Leave blank to use the default heading: <code>%s</code>.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ), $this->heading_multiple ),
				'placeholder' => '',
				'default'     => '',
			),

			'email_type' => array(
				'title'       => __( 'Email type', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options' => array(
					'plain'     => __( 'Plain text', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
					'html'      => __( 'HTML', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
					'multipart' => __( 'Multipart', WC_PDF_Product_Vouchers::TEXT_DOMAIN ),
				),
			),
		);
	}
}
