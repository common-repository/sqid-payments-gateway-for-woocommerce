<?php
/*
Plugin Name: WooCommerce SQID Gateway
Plugin URI: http://woothemes.com/woocommerce
Description: Use SQID Payments as a credit card processor for WooCommerce.
Version: 1.1.1
Author: SQID Payments
Author URI: https://sqidpayments.com.au

Copyright: © 2014, 2015, 2016, 2017 SQID Payments Pty Ltd

*/

/**
 * Required functions
 */
function myplugin_activate() {
global $wpdb;

$checksql = 'select * from '.$wpdb->posts.' where post_name = "Response"';
$checkdata = $wpdb->get_row($checksql);
if(empty($checkdata))
{
     // Create post object
$my_post = array(
  'post_type'    => 'page',
  'post_title'    => 'Response',
  'post_content'  => '[response]',
  'post_status'   => 'publish',
  'post_author'   => 1,
  'post_category' => array( 8,39 )
);
 
// Insert the post into the database
wp_insert_post( $my_post );
}
}
register_activation_hook( __FILE__, 'myplugin_activate' );

add_shortcode('response', 'sendresponse');



function sendresponse(){
if(isset($_GET['order_id']))
{
	$order_id = $_GET['order_id'];
	$order = new WC_Order($order_id);

	if (!empty($order)) {
		$order->update_status( 'completed' );
	}
}
		if($_GET['message'] == 0){
				echo '<h1>Payment was not successful, please try again later.</h1>';
		} else if($_GET['message'] == 1){
	
		$strplace = str_replace('-',' ',$_GET['username']);
		?>
				<h1>Your Payment has been processed successfully.<br> User Name: <?php echo $strplace; ?><br>Receipt Number: <?php echo $_GET['receipt'] ?>
	<?php
	}
}

function RunCron()
{
if(file_exists(ABSPATH."wp-subscription-cron.php")) 
require_once(ABSPATH."wp-subscription-cron.php");
}
add_action('cronHook','RunCron');
wp_schedule_single_event(time()+30*60,'cronHook');

if ( ! function_exists( 'woothemes_queue_update' ) )
	require_once( 'woo-includes/woo-functions.php' );

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'xxxxxxxxxx', 'xxxxx' );

add_action('plugins_loaded', 'woocommerce_sqid_dp_init', 0);

function woocommerce_sqid_dp_init() {

	if (!class_exists('WC_Payment_Gateway'))  return;

    /**
     * Localisation
     */
    load_plugin_textdomain('wc-sqid', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

	class WC_Gateway_Sqid_Direct_Post extends WC_Payment_Gateway {

		public function __construct() {
			global $woocommerce;

		    $this->id 					= 'sqid_dp';
		    $this->method_title 		= __('SQID Payments', 'wc-sqid');
			$this->method_description 	= __('SQID handles all the steps in the secure transaction while remaining virtually transparent. Payment data is passed from the checkout form to SQID for processing thus removing the complexity of PCI compliance.', 'wc-SQID');
			$this->icon 				= plugins_url( '/images/sqidpayments_80x36.jpg' , __FILE__ );
			$this->supports 			= array( 'subscriptions', 'products', 'subscription_cancellation', 'subscription_reactivation', 'subscription_suspension', 'subscription_date_changes','subscription_amount_changes','subscription_payment_method_change' );

		    // Load the form fields.
		    $this->init_form_fields();

		    // Load the settings.
		    $this->init_settings();


			if ($this->settings['testmode'] == 'yes') {
				$this->payurl = 'https://api.staging.sqidpay.com/post';
		    } else {
				$this->payurl = 'https://api.sqidpay.com/post';
		    }
		    // Define user set variables
		    $this->title = $this->settings['title'];
		    $this->description = 'Credit cards accepted: Visa, Mastercard';
		    if ($this->settings['accept_amex'] == 'yes') $this->description .= ', American Express';
		    if ($this->settings['accept_diners'] == 'yes') $this->description .= ', Diners Club';
		    if ($this->settings['accept_jcb'] == 'yes') $this->description .= ', JCB';

   		 	// Hooks
			add_action( 'woocommerce_receipt_sqid_dp', array(&$this, 'receipt_page') );

			// Result listener
			add_action( 'woocommerce_api_wc_gateway_sqid_direct_post', array(&$this, 'relay_response'));
			add_action( 'woocommerce_api_wc_gateway_sqid_direct_post', array(&$this, 'ipn_response'));

			// Save admin options
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			
			// Additional tasks if Subscriptions is installed
			if (class_exists('WC_Subscriptions_Order')) {

				
				add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( &$this, 'remove_renewal_order_meta' ), 10, 4 );
				add_action( 'woocommerce_subscriptions_changed_failing_payment_method_'.$this->id, array(&$this, 'update_failing_payment_method' ), 10, 2 );
			}
		}

		/**
	     * Initialise Gateway Settings Form Fields
		 *
		 * @since 1.0.0
	     */
		function init_form_fields() {
			$this->form_fields = array(
			    'enabled' => array(
			        'title' => __( 'Enable/Disable', 'wc-sqid' ),
			        'type' => 'checkbox',
			        'label' => __( 'Enable this payment method', 'wc-sqid' ),
			        'default' => 'yes'
			    ),
			    'title' => array(
			        'title' => __( 'Title', 'wc-sqid' ),
			        'type' => 'text',
			        'description' => __( 'This controls the title which the user sees during checkout.', 'wc-sqid' ),
			        'default' => __( 'Credit Card via SQID Payments', 'wc-sqid' )
			    ),
				'testmode' => array(
					'title' => __( 'Test mode', 'wc-sqid' ),
					'label' => __( 'Enable Test mode', 'wc-sqid' ),
					'type' => 'checkbox',
					'description' => __( 'Process transactions in Test mode. No transactions will actually take place.', 'wc-sqid' ),
					'default' => 'yes'
				),
				'merchant_id' => array(
					'title' => __( 'SQID Merchant Code', 'wc-sqid' ),
					'type' => 'text',
					'description' => __( 'The SQID Merchant Code will be provided by Sqid.', 'wc-sqid' ),
					'default' => ''
				),
				'api_key' => array(
					'title' => __( 'Sqid API key', 'wc-sqid' ),
					'type' => 'text',
					'description' => __( 'This API key is provided by SQID.', 'wc-sqid' ),
					'default' => ''
				),
				'api_passphrase' => array(
					'title' => __( 'Sqid API passphrase', 'wc-sqid' ),
					'type' => 'text',
					'description' => __( 'This API passphrase is provided by SQID.', 'wc-sqid' ),
					'default' => ''
				),
				'api_passphrase' => array(
					'title' => __( 'SQID API passphrase', 'woothemes' ),
					'type' => 'text',
					'description' => __( 'This API passphrase is provided by SQID.', 'woothemes' ),
					'default' => ''
				),
			    'transaction_description' => array(
			        'title' => __( 'Transaction description', 'wc-sqid' ),
			        'type' => 'text',
			        'description' => __( 'This will be sent as a payment descriptor to appear on the customer\'s credit card statement.', 'wc-sqid' ),
			        'default' => __( 'WooCommerce Transaction', 'wc-sqid' )
			    ),
				'accept_amex' => array(
					'title' => __( 'Accept American Express', 'wc-sqid' ),
					'label' => __( 'Accept American Express cards', 'wc-sqid' ),
					'type' => 'checkbox',
					'description' => __( 'Contact SQID to activate American Express on your account.', 'wc-sqid' ),
					'default' => 'no'
				),
				'accept_diners' => array(
					'title' => __( 'Accept Diners Club', 'wc-sqid' ),
					'label' => __( 'Accept Diners Club cards', 'wc-sqid' ),
					'type' => 'checkbox',
					'description' => __( 'Contact SQID to activate Diners Club on your account.', 'wc-sqid' ),
					'default' => 'no'
				),
				'accept_jcb' => array(
					'title' => __( 'Accept JCB', 'wc-sqid' ),
					'label' => __( 'Accept JCB cards', 'wc-sqid' ),
					'type' => 'checkbox',
					'description' => __( 'Contact SQID to activate JCB on your account.', 'wc-sqid' ),
					'default' => 'no'
				)
			);
		} // End init_form_fields()

		/**
		 * Process the payment and return the result
		 * - redirects the customer to the payment page
		 *
		 * @since 1.0.0
		 */
		function process_payment( $order_id ) {

			$order = new WC_Order( $order_id );

			return array(
				'result' 	=> 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);
		}

		/**
		 * Generate random salt
		 *
		 * @since 1.0.0
		 */
		function generate_salt( $length = 10 ) {
		    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		    $string ='';
		    for ($p = 0; $p < $length; $p++) {
		        $string .= $characters[mt_rand(0, strlen($characters)-1)];
		    }
		    return $string;
		}

		/**
		 * Collect the credit card details on the payment page and post
		 * to Sqid Payments
		 * - includes fingerprint creation
		 *
		 * @since 1.0.0
		 */
		function receipt_page($order_id) {
			global $woocommerce;

			// Get the order
			$order = new WC_Order( $order_id );
			
			$amount = $order->get_total();

			if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) {
				if (method_exists('WC_Subscriptions_Order','get_total_initial_payment')) {
					$amount = WC_Subscriptions_Order::get_total_initial_payment( $order );
				} else {
					$amount = WC_Subscriptions_Order::get_sign_up_fee( $order ) + WC_Subscriptions_Order::get_price_per_period( $order );
				}
			}

			// Payment form
			if ($this->settings['testmode']=='yes') : ?><p><?php _e('TEST MODE ENABLED', 'wc-sqid'); ?></p><?php endif;

			// Calculate the payment fingerprints
			$amount = number_format($amount, 2, '.', '');
			//$hash = md5($this->settings['api_passphrase']).$this->settings['merchant_id'].$amount.get_woocommerce_currency();
			 $hash = md5($this->settings['api_passphrase'].$amount.$this->settings['api_key']);


			$this->result_url = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_Sqid_Direct_Post', home_url( '/' ) ) );
			$this->result_url = add_query_arg('order',$order_id,$this->result_url);
			$this->result_url = add_query_arg('key',$order->order_key,$this->result_url);

			if (get_option('woocommerce_force_ssl_checkout')=='yes' || is_ssl()) $this->result_url = str_replace('http:', 'https:', $this->result_url);

			$this->ipn_url = add_query_arg( 'ipn', 'true', $this->result_url );
			$this->result_url = add_query_arg( 'ipn', 'false', $this->result_url );

			$urlHash = md5($this->settings['api_passphrase']).$this->settings['merchant_id'].$this->result_url.$this->ipn_url;
			$urlHash = md5(strtolower($urlHash));

			$hashSalt = $this->generate_salt();
			$method = ($amount > 0) ? "processCard" : "addCard"; 
			$ip_address = isset( $_POST['ip_address'] ) ? woocommerce_clean( $_POST['ip_address'] ) : '';

			if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
			<form method="POST" action="<?php //echo $this->payurl; ?>">
			<input type="hidden" name="method" value="<?php echo $method; ?>" />
			<input type="hidden" name="merchantUUID" value="<?php echo $this->settings['merchant_id']; ?>" />
		
			<input type="hidden" name="apiKey" value="<?php echo $this->settings['api_key']; ?>" />
			<?php if ($method == "processCard") { ?>
				<input type="hidden" name="transactionAmount" value="<?php echo $amount; ?>" />
				<input type="hidden" name="transactionCurrency" value="<?php echo strtoupper(get_woocommerce_currency()); ?>" />
				<input type="hidden" name="transactionProduct" value="<?php echo $this->settings['transaction_description']; ?>" />
				<input type="hidden" name="transactionReferenceID" value="<?php echo $order_id.'_'.time(); ?>" />
			<?php } ?>

			<input type="hidden" name="notifyURL" value="<?php echo $this->ipn_url; ?>" />
			<input type="hidden" name="hashSalt" value="<?php echo $hashSalt; ?>" />
			<input type="hidden" name="returnURL" value="<?php echo $this->result_url; ?>" />
			<input type="hidden" name="hash" value="<?php echo $hash; ?>" />	
			<input type="hidden" name="urlHash" value="<?php echo $urlHash; ?>" />


			<input type="hidden" name="customerName" value="<?php echo $order->billing_first_name.' '.$order->billing_last_name; ?>" />
			<input type="hidden" name="customerCountry" value="<?php echo ($order->billing_country) ? $order->billing_country : "NA" ; ?>" />
			<input type="hidden" name="customerState" value="<?php echo ($order->billing_state) ? $order->billing_state : "NA" ; ?>" />
			<input type="hidden" name="customerCity" value="<?php echo ($order->billing_city) ? $order->billing_city : "NA" ; ?>" />
			<input type="hidden" name="customerAddress" value="<?php echo ($order->billing_address_1) ? $order->billing_address_1 : "NA" ; ?>" />
			<input type="hidden" name="customerPostCode" value="<?php echo ($order->billing_postcode) ? $order->billing_postcode : "NA" ; ?>" />
			<input type="hidden" name="customerEmail" value="<?php echo $order->billing_email; ?>" />

			<?php if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) { ?>
				<input type="hidden" name="addCard" value="1" />
				<input type="hidden" name="cardGlobal" value="0" />
				<input type="hidden" name="cardEmail" value="<?php echo $order->billing_email; ?>" />
				<input type="hidden" name="cardContact" value="<?php echo str_replace(array(' ','.','-','(',')'),'',$order->billing_phone); ?>" />
			<?php } ?>

			<input type="hidden" name="paymentCardExpiry" value="<?php echo date('my'); ?>" id="jsCardExpiry" />
			<input type="hidden" name="paymentCardName" value="<?php echo $order->billing_first_name.' '.$order->billing_last_name; ?>" />

				<fieldset>
					<p class="form-row form-row-first">
						<label for="sqid_card_number"><?php _e("Credit card number", 'woocommerce') ?> <span class="required">*</span></label>
						<input type="text" class="input-text" name="paymentCardNumber" id="sqid_card_number" /><span id="jsCardType"></span>
						<div style="clear: left;float: left;" id="errordiv"></div>
					</p>
					<div class="clear"></div>
					<p class="form-row form-row-first">
						<label for="cc-expire-month"><?php _e("Expiration date", 'woocommerce') ?> <span class="required">*</span></label>
						<select name="EPS_EXPIRYMONTH" id="cc-expire-month">
							<option value=""><?php _e('Month', 'woocommerce') ?></option>
							<?php
								$months = array();
								for ($i = 1; $i <= 12; $i++) {
									$timestamp = mktime(0, 0, 0, $i, 1);
								    $months[date('m', $timestamp)] = date('F', $timestamp);
								}
								foreach ($months as $num => $name) {
						            printf('<option value="%s">%s - %s</option>', $num,$num, __($name,'woocommerce'));
						        }
							?>
						</select>
						<select name="EPS_EXPIRYYEAR" id="cc-expire-year">
							<option value=""><?php _e('Year', 'woocommerce') ?></option>
							<?php
								$years = array();
								for ($i = date('Y'); $i <= date('Y') + 15; $i++) {
									$twodigit = substr($i,-2);
								    printf('<option value="%u">%u</option>', $twodigit, $i);
								}
							?>
						</select>
					</p>
					<p class="form-row form-row-last">
						<label for="sqid_card_ccv"><?php _e("Card security code", 'woocommerce') ?> <span class="required">*</span></label>
						<input type="text" class="input-text" id="sqid_card_ccv" name="paymentCardCSC" maxlength="4" style="width:60px" />
						<span class="help sqid_card_ccv_description"><?php _e('3 or 4 digits usually found on the signature strip.', 'woocommerce') ?></span>
					</p>
					<div class="clear"></div>
				</fieldset>
				<input type="submit" name="post" id="jsPayButton" class="submit buy button" value="<?php _e('Click Once to Pay','woocommerce'); ?>" />
				</form>
				<?php 
				//Country Codes custom array
				$country_codes = array(
				'AX' 	=>'ALA',
				'AF'	=>'AFG',
				'AL'	=>'ALB',
				'DZ'	=>'DZA',
				'AD'	=>'AND',
				'AO'	=>'AGO',
				'AI'	=>'AIA',
				'AQ'	=>'ATA',
				'AG'	=>'ATG',
				'AR'	=>'ARG',
				'AM'	=>'ARM',
				'AW'	=>'ABW',
				'AU'	=>'AUS',
				'AT'	=>'AUT',
				'AZ'	=>'AZE',
				'BS'	=>'BHS',
				'BH'	=>'BHR',
				'BD'	=>'BGD',
				'BB'	=>'BRB',
				'BY'	=>'BLR',
				'BE'	=>'BEL',
				'BZ'	=>'BLZ',
				'BJ'	=>'BEN',
				'BM'	=>'BMU',
				'BT'	=>'BTN',
				'BO'	=>'BOL',
				'BA'	=>'BIH',
				'BW'	=>'BWA',
				'BV'	=>'BVT',
				'BR'	=>'BRA',
				'IO'	=>'IOT',
				'BN'	=>'BRN',
				'BG'	=>'BGR',
				'BF'	=>'BFA',
				'BI'	=>'BDI',
				'KH'	=>'KHM',
				'CM'	=>'CMR',
				'CA'	=>'CAN',
				'CV'	=>'CPV',
				'KY'	=>'CYM',
				'CF'	=>'CAF',
				'TD'	=>'TCD',
				'CL'	=>'CHL',
				'CN'	=>'CHN',
				'CX'	=>'CXR',
				'CC'	=>'CCK',
				'CO'	=>'COL',
				'KM'	=>'COM',
				'CG'	=>'COG',
				'CD'	=>'COD',
				'CK'	=>'COK',
				'CR'	=>'CRI',
				'HR'	=>'HRV',
				'CU'	=>'CUB',
				'CY'	=>'CYP',
				'CZ'	=>'CZE',
				'DK'	=>'DNK',
				'DJ'	=>'DJI',
				'DM'	=>'DMA',
				'DO'	=>'DOM',
				'EC'	=>'ECU',
				'EG'	=>'EGY',
				'SV'	=>'SLV',
				'GQ'	=>'GNQ',
				'ER'	=>'ERI',
				'EE'	=>'EST',
				'ET'	=>'ETH',
				'FK'	=>'FLK',
				'FO'	=>'FRO',
				'FJ'	=>'FJI',
				'FI'	=>'FIN',
				'FR'	=>'FRA',
				'GF'	=>'GUF',
				'PF'	=>'PYF',
				'TF'	=>'ATF',
				'GA'	=>'GAB',
				'GM'	=>'GMB',
				'GE'	=>'GEO',
				'DE'	=>'DEU',
				'GH'	=>'GHA',
				'GI'	=>'GIB',
				'GR'	=>'GRC',
				'GL'	=>'GRL',
				'GD'    =>'GRD',
				'GP'	=>'GLP',
				'GT'	=>'GTM',
				'GN'	=>'GIN',
				'GW'	=>'GNB',
				'GY'	=>'GUY',
				'HT'	=>'HTI',
				'HM'	=>'HMD',
				'HK'	=>'HKG',
				'HU'	=>'HUN',
				'IS'	=>'ISL',
				'IN'	=>'IND',
				'ID'	=>'IDN',
				'IR'	=>'IRN',
				'IQ'	=>'IRQ',
				'IL'	=>'ISR',
				'IT'	=>'ITA',
				'JM'	=>'JAM',
				'JP'	=>'JPN',
				'JO'	=>'JOR',
				'KZ'	=>'KAZ',
				'KE'	=>'KEN',
				'KI'	=>'KIR',
				'KW'	=>'KWT',
				'KG'	=>'KGZ',
				'LA'	=>'LAO',
				'LV'	=>'LVA',
				'LB'	=>'LBN',
				'LS'	=>'LSO',
				'LR'	=>'LBR',
				'LY'	=>'LBY',
				'LI'	=>'LIE',
				'LT'	=>'LTU',
				'LU'	=>'LUX',
				'MO'	=>'MAC',
				'MK'	=>'MKD',
				'MG'	=>'MDG',
				'MW'	=>'MWI',
				'MY'	=>'MYS',
				'MV'	=>'MDV',
				'ML'	=>'MLI',
				'MT'	=>'MLT',
				'MH'	=>'MHL',
				'MQ'	=>'MTQ',
				'MR'	=>'MRT',
				'MU'	=>'MUS',
				'YT'	=>'MYT',
				'MX'	=>'MEX',
				'FM'	=>'FSM',
				'MD'	=>'MDA',
				'MC'	=>'MCO',
				'MN'	=>'MNG',
				'MS'	=>'MSR',
				'MA'	=>'MAR',
				'MZ'	=>'MOZ',
				'MM'	=>'MMR',
				'NA'	=>'NAM',
				'NR'	=>'NRU',
				'NP'	=>'NPL',
				'NL'	=>'NLD',
				'AN'	=>'ANT',
				'NC'	=>'NCL',
				'NZ'	=>'NZL',
				'NI'	=>'NIC',
				'NE'	=>'NER',
				'NG'	=>'NGA',
				'NU'	=>'NIU',
				'NF'	=>'NFK',
				'NO'	=>'NOR',
				'OM'	=>'OMN',
				'PK'	=>'PAK',
				'PA'	=>'PAN',
				'PG'	=>'PNG',
				'PY'	=>'PRY',
				'PE'	=>'PER',
				'PH'	=>'PHL',
				'PN'	=>'PCN',
				'PL'	=>'POL',
				'PT'	=>'PRT',
				'QA'	=>'QAT',
				'RE'	=>'REU',
				'RO'	=>'ROU',
				'RU'	=>'RUS',
				'RW'	=>'RWA',
				'SH'	=>'SHN',
				'KN'	=>'KEN',
				'LC'	=>'LCA',
				'PM'	=>'SPM',
				'VC'	=>'VCT',
				'SM'	=>'SMR',
				'SA'	=>'SAU',
				'SN'	=>'SEN',
				'RS'	=>'RSD',
				'SC'	=>'SYC',
				'SL'	=>'SLE',
				'SG'	=>'SGP',
				'SK'	=>'SVK',
				'SI'	=>'SVN',
				'SB'	=>'SLB',
				'SO'	=>'SOM',
				'ZA'	=>'ZAF',
				'GS'	=>'SGS',
				'KR'	=>'KOR',
				'SS'	=>'SSP',
				'ES'	=>'ESP',
				'LK'	=>'LKA',
				'SD'	=>'SDN',
				'SR'	=>'SUR',
				'SJ'	=>'SJM',
				'SZ'	=>'SWZ',
				'SE'	=>'SWE',
				'CH'	=>'CHE',
				'SY'	=>'SYR',
				'TW'	=>'TWN',
				'TJ'	=>'TJK',
				'TZ'	=>'TZA',
				'TH'	=>'THA',
				'TG'	=>'TGO',
				'TK'	=>'TKL',
				'TO'	=>'TON',
				'TT'	=>'TTO',
				'TN'	=>'TUN',
				'TR'	=>'TUR',
				'TM'	=>'TKM',
				'TC'	=>'TCA',
				'TV'	=>'TUV',
				'UG'	=>'UGA',
				'UA'	=>'UKR',
				'AE'	=>'ARE',
				'GB'	=>'GBR',
				'US'	=>'USA',
				'UY'	=>'URY',
				'UZ' 	=>'UZB',
				'VU'	=>'VUT',
				'VE'	=>'VEN',
				'VN'	=>'VNM',
				'WF'	=>'WLF',
				'EH'	=>'ESH',
				'YE'	=>'YEM',
				'ZM'	=>'ZMB',
				'ZW'	=>'ZWE',
				 );
				
				if(isset($_POST['post'])){
				
				//Getting token for payment from SQID
				$data1 = array(
					"methodName"=>"getToken",
					"merchantCode"=>$this->settings['merchant_id'],
					"apiKey"=>$this->settings['api_key'],
					"amount"=> $amount,
					"currency"=>get_woocommerce_currency(),
					"referenceID"=>$order_id.'_'.time(),
					"cardCSC"=>$_POST['paymentCardCSC'],
					//"token"=>$dcode->token,
					"customerName"=> $order->billing_first_name.' '.$order->billing_last_name,
					"customerHouseStreet"=>$order->billing_address_1,
					"customerSuburb"=> $order->billing_city,
					"customerCity"=>$order->billing_city,
					"customerState"=>$order->billing_state,
					"customerCountry"=>$country_codes[$order->billing_country],
					"customerPostCode"=>$order->billing_postcode,
					"customerMobile"=>str_replace(array(' ','.','-','(',')'),'',$order->billing_phone),
					"customerEmail"=> $order->billing_email,
					"customerIP"=>$_SERVER['REMOTE_ADDR'],
					"cardNumber"=>$_POST['paymentCardNumber'],
					"cardExpiry"=>$_POST['EPS_EXPIRYMONTH'].$_POST['EPS_EXPIRYYEAR'],
					"cardName"=> $order->billing_first_name.' '.$order->billing_last_name,
					"customField1"=>$order_id,
					"customField2"=>$dcode->token,
					"customField3"=>"SPV1.1.1".'_'.site_url(),
					"hashValue"=>$hash,
				);
					$str_data1 = json_encode($data1);
				
					$decode = $this->sendPostData($this->payurl, $str_data1);
					$dcode = json_decode($decode);
					if(empty($dcode->token))
					{
						$message = "Credit card number incomplete or incorrect";
						echo "<script type='text/javascript'>alert('$message');</script>";
						die;
					}
					//Making Payment by using the previous token
					
					$data = array(
					"methodName"=>"processTokenPayment",
					"merchantCode"=>$this->settings['merchant_id'],
					"apiKey"=>$this->settings['api_key'],
					"amount"=>$amount,
					"currency"=>get_woocommerce_currency(),
					"referenceID"=>$order_id.'_'.time(), 
					"token"=>$dcode->token,
					"cardCSC"=>$_POST['paymentCardCSC'],
					"customerIP"=>$_SERVER['REMOTE_ADDR'],
					"customerCountry"=>$country_codes[$order->billing_country],
					"customField1"=>$order_id,
					"customField2"=>$dcode->token,
					"customField3"=>"SPV1.1.1".'_'.site_url(),
					"hashValue"=>$hash,
					);
					global $wpdb;
					$order = new WC_Order( $order_id );
					$items = $order->get_items();
					
					if(!empty($items) && isset($items)) {
						foreach($items as $key=>$item)
						{
							$sql = "Select * from sq_woocommerce_order_itemmeta WHERE order_item_id = ".$key." AND meta_key = '_subscription_expiry_date'  "; 
							$row = $wpdb->get_row($sql);
							$sql1 = "Select * from sq_woocommerce_order_items WHERE order_item_id = ".$key.""; 
							$row1 = $wpdb->get_row($sql1);
							$sql2 = "Select * from sq_postmeta WHERE post_id = ".$item['product_id']." AND meta_key = '_price' "; 
							$row2 = $wpdb->get_row($sql2);
							$subs_product_id 	=	$item['product_id'];
							$subs_order_id		=	$row1->order_id;
							$subs_price			=	$row2->meta_value;
							$subs_product_name 	=	$item['name'];
							$subs_total_price 	=	$item['line_subtotal'];
							$subs_interval	 	=	$item['subscription_interval'];
							$subs_length	 	=	$item['subscription_length'];
							$subs_time_perioud 	=	$item['subscription_trial_period'];
							$subs_start_date 	=	$item['subscription_start_date'];
							$subs_qty			=   $item['qty'];
							$subs_expiry_date 	=	$row->meta_value;
						}
					} 
					global $totalprice;
					$totalprice	=	'$'.$amount; 
					global $subs_qty1;
					$subs_qty1 = $subs_qty;
					global $sbprce;
					$sbprce = '$'.$subs_price;
					global $subs_length1;
					$subs_length1 = $subs_length;
					global $subs_time_perioud1;
					$subs_time_perioud1 = $subs_time_perioud;
					global $subs_product_name1;
					$subs_product_name1 = $subs_product_name;
					add_filter( 'woocommerce_email_order_items_table','subProduct' );
					 function subProduct($array1){
						 global $totalprice;
						 global $subs_qty1;
						 global $sbprce;
						 global $subs_length1;
						 global $subs_time_perioud1;
						 global $subs_product_name1;
						?>
						<thead>
							<tr>
								<th style="text-align:left; border: 1px solid #eee;" scope="col"><?php echo $subs_product_name1; ?></th>
								<th style="text-align:left; border: 1px solid #eee;" scope="col"><?php echo $subs_qty1; ?></th>
								<th style="text-align:left; border: 1px solid #eee;" scope="col"><?php echo $totalprice; ?></th>
							</tr>
						</thead>
						<?php
					
					 }   
					
					
					
					global $filterprice;
					$filterprice	=	'$'.$amount; 
					$str_data = json_encode($data);
					// Start // Customized email template using hook  
					 add_filter( 'woocommerce_get_order_item_totals', 'customerTemplate');
					 function customerTemplate($array) { 
						 global $filterprice;
						 $array['cart_subtotal']		=	array("label"=>'Cart Subtotal:',"value"=>$filterprice);
						return $array;
					 } 
					// end
					$decode = $this->sendPostData($this->payurl, $str_data);
					$decode_data = json_decode($decode);
					  
					foreach($decode_data as $key=>$val){
						$fArray[$key]=$val;
					}
					$this->ipn_response($fArray);
					$searlize = serialize($decode_data);
					
					if($decode_data->sqidResponseCode == 0) {
					//add_option("transaction_".$decode_data->receiptNo, $searlize);
					$message = 1;
						$username = $order->billing_first_name.'-'.$order->billing_last_name;
						$site_url = site_url().'/response?order_id='.$order_id.'&receipt='.$decode_data->receiptNo.'&username='.$username.'&message='.$message;
					} else {
						$message = 0;
						$site_url = site_url().'/response?message='.$message;
					}
					
					// Checked if Subscription Plugin is Active
					
					
					if (class_exists('WC_Subscriptions')) {
					global $wpdb;
					$order = new WC_Order( $order_id );
					$items = $order->get_items();
					if ($this->settings['testmode'] == 'yes') {
						$this->payurl = 'https://api.staging.sqidpay.com/post';
					} else {
						$this->payurl = 'https://api.sqidpay.com/post';
					}
					if(!empty($items) && isset($items)) {
						foreach($items as $key=>$item)
						{
							$sql = "Select * from sq_woocommerce_order_itemmeta WHERE order_item_id = ".$key." AND meta_key = '_subscription_expiry_date'  "; 
							$row = $wpdb->get_row($sql);
							$sql1 = "Select * from sq_woocommerce_order_items WHERE order_item_id = ".$key.""; 
							$row1 = $wpdb->get_row($sql1);
							$sql2 = "Select * from sq_postmeta WHERE post_id = ".$item['product_id']." AND meta_key = '_price' "; 
							$row2 = $wpdb->get_row($sql2);
							$subs_product_id 	=	$item['product_id'];
							$subs_order_id		=	$row1->order_id;
							$subs_price			=	$row2->meta_value;
							$subs_product_name 	=	$item['name'];
							$subs_total_price 	=	$item['line_subtotal'];
							$subs_interval	 	=	$item['subscription_interval'];
							$subs_length	 	=	$item['subscription_length'];
							$subs_time_perioud 	=	$item['subscription_trial_period'];
							$subs_start_date 	=	$item['subscription_start_date'];
							$subs_expiry_date 	=	$row->meta_value;
						}
						
						
				    
					  
					}
					include_once( ABSPATH . 'wp-admin/includes/plugin.php' ); 
					$susproduct = WC_Subscriptions_Product::get_price_string( $subs_product_id );
					
					if(!empty($susproduct)){
					global $wpdb;
					$insert_query = "insert into sq_subscription(subs_prdct_id,subs_order_id,subs_prdct_price,subs_prdct_name,merchant_code,apikey,currency,hashvalue,token,subs_total_price,subs_interval,time_perioud,subs_length,start_data,url,expiry_date)values('".$subs_product_id."','".$subs_order_id. "','".$subs_price. "','".$subs_product_name."','".$this->settings['merchant_id']."','".$this->settings['api_key']."','". get_woocommerce_currency() ."','".$hash."','".$dcode->token ."','".$subs_total_price."','".$subs_interval."','".$subs_time_perioud."','".$subs_length."','".$subs_start_date."','".$this->payurl."','".$subs_expiry_date."' )";
				
					$insert_data = $wpdb->query($insert_query);
					} 
					if($subs_time_perioud == 'day'){
						$interval = 'day';
					}
					else if($subs_time_perioud == 'week'){
						$interval = 'week';
					}
					else if($subs_time_perioud == 'month'){
						$interval = 'month';
					}
					else{
						$interval = 'year';
					}
			
					// for per day
					$startdate = $subs_start_date;
					$enddate = $subs_expiry_date;
					$expiredatetimestamp = strtotime($enddate);
					$startdatetimestamp = strtotime($startdate);
					if($subs_interval == 1){
						for($i = 0; $i<$subs_length ; $i++)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					else if($subs_interval == 2){
						for($i = 0; $i<$subs_length ; $i += 2)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					else if($subs_interval == 3){
						for($i = 0; $i<$subs_length ; $i += 3)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					else if($subs_interval == 4){
						for($i = 0; $i<$subs_length ; $i += 4)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					else if($subs_interval == 5){
						for($i = 0; $i<$subs_length ; $i += 5)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					else{
						for($i = 0; $i<$subs_length ; $i += 6)
						{
							$timeentries[] =  strtotime('+'.$i. $interval, $startdatetimestamp);
						}
					}
					
					$lastid = $wpdb->insert_id;
					if(!empty($timeentries)) {
						foreach($timeentries as $key=>$value)
						{
							if($key!=0)
							{
								 $insert_sql= "insert into sq_cron_entries(subscription_key, cron_time,status)values('".$lastid."','".$value. "','0')"; 
								 $insert_data = $wpdb->query($insert_sql);
							}
						}
					}
				}
					wp_redirect($site_url);
				die;
				}
				?>
				<script type="text/javascript">
				function isCreditCard( CC )
					 {                    
						var checksum = 1;
						  if (CC.length > 19)
							    return checksum = 0;

						  sum = 0; mul = 1; l = CC.length;
						  for (i = 0; i < l; i++)
						  {
							   digit = CC.substring(l-i-1,l-i);
							   tproduct = parseInt(digit ,10)*mul;
							   if (tproduct >= 10)
									sum += (tproduct % 10) + 1;
							   else
									sum += tproduct;
							   if (mul == 1)
									mul++;
							   else
									mul--;
						  }
						  if ((sum % 10) == 0)
							   return checksum = 1;
						  else
							   return checksum = 0;
					 }
				jQuery(function(){

					// Copy across the expiry field values to the hidden input
					jQuery('select#cc-expire-month, select#cc-expire-year').change(function() {
						jQuery('input#jsCardExpiry').val(jQuery('select#cc-expire-month').val()+jQuery('select#cc-expire-year').val());
					});

					jQuery('input#jsPayButton').attr('disabled', 'disabled');
					
					jQuery('#sqid_card_ccv').keyup(function() {
					var cnumber = jQuery('#jsCardType').html();
					if(cnumber =='Credit card number incomplete or incorrect' || cnumber=='' )
						cval = 0;
					else
						cval = 1;
						var exm = jQuery('#cc-expire-month').val();
						var cvv = jQuery('#sqid_card_ccv').val();
						var exy = jQuery('#cc-expire-year').val();
						if(exm!='' && cvv!='' && exy!='' && cval==1)
						jQuery('input#jsPayButton').removeAttr('disabled');
						else
						jQuery('input#jsPayButton').attr('disabled', 'disabled');

					})
					
					jQuery('#cc-expire-year').on('change',function() {
						var cnumber = jQuery('#jsCardType').html();
					if(cnumber =='Credit card number incomplete or incorrect' || cnumber=='' )
						cval = 0;
					else
						cval = 1;
						var exm = jQuery('#cc-expire-month').val();
						var cvv = jQuery('#sqid_card_ccv').val();
						var exy = jQuery('#cc-expire-year').val();
						if(exm!='' && cvv!='' && exy!='' && cval==1)
						jQuery('input#jsPayButton').removeAttr('disabled');
						else
						jQuery('input#jsPayButton').attr('disabled', 'disabled');
					})
					jQuery('#cc-expire-month').on('change',function() {
						var cnumber = jQuery('#jsCardType').html();
					if(cnumber =='Credit card number incomplete or incorrect' || cnumber=='' )
						cval = 0;
					else
						cval = 1;
						var exm = jQuery('#cc-expire-month').val();
						var cvv = jQuery('#sqid_card_ccv').val();
						var exy = jQuery('#cc-expire-year').val();
						if(exm!='' && cvv!='' && exy!='' && cval==1)
						jQuery('input#jsPayButton').removeAttr('disabled');
						else
						jQuery('input#jsPayButton').attr('disabled', 'disabled');

					})
					

					
					jQuery('input#sqid_card_number').keyup(function() {
					var exm = jQuery('#cc-expire-month').val();
					var cvv = jQuery('#sqid_card_ccv').val();
					var exy = jQuery('#cc-expire-year').val();
						var number = jQuery(this).val();
						var length = number.toString().length;
						//number = number.replace(/[^0-9]/g, '');
							var re = new RegExp("^4[0-9]{12}(?:[0-9]{3})?$");
							var checksum = isCreditCard(number);
           					if (number.match(re) && checksum==1 && length==16) {
              				jQuery('span#jsCardType').html('<img src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/visa.png'; ?>" alt="Visa detected" style="vertical-align: bottom;"/>');
				            jQuery('input#jsCardType').val('visa');
							if(exm!='' && cvv!='' && exy!='')
				            jQuery('input#jsPayButton').removeAttr('disabled');
				            return;
           					}
							else
							{
								 jQuery('span#jsCardType').html('Credit card number incomplete or incorrect');
								jQuery('input#jsPayButton').attr( "disabled", "disabled" );
							}
							re = new RegExp("^5[1-5][0-9]{14}$");
				            if (number.match(re) != null && checksum==1 && length==16) {
				            jQuery('span#jsCardType').html('<img src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/mastercard.png'; ?>" alt="Mastercard detected" style="vertical-align: bottom;"/>');
				            jQuery('input#jsCardType').val('mastercard');
							if(exm!='' && cvv!='' && exy!='')
				            jQuery('input#jsPayButton').removeAttr('disabled');
				            return;
				            }
							else
							{
								 jQuery('span#jsCardType').html('Credit card number incomplete or incorrect');
								jQuery('input#jsPayButton').attr( "disabled", "disabled" );
							}
				            re = new RegExp("^3[47][0-9]{13}$");
				            if (number.match(re) != null && checksum==1 && length==15) {
				            jQuery('span#jsCardType').html('<img src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/amex.png'; ?>" alt="American Express detected"  style="vertical-align: bottom;"/>');
				            jQuery('input#jsCardType').val('amex');
							if(exm!='' && cvv!='' && exy!='')
				            jQuery('input#jsPayButton').removeAttr('disabled');
				            return;
				            }
							else
							{
								 jQuery('span#jsCardType').html('Credit card number incomplete or incorrect');
								jQuery('input#jsPayButton').attr( "disabled", "disabled" );
							}
				            re = new RegExp("^3(?:0[0-5]|[68][0-9])[0-9]{11}$");
				            if (number.match(re) != null && checksum==1) {
				            jQuery('span#jsCardType').html('<img src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/diners.png'; ?>" alt="Diners Club card detected"  style="vertical-align: bottom;"/>');
				            jQuery('input#jsCardType').val('dinersclub');
							if(exm!='' && cvv!='' && exy!='')
				            jQuery('input#jsPayButton').removeAttr('disabled');
				            return;
				            }
							else
							{
								jQuery('span#jsCardType').html('Credit card number incomplete or incorrect');
								jQuery('input#jsPayButton').attr( "disabled", "disabled" );
							}
				            re = new RegExp("^(?:3[0-9]{15}|(2131|1800)[0-9]{11})$");
				            if (number.match(re) != null && checksum==1) {
				            jQuery('span#jsCardType').html('<img src="<?php echo WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/jcb.png'; ?>" alt="JCB card detected"  style="vertical-align: bottom;"/>');
				            jQuery('input#jsCardType').val('jcb');
						if(exm!='' && cvv!='' && exy!='')
				            jQuery('input#jsPayButton').removeAttr('disabled');
				            return;
				            }
							else
							{
								 jQuery('span#jsCardType').html('Credit card number incomplete or incorrect');
								jQuery('input#jsPayButton').attr( "disabled", "disabled" );
							}
			
							
							
							//jQuery('span#jsCardType').html('');
							jQuery('input#jsCardType').val('');
					});
				});
				</script>
		<?php
		}

		function sendPostData($url, $post){
			$headers = array('Accept: application/json','Content-Type: application/json');
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_PORT, 443);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			$response = curl_exec($curl);
			curl_close($curl); // Seems like good practice
			return $response;
		 }
		/* Handles IPN like message from SQID Payments
		 *
		 * @since 1.0.0
		 */
		 function ipn_response($POST=array()) {
			global $woocommerce;
				$order_id = (int) $POST['custom1'];
		        $order = new WC_Order($order_id);

						if (isset($POST['sqidResponseCode']) && (string)$POST['sqidResponseCode'] == '0') {
			
								// Payment complete
								if (isset($POST['transactionID'])) {
									$order->add_order_note(
								'Sqid Transaction ID: '.(string)$POST['transactionID']."\r\n".'Receipt #: '.(string)$POST['receiptNo']);
								} else {
									// This was probably just an addCard API
									$order->add_order_note('Card details saved to Sqid.');
								}

								if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) {
									// Check for saved card key
									if (isset($POST['custom2'])) {
										update_post_meta( $order_id, '_sqidpayments_payment_token', (string)$POST['custom2'] );
										// Activate subscriptions
										WC_Subscriptions_Manager::activate_subscriptions_for_order( $order );
									} else {
										$order->add_order_note('Unable to store payment details with Sqid for ongoing subscription. Cancelling subscription.');
										WC_Subscriptions_Manager::cancel_subscriptions_for_order( $order );
									}
								}

								$order->payment_complete();

								// Remove cart
								$woocommerce->cart->empty_cart();
						} 
			}

		   
		 /**
		 * Don't transfer Sqid customer/token meta when creating a parent renewal order.
		 * 
		 * @access public
		 * @param array $order_meta_query MySQL query for pulling the metadata
		 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
		 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
		 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
		 * @return void
		 */
		function remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

			if ( 'parent' == $new_order_role )
				$order_meta_query .= " AND `meta_key` NOT LIKE '_sqid_payment_token' ";
 
			return $order_meta_query;
		}
			
}


	add_action('wp_head', 'addtable');
	function addtable()
	{
			global $wpdb;
			$querySelect = "SELECT ID FROM sq_subscription";
			$result = $wpdb->query($querySelect);
			if(empty($result)) {
			$queryCreate = "CREATE TABLE IF NOT EXISTS sq_subscription (
			id int(11) AUTO_INCREMENT,
			subs_prdct_id int(11) NOT NULL,
			subs_order_id varchar(255) NOT NULL,
			subs_prdct_price varchar(255) NOT NULL,
			subs_prdct_name varchar(255) NOT NULL,
			merchant_code varchar(255) NOT NULL,
			apikey varchar(255) NOT NULL,
			currency varchar(255) NOT NULL,
			hashvalue varchar(255) NOT NULL,
			token varchar(255) NOT NULL,
			subs_total_price varchar(255) NOT NULL,
			subs_interval varchar(255) NOT NULL,
			time_perioud varchar(255) NOT NULL,
			subs_length varchar(255) NOT NULL,
			start_data varchar(255) NOT NULL,
			url varchar(255) NOT NULL,
			expiry_date varchar(255) NOT NULL,
			PRIMARY KEY  (id)
			)";
			$wpdb->query($queryCreate);
		}
		    $querySelect1 = "SELECT ID FROM sq_cron_entries";
			$result1 = $wpdb->query($querySelect1);
			if(empty($result1)) {
				$queryCreate1 = "CREATE TABLE IF NOT EXISTS sq_cron_entries (
				id int(11) AUTO_INCREMENT,
				subscription_key int(11) NOT NULL,
				cron_time varchar(255) NOT NULL,
				status int(11) NOT NULL,
				PRIMARY KEY  (id)
				)";
				$wpdb->query($queryCreate1);
			}
			
	}

	/**
	 * Add the SQID Payments gateway to WooCommerce
	 *
	 * @since 1.0.0
	 **/
	function add_sqid_dp_gateway( $methods ) {
		$methods[] = 'WC_Gateway_sqid_Direct_Post';
		return $methods;
	}
	add_filter('woocommerce_payment_gateways', 'add_sqid_dp_gateway' );
}