<?php
/**
 * Plugin Name: Custom Payment Gateway for iPAY
 * Plugin URI: ipayafrica.com
 * Description: iPay Payment Gateway for woocommerce.
 * Version: 1.0.0
 * Author: Infoseek Team
 * Author URI: http://infoseeksoftwaresystems.com/
 * License: GPL2
 */

// to prevent direct access to the plugin
defined('ABSPATH') or die("No script kiddies please!");

add_action( 'plugins_loaded', 'init_my5tech_iPay_payment_gateway' );
function init_my5tech_iPay_payment_gateway() {
	
	if( !class_exists( 'WC_Payment_Gateway' )) return;

	/**
	 * iPay Payment Gateway
	 *
	 * @class          WC_my5tech_Payment_Gateway_Ipay
	 * @extends        WC_Payment_Gateway
	 * @version        1.0.0
	 */
	class WC_My5tech_Payment_Gateway_Ipay extends WC_Payment_Gateway {
		/**
		*  Plugin constructor
		*/
		public function __construct(){
			/**
			* setting basic settings eg name, callback url, title etc
			*/
			$this->id                 = 'ipay';
			$this->icon               = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/logo.png'; 
			$this->has_fields         = false;
			$this->method_title       = __( 'iPay', 'woocommerce' );
			$this->method_description = __( 'Payments Made Easy' );
			$this->callback_url       = $this->ipay_callback();
			
			// load the settings
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
			$this->title            = $this->get_option( 'title' );
			$this->description      = $this->get_option( 'description' );
			$this->instructions     = $this->get_option( 'instructions', $this->description );
			$this->mer              = $this->get_option( 'mer' );
			$this->vid              = $this->get_option( 'vid' );
			$this->hsh              = $this->get_option( 'hsh' );
			$this->live             = $this->get_option( 'live' );
			$this->mpesa            = $this->get_option( 'mpesa' );
			$this->airtel           = $this->get_option( 'airtel' );
			$this->equity           = $this->get_option( 'equity' );
			$this->creditcard       = $this->get_option( 'creditcard' );
			$this -> msg['message'] = "";
			$this -> msg['class']  = "";

			// actions handling the callback:
			add_action('init', array($this, 'my5tech_callback_handler'));
			add_action( 'woocommerce_api_wc_my5tech_payment_gateway_ipay', array( &$this, 'my5tech_callback_handler' ) );
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {

			add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );

			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
			}
			add_action( 'woocommerce_receipt_ipay', array( $this, 'iPay_receipt_page' ) );
		}

		/**
		*Initialize Gateway Form Fields - Backend Settings
		*/
		public function init_form_fields() {

			$this->form_fields = array(
				'enabled' => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable iPay Payments Gateway', 'woocommerce' ),
					'default' => 'yes'
				),
				'title' => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'iPay', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					'default'     => __( 'Place order and pay using (M-PESA, Airtel Money, Kenswitch, VISA, MasterCard) <br> Powered by www.ipayafrica.com', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'instructions' => array(
					'title'       => __( 'Instructions', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
					'default'     => __( 'Place order and pay using (M-PESA, Airtel Money, Kenswitch, VISA, MasterCard) <br> Powered by www.ipayafrica.com', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'mer' => array(
					'title'       => __( 'Merchant Name', 'woocommerce' ),
					'description' => __( 'Company name', 'woocommerce' ),
					'type'        => 'text',
					'default'     => __( 'Company Name', 'woocommerce'),
					'desc_tip'    => false,
				),
				'vid' => array(
				   'title'       => __( 'Vendor ID', 'woocommerce' ),
				   'type'        => 'text',
				   'description' => __( 'Vendor ID as assigned by iPay. SET IN LOWER CASE.', 'woocommerce' ),
				   'default'     => __( 'demo', 'woocommerce' ),
				   'desc_tip'    => false,
				),
				'hsh' => array(
					'title'       => __( 'Security Key', 'woocommerce'),
					'type'        => 'password',
					'description' => __( 'Security key assigned by iPay', 'woocommerce' ),
					'default'     => __( 'demo', 'woocommerce' ),
					'desc_tip'    => false,
				),
				'live' => array(
					'title'     => __( 'Live/Demo', 'woocommerce' ),
					'type'      => 'checkbox',
					'label'     => __( 'Make iPay live', 'woocommerce' ),
					'default'   => 'no',
				),
				'mpesa' => array(
					'title'     => __( 'MPESA', 'woocommerce' ),
					'type'      => 'checkbox',
					'label'     => __( 'Turn On Mobile MPESA', 'woocommerce' ),
					'default'   => 'yes',
				),
				'airtel' => array(
					'title'     => __( 'Airtel', 'woocommerce' ),
					'type'      => 'checkbox',
					'label'     => __( 'Turn On Airtel', 'woocommerce' ),
					'default'   => 'no',
				),
				'equity' => array(
					'title'     => __( 'Equity', 'woocommerce' ),
					'type'      => 'checkbox',
					'label'     => __( 'Turn On Equity(eazzypay)', 'woocommerce' ),
					'default'   => 'no',
				),
				'creditcard' => array(
					'title'     => __( 'Credit Card Channel', 'woocommerce' ),
					'type'      => 'checkbox',
					'label'     => __( 'Turn On Credit Card Channel', 'woocommerce' ),
					'default'   => 'no',
				),
			);
		}

		/**
		 * Generates the HTML for the admin settings page
		*/
		public function admin_options(){
			/*
			 *The heading and paragraph below are the ones that appear on the backend ipay settings page
			 */
			echo '<h3>' . 'iPay Payments Gateway' . '</h3>';

			echo '<p>' . 'Payments Made Easy' . '</p>';

			echo '<table class="form-table">';

			$this->generate_settings_html( );

			echo '</table>';
		}
	
		/*
		  * iPAY redirect URL
		  * 
		  */
		 function ipay_redirect_url($order){
			 
			$redirect_url = $this->get_return_url($order);
			$redirect_url .= "&wc-api=WC_My5tech_Payment_Gateway_Ipay";	 
			return $redirect_url;
		}
		/**
		 * Receipt Page
		 **/
		public function iPay_receipt_page( $order_id ) {

			echo $this->generate_ipay_form( $order_id );

		}

		/**
		 * Function that posts the params to iPay and generates the iframe
		 */
		public function generate_ipay_form( $order_id ) {
			
			global $woocommerce;
			$order = new WC_Order ( $order_id );
			/**
			 *The checkboxes return the values 'yes' when checked and 'no' when unchecked.
			 *YES = 0 in the ipay settings and NO = 1
			 *Using if statements to set the values
			**/

			/**
			 *For the live variable, unchecked = 0, checked = 1
			 *For loop
			**/
			$mpesa = ($this->mpesa == 'yes')? 1 : 0;
			$airtel = ($this->airtel == 'yes')? 1 : 0;
			$equity = ($this->equity == 'yes')? 1 : 0;
			$creditcard =($this->creditcard == 'yes')? 1 : 0;

			if ( $this->live == 'no' ) {

				$live = 0;
			}else{
				$live = 1;
			}
			$mer = $this->mer;
			$tel = $order->billing_phone;
			
			//incase of any dashes in the telephone number the code below removes them
			$tel = str_replace("-", "", $tel);
			$tel = str_replace( array(' ', '<', '>', '&', '{', '}', '*', "+", '!', '@', '#', "$", '%', '^', '&'), "", $tel );
			$eml = $order->billing_email;
			// make ssl if needed
			//added by star
		   $live = $live;
		   $vid = $this->vid;
		   $oid = $order->id;
		   $inv = $oid;
		   $p1 = '';
		   $p2 = '';
		   $p3 = '';
		   $p4 = '';
		   $eml = $order->billing_email;
		   $curr = get_woocommerce_currency();
		   // $curr = 'KES';
		   $ttl = $order->order_total;
		   $tel = $tel;
		   $crl = '0';
		   $cst = '1';
		   $callbk = $this->callback_url;
		   $cbk = $callbk;
		   $hsh = $this->hsh;
		   //added by me
		   $redirect_url = $this->ipay_redirect_url($order);
		  
		   $datastring = $live.$oid.$inv.$ttl.$tel.$eml.$vid.$curr.$p1.$p2.$p3.$p4.$cbk.$cst.$crl;
		   $hash_string = hash_hmac('sha1', $datastring,$hsh);
		   $hash = $hash_string;
		 
			//echo strlen($hash);
			$url = "https://payments.ipayafrica.com/v3/ke?live=".$live."&oid=".$oid."&inv=".$inv."&ttl=".$ttl."&tel=".$tel."&eml=".$eml."&vid=".$vid."&curr=".$curr."&p1=".$p1."&p2=".$p2."&p3=".$p3."&p4=".$p4."&cbk=".$cbk."&cst=".$cst."&crl=".$crl."&hsh=".$hash."&mpesa=".$mpesa."&airtel=".$airtel."&creditcard=".$creditcard."&equity=".$equity."&redirect_url=".$redirect_url;//======================================POS ENDPOINT================================================

			// $items = $order->get_items();
			// //print_r($items);
			// $text = "Phone Number : ".$tel." <br><br>";

			// foreach ( $items as $item ){
				// $product_name = $item['name'];
				// $text .="Name: ".$product_name."<br>";
				// $qty =  $item['qty'];
				// $text .="Quantity: ".$qty."<br><br>";
			// }
			$ipay_args = array(
			  'live' 		=> $live,          
			  'oid' 		=> $oid,
			  'inv' 		=> $inv,
			  'amount' 		=> $ttl,
			  'tel' 		=> $tel,
			  'eml' 		=> $eml,
			  'vid' 		=> $vid,
			  'curr' 		=> $curr,
			  'p1' 			=> $p1,
			  'p2'			=> $p2,
			  'p3' 	    	=> $p3,
			  'p4'      	=> $p4,
			  'cbk'     	=> $cbk,
			  'cst'     	=> $cst,
			  'crl'     	=> $crl,
			  'hash'    	=> $hash,
			  'mpesa'		=> $mpesa,
			  'airtel'		=> $airtel,
			  'creditcard'	=> $creditcard,
			  'equity'		=> $equity,
			  'redirect_url'=> $redirect_url
			);
			$ipay_args_array = array();
			foreach($ipay_args as $key => $value){
			  $ipay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
			}
		
			return '<form action="'.$url.'" method="post" id="iPay_payment_form">
				' . implode('', $ipay_args_array) . '
				<input type="submit" class="button-alt" id="submit_iPay_payment_form" value="'.__('PAY VIA iPAY', 'woocommerce').'" /> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
				<script type="text/javascript">
					jQuery(function(){ 
					jQuery("body").block(
							{
								message: "<img src=\"'.WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/ajax-loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'woocommerce').'",
									overlayCSS:
							{
								background: "#000",
									opacity: 0.4
						},
						css: {
								padding:        20,
								textAlign:      "center",
								color:          "#555",
								border:         "3px solid #aaa",
								backgroundColor:"#fff",
								cursor:         "wait",
								lineHeight:"32px"
						}
						});
						jQuery("#submit_iPay_payment_form").click();});</script>
				</form>';
		}

		/**
		 * Returns link to the callback class
		 * Refer to WC-API for more information on using classes as callbacks
		 */
		public function ipay_callback(){
		   return WC()->api_request_url('WC_My5tech_Payment_Gateway_Ipay');
		}

		/**
		 * This function gets the callback values posted by iPay to the callback url
		 * It updates order status and order notes
		 */
		public function my5tech_callback_handler() {
		
			global $woocommerce;
			$status = '';
			$message = '';
			if(isset($_REQUEST['txncd'])){
				$val = $this->vid;
				//Response					
			   $txnReferenceNo = $_GET['txncd'];
			   $val1 = $_GET['id'];//order id
			   $val2 = $_GET['ivm'];
			   $val3 = $_GET['qwh'];
			   $val4 = $_GET['afd'];
			   $val5 = $_GET['poi'];
			   $val6 = $_GET['uyt'];
			   $val7 = $_GET['ifd'];
			   $ipnurl = "https://www.ipayafrica.com/ipn/?vendor=".$val."&id=".$val1."&ivm=".$val2."&qwh=".$val3."&afd=".$val4."&poi=".$val5."&uyt=".$val6."&ifd=".$val7;
		   
				if ( $this->live !== 'no' ) {
					$fp = fopen($ipnurl, "rb");
					$authStatus = stream_get_contents($fp, -1, -1);
					fclose($fp);
				}else{
					$authStatus = $_GET['status'];
				}
				
				$order = new WC_Order($val1);
				
				if($val1 != ''){// if order id
					try{
						$transauthorised = false;
						$status = strtolower($status);
						if($order -> status !== 'completed'){					 
							if($authStatus == "aei7p7yrx4ae34"){//success
								$transauthorised = true;
								$this -> msg['message'] = "Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.";
								$this -> msg['class'] = 'success';
								if($order -> status == 'processing'){

								}else{
									$order -> payment_complete();
									$order -> add_order_note('iPAY payment successful<br/>Transaction Id from iPAY: '.$txnReferenceNo);
									$order -> add_order_note($this->msg['message']);
									$woocommerce -> cart -> empty_cart();
								}
								
							}else if($authStatus == "fe2707etr5s4wq"){//Failed
								$order -> add_order_note('iPAY payment failed.');
								$order->update_status('failed', 'The attempted payment FAILED. Status:'.$authStatus, 'woocommerce' );
								
							}else if($authStatus == "bdi6p2yy76etrs"){//pending
								$this -> msg['message'] = "Thank you for shopping with us. Right now your payment staus is pending, We will keep you posted regarding the status of your order through e-mail";
								$this -> msg['class'] = 'error';
								$order -> add_order_note('iPAY payment status is pending ('.$authStatus.')<br/>Unnique Id from iPAY: '.$txnReferenceNo);
								$order -> add_order_note($this->msg['message']);
								$order -> update_status('on-hold');
								$woocommerce -> cart -> empty_cart();
								
							}else if($authStatus == "cr5i3pgy9867e1"){// Used Code
								$order -> add_order_note('iPAY payment failed.');
								$order->update_status('failed', 'The attempted payment FAILED due to Used Code. Status:'.$authStatus, 'woocommerce' );
								
							}else if($authStatus == "dtfi4p7yty45wq"){//less
							$order->update_status( 'on-hold', __( 'Amount paid was LESS than the required - iPay.', 'woocommerce') );
								// Reduce stock levels
								$order->reduce_order_stock();
								
							}else if($authStatus == "eq3i7p5yt7645e"){//more
								$transauthorised = true;
								$order->update_status('completed', __( 'The amount paid was MORE than the required. Please refund customer - iPay.', 'woocommerce' ));
								// Reduce stock levels
								$order->reduce_order_stock();
								
							}else{
								$order -> add_order_note('Failed Transaction.');
								$order->update_status('failed', 'Security Error. Illegal access detected', 'woocommerce' ); 
							}
						}
					}catch(Exception $e){
							$this -> msg['class'] = 'error';
							$this -> msg['message'] = "An unexpected error occurred ! Transaction failed.";
					}
				}//if order if
				
				/*Redirect to Thank you page*/
				$redirect_url = $this->get_return_url($order);
				if ( wp_redirect( $redirect_url ) ) {
					exit;
				}
			}
		}

		/**
		* Process the payment field and redirect to checkout/pay page.
		*
		* @param $order_id
		* @return array
		*/
		public function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );

			// Redirect to checkout/pay page
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id,
					add_query_arg('key', $order->order_key, $order->get_checkout_payment_url(true)))
			);

		}
	}//class end

	/**
	 * Telling woocommerce that ipay payments gateway class exists
	 * Filtering woocommerce_payment_gateways
	 * Add the Gateway to WooCommerce
	**/
	function add_my5tech_ipay_gateway_class( $methods ) {
		$methods[] = 'WC_My5tech_Payment_Gateway_Ipay';
		return $methods;
	}

	if(!add_filter( 'woocommerce_payment_gateways', 'add_my5tech_ipay_gateway_class' )){
			die;
		}
}//function end