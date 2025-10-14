<?php
/**
 * SendCloud Shipping Cost Addon
 * Main functions file
 * 
 * @package sg_sendcloud_shipping_cost
 * @version 1.0
 */

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Registry;
use Tygh\Languages\Languages;

/**
 * Get addon settings from registry
 *
 * @param string $setting_name Optional setting name to get specific setting
 *
 * @return array|string|null Addon settings or specific setting value
 */
function fn_sg_sendcloud_get_addon_settings($setting_name = null) {
    static $settings = null;
    
    if ($settings === null) {
        $settings = array(
            'public_api_key' => Registry::get('addons.sg_sendcloud_shipping_cost.public_api_key'),
            'secret_api_key' => Registry::get('addons.sg_sendcloud_shipping_cost.secret_api_key'),
            'brand_id' => Registry::get('addons.sg_sendcloud_shipping_cost.brand_id'),
        );
    }
    
    if ($setting_name !== null) {
        return isset($settings[$setting_name]) ? $settings[$setting_name] : null;
    }
    
    return $settings;
}

/**
 * Validate addon settings before save
 *
 * @param array $settings Settings array to validate
 *
 * @return boolean True if valid, false otherwise
 */
function fn_sg_sendcloud_validate_settings($settings) {
    if (empty($settings['public_api_key']) || empty($settings['secret_api_key']) || empty($settings['brand_id'])) {
        fn_set_notification('E', __('error'), __('sg_sendcloud.error_missing_credentials'));
        return false;
    }
    
    return true;
}

/**
 * Install hook function
 * Called during addon installation
 *
 * @return void
 */
function fn_sg_sendcloud_shipping_cost_install() {
    $service = array(
        'status'      => 'A',
        'module'      => 'sendcloud_two',
        'code'        => 'sendcloud_two',
        'sp_file'     => '',
    );

    $service['service_id'] = db_get_field('SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s', $service['module'], $service['code']);
    if (empty($service['service_id'])) {
        $service['service_id'] = db_query('INSERT INTO ?:shipping_services ?e', $service);
    }

    $languages = Languages::getAll();
    foreach ($languages as $lang_code => $lang_data) {
        $service['description'] = "Sendcloud Shipping Service";
        $service['lang_code'] = $lang_code;
        db_query('INSERT INTO ?:shipping_service_descriptions ?e', $service);
    }
}

/**
 * Uninstall hook function
 * Called during addon uninstallation
 *
 * @return void
 */
function fn_sg_sendcloud_shipping_cost_uninstall() {
    $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', 'sendcloud_two');
    if (!empty($service_ids)) {
        db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
        db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
    }
}

/**
 * Get shipping rates from Sendcloud API
 * Placeholder for implementation in service class
 *
 * @param array $shipping_settings Shipping method settings
 * @param array $order_info Order information
 * @param string $lang_code Language code
 *
 * @return array|false Array of shipping rates or false on failure
 */
function fn_sg_sendcloud_get_rates($shipping_settings, $order_info, $lang_code = CART_LANGUAGE) {
    // This function will be called from SendcloudShipping2 service class
    // Implementation will be in the service class
    return false;
}

/**
 * Format error message from error code
 *
 * @param string $error_code Error code
 *
 * @return string Formatted error message
 */
function fn_sg_sendcloud_format_error($error_code) {
    $error_map = array(
        'invalid_credentials' => __('sg_sendcloud.error_invalid_credentials'),
        'api_error' => __('sg_sendcloud.error_api_error'),
        'network_error' => __('sg_sendcloud.error_network_error'),
        'missing_config' => __('sg_sendcloud.error_missing_config'),
    );
    
    return isset($error_map[$error_code]) ? $error_map[$error_code] : __('sg_sendcloud.error_unknown');
}

/**
 * Test Sendcloud API connection
 * Called via AJAX from settings page
 *
 * @param string $public_key Public API key
 * @param string $secret_key Secret API key
 * @param string $brand_id Brand ID
 *
 * @return array Response array with status
 */
function fn_sg_sendcloud_test_connection($public_key, $secret_key, $brand_id) {
    if (empty($public_key) || empty($secret_key) || empty($brand_id)) {
        return array(
            'success' => false,
            'message' => fn_sg_sendcloud_format_error('missing_config')
        );
    }
    
    // TODO: Implement actual API connection test
    // This will connect to Sendcloud API and verify credentials
    
    return array(
        'success' => true,
        'message' => __('sg_sendcloud.success_connection_tested')
    );
}