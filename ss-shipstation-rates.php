<?php
/**
 * Plugin Name: Signalstuff Shipstation Rates
 * Plugin URI: https://signalstuff.com/
 * Description: Custom Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Signalstuff
 * Author URI: https://signalstuff.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ss-shipstation-rates
 */

 
if ( ! defined( 'WPINC' ) ) {
    die;
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    function ss_shipping_method_init() {
        if ( ! class_exists( 'Ss_Shipstation_Shipping_Method' ) ) {
            class Ss_Shipstation_Shipping_Method extends WC_Shipping_Method {

                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id           = 'ss_shipping_method'; // Id for your shipping method. Should be uunique.
                    $this->method_title = __( 'ShipStation', 'ss-shipstation-rates' );  // Title shown in admin
                    $this->init();

                    $this->services        = get_option( 'ss_shipping_data' );
                    $this->enabled         = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes'; // This can be added as an setting but for this example its forced enabled
                    $this->title           = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'SS Shipping', 'ss-shipstation-rates' ); // This can be added as an setting but for this example its forced.
                    $this->custom_services = isset( $this->settings['services'] ) ? $this->settings['services'] : array();
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                public function init() {
                    
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
                    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
                
                /**
                 * generate_services_html function.
                 */
                public function generate_services_html() {
                    ob_start();
                    include( 'includes/ss-services-html.php' );
                    return ob_get_clean();
                }

                /**
                 * Define settings field for this shipping
                 *
                 * @access public
                 * @return void
                 */
                public function init_form_fields() {
                    //die('init_form_fields');
                    $this->form_fields = array(
                        'enabled' => array(
                            'title'   => __('Enable', 'ss-shipstation-rates'),
                            'type'    => 'checkbox',
                            'default' => 'yes'
                        ),
                        'title' => array(
                            'title'   => __('Title', 'ss-shipstation-rates'),
                            'type'    => 'text',
                            'default' => __('SS Shipping', 'ss-shipstation-rates')
                        ),
                        'api_key'   => array(
                            'title' => __('API Key', 'ss-shipstation-rates'),
                            'type'  => 'text',
                        ),
                        'api_secret' => array(
                            'title' => __('API Secret', 'ss-shipstation-rates'),
                            'type'  => 'password',
                        ),
                        'services'  => array(
                            'type' => 'services',
                        ),
                    );
                }

                /**
                 * validate_services_field function.
                 *
                 * @access public
                 * @param mixed $key
                 * @return void
                 */
                public function validate_services_field( $key ) {
                    $services        = array();
                    $posted_services = $_POST['ss_shipping_service'];
					
                    foreach ( $posted_services as $code => $settings ) {
                        foreach ( $this->services[$code] as $skey => $list_services ) {
                            foreach ( $list_services->list_packages as $pkey => $package ) {
								$services[ $code ]['enabled']                                                      = isset( $settings['enabled'] ) ? true : false;
								$services[ $code ][ $list_services->code ]['name']                                 = isset( $settings[ $list_services->code ]['name'] ) ? $settings[ $list_services->code ]['name'] : '';
                                $services[ $code ][ $list_services->code ][ $package->code ]['enabled']            = isset( $settings[ $list_services->code ][ $package->code ]['enabled'] ) ? true : false;
                                $services[ $code ][ $list_services->code ][ $package->code ]['adjustment']         = wc_clean( $settings[ $list_services->code ][ $package->code ]['adjustment'] );
                                $services[ $code ][ $list_services->code ][ $package->code ]['adjustment_percent'] = wc_clean( $settings[ $list_services->code ][ $package->code ]['adjustment_percent'] );
                            }
                        }

					}
					
                    return $services;
                }

                /**
                 * Make API Call.
                 * 
                 * @access public
                 * @param string $api_url
                 * @param string $method
                 * @param array $headers
                 * @param array $post_data
                 * @return array
                 */
                public function ss_shipstation_api_request( $api_url, $method = "GET",  $headers = array(), $post_data = array() ) {
                    //api headers
                    $ss_auth = base64_encode( $this->settings['api_key'] . ':' . $this->settings['api_secret'] );
                    $ss_headers = array(
                        "Host: ssapi.shipstation.com",
                        "Authorization: Basic $ss_auth"
                    );

                    if( ! empty( $headers ) ){
                        $ss_headers = array_merge( $ss_headers, $headers );
                    }
                    
                    $curl = curl_init();

                    curl_setopt_array( $curl, array(
                        CURLOPT_URL => $api_url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => $method,
                        CURLOPT_HTTPHEADER => $ss_headers,
                    ) );

                    if( $method == "POST" && ! empty( $post_data ) ){
                        curl_setopt( $curl, CURLOPT_POST, true );
                        curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $post_data ) );
                    }

                    $response = curl_exec($curl);

                    curl_close($curl);
                    
                    return json_decode($response);
                }


                /**
                 * Retrieves shipping rates for the specified shipping details.
                 * 
                 * @access public
                 * @return void
                 */
                public function ss_shipstation_get_rates( $shipping_info = array() ) {
                    if( ! empty( $shipping_info ) ) {
                        //set api headers
                        $header    = array( 'Content-Type: application/json' );
                        $post_data = array();

                        if( ! empty( $shipping_info['carrierCode'] ) ){
                            $post_data['carrierCode'] = $shipping_info['carrierCode'];
                        }
                        
                        if( ! empty( $shipping_info['serviceCode'] ) ){
                            $post_data['serviceCode'] = $shipping_info['serviceCode'];
                        }

                        if( ! empty( $shipping_info['packageCode'] ) ){
                            $post_data['packageCode'] = $shipping_info['packageCode'];
                        }

                        if( ! empty( $shipping_info['fromPostalCode'] ) ){
                            $post_data['fromPostalCode'] = $shipping_info['fromPostalCode'];
                        }

                        if( ! empty( $shipping_info['toCountry'] ) ){
                            $post_data['toCountry'] = $shipping_info['toCountry'];
                        }

                        if( ! empty( $shipping_info['toPostalCode'] ) ){
                            $post_data['toPostalCode'] = $shipping_info['toPostalCode'];
                        }

                        if( ! empty( $shipping_info['toCity'] ) ){
                            $post_data['toCity'] = $shipping_info['toCity'];
                        }

                        if( ! empty( $shipping_info['weight'] ) ){
                            $post_data['weight'] = $shipping_info['weight'];
                        }

                        $api_url           = 'https://ssapi.shipstation.com/shipments/getrates';
                        $shipstation_rates = $this->ss_shipstation_api_request( $api_url, 'POST', $header, $post_data );
    
                        return $shipstation_rates;
                    }
                    return array();
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param array $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {

                    $weight = 0;
                    $cost = 0;
                    $shipping_info = array();
                    
                    foreach ( $package['contents'] as $item_id => $values ) 
                    {
                        $_product = $values['data'];
                        $weight = $weight + $_product->get_weight() * $values['quantity']; 
                    }
                    
                    $weight_unit = strtolower( get_option( 'woocommerce_weight_unit' ) );
                    $weight = wc_get_weight( $weight, $weight_unit );
                    
                    $shipstation_rates = array();

					foreach( $this->custom_services as $carrier_code => $services ) {
						if( $services['enabled'] ) {
							$shipping_info['carrierCode']    = $carrier_code;
							$shipping_info['fromPostalCode'] = get_option( 'woocommerce_store_postcode' );
							$shipping_info['toCountry']      = $package["destination"]["country"];
							$shipping_info['toPostalCode']   = $package["destination"]["postcode"];
							$shipping_info['toCity']         = $package["destination"]["city"];
							$shipping_info['weight']         = array( 
																	'value' => $weight,
																	'units' => $weight_unit, 
																);
							$shipstation_rates               = $this->ss_shipstation_get_rates( $shipping_info );
							
							if( ! empty( $shipstation_rates ) ) {
								foreach( $shipstation_rates as $ss_rate ) {
									if( ! empty ( $ss_rate->serviceName ) ) {
										
										if (!array_key_exists($ss_rate->serviceCode, $services)) { continue; }
										// $spackages = $services[$ss_rate->serviceCode];
										// if (!array_key_exists($ss_rate->packageCode, $services)) { continue; }
										
										$total_amount = floatval( $ss_rate->shipmentCost );

										if( ! empty ( $ss_rate->otherCost ) ) {
											$total_amount += floatval( $ss_rate->otherCost );
										}

                                        foreach ($services[$ss_rate->serviceCode] as $key => $spackage){ 
                                            if($key != 'name' && $spackage['enabled']){
                                                // percent adjustment
                                                if ( ! empty( $spackage['adjustment_percent'] ) ) {
                                                    $total_amount = $total_amount + ( $total_amount * ( floatval( $spackage['adjustment_percent'] ) / 100 ) );
                                                }

                                                // Cost adjustment
                                                if ( ! empty( $spackage['adjustment'] ) ) {
                                                    $total_amount = $total_amount + floatval( $spackage['adjustment'] );
                                                }

                                                $rate = array(
                                                    'id'       => (string) $this->id . ':' . $ss_rate->serviceCode . ':' . $key,
                                                    'cost'     => (string) $total_amount,
                                                    'calc_tax' => 'per_order',
                                                );

                                                if( empty( $services[$ss_rate->serviceCode]['name'] ) ) {
                                                    $rate['label'] = (string) $ss_rate->serviceName;
                                                } else {
                                                    $rate['label'] = (string) $services[$ss_rate->serviceCode]['name'];
                                                }
                                                
                                                // Register the rate
                                                $this->add_rate( $rate );
                                            }
                                            
                                        }
                                    }
                                }
							}
                        }
                    }
                }
            }
        }
    }
    add_action( 'woocommerce_shipping_init', 'ss_shipping_method_init' );
    
    /**
     * Add custom shipping method class
     * 
     * @param array $methods
     * @return array
     */
    function add_ss_shipping_method( $methods ) {
        $methods[] = 'Ss_Shipstation_Shipping_Method';
        return $methods;
    }
    add_filter( 'woocommerce_shipping_methods', 'add_ss_shipping_method' );

    /**
     * Make API Call.
     * 
     * @param string $api_url
     * @return array
     */
    function ss_api_request( $api_url ) {

		$ss_shipping_setting = get_option( 'woocommerce_ss_shipping_method_settings' );
		
        //api headers
        $ss_auth = base64_encode( $ss_shipping_setting['api_key'] . ':' . $ss_shipping_setting['api_secret'] );
        $ss_headers = array(
            "Host: ssapi.shipstation.com",
            "Authorization: Basic $ss_auth"
        );
        
        $curl = curl_init();

        curl_setopt_array( $curl, array(
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $ss_headers,
        ) );

        $response = curl_exec($curl);

        curl_close($curl);
        
        return json_decode($response);
    }

    /**
     * Get Lists of Carriers.
     * 
     * @return array
     */
    function ss_shipstation_list_carriers() {
        $api_url  = 'https://ssapi.shipstation.com/carriers';
        $carriers = ss_api_request( $api_url );

        return $carriers;
    }

    /**
     * Get Lists of Services.
     * 
     * @param string $carriers_code
     * @return array
     */
    function ss_shipstation_list_services( $carriers_code ) {
        
        $api_url              = 'https://ssapi.shipstation.com/carriers/listservices?carrierCode=' . $carriers_code;
        $shipstation_services = ss_api_request( $api_url );
        
        return $shipstation_services;
    }


    /**
     * Get Lists of Packages.
     * 
     * @param string $carriers_code
     * @return array
     */
    function ss_shipstation_list_packages( $carriers_code ) {
        
        $api_url              = 'https://ssapi.shipstation.com/carriers/listpackages?carrierCode=' . $carriers_code;
        $shipstation_packages =  ss_api_request( $api_url );
        
        return $shipstation_packages;
    }

    /**
     * store api data
     * 
     * @return void
     */
    function set_admin_services_data() {

        $services_data = array();
        $carriers = ss_shipstation_list_carriers();
        
        foreach($carriers as $key => $carrier) {
            if ($carrier->code != "ups_walleted") { continue; }
            $services_data[$carrier->code] = ss_shipstation_list_services( $carrier->code );
            foreach($services_data[$carrier->code] as $skey => $service) {
                $services_data[$carrier->code][$skey]->carrier_name  = $carrier->name;
                $services_data[$carrier->code][$skey]->list_packages = array();
                $services_data[$carrier->code][$skey]->list_packages = ss_shipstation_list_packages( $carrier->code );
            }
        }
        
        update_option( 'ss_shipping_data', $services_data );
    }

    /**
     * Get all cron schedules
     * 
     * @param array $schedules
     * @return array
     */
    /*add_filter( 'cron_schedules', 'ss_cron_schedules' );
    function ss_cron_schedules( $schedules ){

        if( ! isset( $schedules["1min"] ) ){
            $schedules["1min"] = array(
                'interval' => 1*60,
                'display' => __('Once every minutes'));
        }

        return $schedules;
    }*/

    /**
     * Register plugin activation hook
     * 
     * @return void
     */
    register_activation_hook( __FILE__, 'ss_activation' );
    add_action( 'ss_store_api_data', 'set_admin_services_data' );
    function ss_activation() {
        if ( ! wp_next_scheduled ( 'ss_store_api_data' )) {
            wp_schedule_event( time(), 'daily', 'ss_store_api_data' );
        }
    }

    /**
     * Register plugin deactivation hook
     * 
     * @return void
     */
    register_deactivation_hook( __FILE__, 'ss_deactivation' );
    function ss_deactivation() {
        wp_clear_scheduled_hook( 'ss_store_api_data' );
	}
}
