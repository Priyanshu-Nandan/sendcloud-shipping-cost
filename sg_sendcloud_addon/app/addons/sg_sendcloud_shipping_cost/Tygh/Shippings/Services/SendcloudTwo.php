<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2024 Priyanshu Nandan                                             *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Registry;
use Tygh\Http;

/**
 * SendCloud shipping service
 */
class SendcloudTwo implements IService
{
    /**
     * Availability multithreading in this module
     *
     * @var array $_allow_multithreading
     */
    private $_allow_multithreading = true;


    /**
     * Sets data to internal class variable
     *
     * @param array $shipping_info
     */
    public function prepareData($shipping_info)
    {
        $this->_shipping_info = $shipping_info;
    }

    /**
     * Gets shipping cost and information about possible errors
     *
     * @param  string $response Response from Shipping service server
     * @return array  Shipping cost and errors
     */
    public function processResponse($response)
    {
        $return = array(
            'cost' => false,
            'error' => false,
            'delivery_time' => false,
        );
        
        $response = json_decode($response, true);
fn_print_die($this->_shipping_info, $response);
        if (!empty($response['error'])) {
            $return['error'] = $response['error'];
            return $return;
        }

        if (isset($response[0]['price'])) {
            $base_price = (float)$response[0]['price'];
            $surcharge = isset($this->_shipping_info['service_params']['surcharge_amount']) 
                ? (float)$this->_shipping_info['service_params']['surcharge_amount'] 
                : 0;
            
            $return['cost'] = $base_price + $surcharge;
        } else {
            $return['error'] = __('sendcloud_shipping_error');
        }
        return $return;
    }

    /**
     * Gets error message from shipping service server
     *
     * @param  string $response Response from Shipping service server
     * @return string Text of error or false if no errors
     */
    public function processErrors($response)
    {
        if (isset($response['error'])) {
            return $response['error'];
        }

        return false;
    }

    /**
     * Checks if shipping service allows to use multithreading
     *
     * @return bool true if allow
     */
    public function allowMultithreading()
    {
        return $this->_allow_multithreading;
    }

    
    /**
     * Prepare request information
     *
     * @return array Prepared data
     */
    public function getRequestData()
    {
        $shipping_settings = $this->_shipping_info['service_params'];
        $package_info = $this->_shipping_info['package_info'];
        $shipping_id = $this->_shipping_info['shipping_id'];

        $sendcloud_creds = $this->getSendcloudCredentials();
        
        if (empty($sendcloud_creds['public_api_key']) || empty($sendcloud_creds['secret_api_key'])) {
            return array(
                'error' => __('sendcloud_credentials_missing')
            );
        }

        $username = $sendcloud_creds['public_api_key'];
        $password = $sendcloud_creds['secret_api_key'];
        $authCredentials = base64_encode($username . ':' . $password);

        // Calculate shipment weight
        $weight_data = (float) $package_info['W'];
        $shipment_weight = $weight_data * Registry::get('settings.General.weight_symbol_grams');
        $shipment_weight = $shipment_weight / 1000; 

        $shipping_method_id = $shipping_settings['sendcloud_method_id'];

        // Get company data for origin country
        $company_id = $this->_shipping_info['package_info']['company_id'] ?? 0;
        $company_data = !empty($company_id) ? fn_get_company_data($company_id) : array();
        $fromCountry = !empty($company_data['country']) ? trim($company_data['country']) : 'NL';
        
        // Get destination country
        $location = $this->prepareAddress($package_info['location']);
        $toCountry = $location['country'];

        // fn_print_die($fromCountry, $toCountry);
        // Prepare weight for API (convert to grams)
        $weight = round($shipment_weight * 1000, 3);
        $weightUnit = 'gram';

        // Build API URL
        $url = "https://panel.sendcloud.sc/api/v2/shipping-price?" . http_build_query(array(
            'from_country' => $fromCountry,
            'to_country' => $toCountry,
            'shipping_method_id' => $shipping_method_id,
            'weight' => $weight,
            'weight_unit' => $weightUnit
        ));

        $request_data = array(
            'method' => 'get',
            'url' => $url,
            'headers' => array(
                'Authorization: Basic ' . $authCredentials,
                'Content-Type: application/json',
            ),
            'credentials' => array(
                'username' => $username,
                'password' => $password
            ),
            'shipping_method_id' => $shipping_method_id,
            'weight' => $weight
        );
        return $request_data;
    }

    /**
     * Process simple request to shipping service server
     *
     * @return array Server response with price or error
     */
    public function getSimpleRates()
    {
        $data = $this->getRequestData();
        
        if (isset($data['error'])) {
            return $data;
        }

        $response = Http::get($data['url'], array(), array(
            'headers' => $data['headers']
        ));

        $response_data = json_decode($response, true);

        if ($response === false || json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'error' => __('sendcloud_connection_error')
            );
        }

        if (isset($response_data[0]['price'])) {
            $base_price = (float)$response_data[0]['price'];
            $surcharge = isset($this->_shipping_info['service_params']['surcharge_amount']) 
                ? (float)$this->_shipping_info['service_params']['surcharge_amount'] 
                : 0;
                
            return array(
                'price' => $base_price + $surcharge
            );
        } elseif (isset($response_data['error']['message'])) {
            return array(
                'error' => $response_data['error']['message']
            );
        } else {
            return array(
                'error' => __('sendcloud_unexpected_response')
            );
        }
    }

    /**
     * Get SendCloud credentials from add-on settings
     *
     * @return array SendCloud API credentials
     */
    private function getSendcloudCredentials()
    {
        $company_id = $this->_shipping_info['package_info']['company_id'] ?? 0;
        // if ($company_id) {
        //     $settings = fn_get_settings('sg_sendcloud_shipping_cost', 'general', $company_id);
        // } else {
        //     $settings = fn_get_settings('sg_sendcloud_shipping_cost', 'general');
        // }

        $settings = fn_sg_sendcloud_get_addon_settings();

        return array(
            'public_api_key' => $settings['public_api_key'] ?? '',
            'secret_api_key' => $settings['secret_api_key'] ?? '',
            'brand_id' => $settings['brand_id'] ?? ''
        );
    }

    /**
     * Fill required address fields
     *
     * @param array $address Address data
     * @return array Filled address data
     */
    public function prepareAddress($address)
    {
        $default_fields = array(
            'zipcode' => '',
            'country' => '',
            'city' => '',
            'address' => '',
            'address_2' => ''
        );

        return array_merge($default_fields, $address);
    }

    /**
     * Returns shipping service information
     * 
     * @return array information
     */
    public static function getInfo()
    {
        return array(
            'name' => __('carrier_sendcloud'),
            'tracking_url' => 'https://tracking.sendcloud.sc/parcels/%s'
        );
    }
}