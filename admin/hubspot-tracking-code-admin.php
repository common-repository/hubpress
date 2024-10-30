<?php

if ( !defined('HUBSPOT_TRACKING_CODE_PLUGIN_VERSION') )
{
    header('HTTP/1.0 403 Forbidden');
    die;
}

//=============================================
// Define Constants
//=============================================

if ( !defined('HUBSPOT_TRACKING_CODE_ADMIN_PATH') )
    define('HUBSPOT_TRACKING_CODE_ADMIN_PATH', untrailingslashit(__FILE__));

//=============================================
// Include Needed Files
//=============================================

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

//=============================================
// HubSpotTrackingCodeAdmin Class
//=============================================
class HubSpotTrackingCodeAdmin
{
    /**
     * Class constructor
     */
    function __construct ()
    {
        //=============================================
        // Hooks & Filters
        //=============================================

        $options = get_option('hs_settings');

        // If the plugin version matches the latest version escape the update function
        if ( $options['hs_version'] != HUBSPOT_TRACKING_CODE_PLUGIN_VERSION )
            self::hubspot_tracking_code_update_check();

        add_action('admin_init', array(&$this, 'hubspot_build_settings_page'));
        add_filter('plugin_action_links_' . HUBSPOT_TRACKING_CODE_PLUGIN_SLUG . '/hubspotplugin.php', array($this, 'hubspot_plugin_settings_link'));
    }

    function hubspot_tracking_code_update_check ()
    {
        $options = get_option('hs_settings');

        // Set the plugin version
        hubspot_tracking_code_update_option('hs_settings', 'hs_version', HUBSPOT_TRACKING_CODE_PLUGIN_VERSION);
    }

    
    //=============================================
    // Settings Page
    //=============================================

    /**
     * Adds setting link for HubSpot to plugins management page
     *
     * @param   array $links
     * @return  array
     */
    function hubspot_plugin_settings_link ( $links )
    {
        $url = get_admin_url() . 'admin.php?page=tracking-code';
        $settings_link = '<a href="' . $url . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Creates settings options
     */
    function hubspot_build_settings_page ()
    {
        global $pagenow;
        $options = get_option('hs_settings');

        register_setting(
            'hubspot_settings_options',
            'hs_settings',
            array($this, 'sanitize')
        );

        add_settings_section(
            'hubspot_settings_section',
            '',
            array($this, 'hs_settings_section_heading'),
            HUBSPOT_TRACKING_CODE_ADMIN_PATH
        );

        add_settings_field(
            'hs_portal',
            'Hub ID',
            array($this, 'hs_portal_callback'),
            HUBSPOT_TRACKING_CODE_ADMIN_PATH,
            'hubspot_settings_section'
        );
    }

    function hs_settings_section_heading ( )
    {
        $this->print_hidden_settings_fields();
    }

    function print_hidden_settings_fields ()
    {
         // Hacky solution to solve the Settings API overwriting the default values
        $options = get_option('hs_settings');

        $hs_installed = ( isset($options['hs_installed']) ? $options['hs_installed'] : 1 );
        $hs_version   = ( isset($options['hs_version']) ? $options['hs_version'] : HUBSPOT_TRACKING_CODE_PLUGIN_VERSION );

        printf(
            '<input id="hs_installed" type="hidden" name="hs_settings[hs_installed]" value="%d"/>',
            $hs_installed
        );

        printf(
            '<input id="hs_version" type="hidden" name="hs_settings[hs_version]" value="%s"/>',
            $hs_version
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize ( $input )
    {
        $new_input = array();

        $options = get_option('hs_settings');

        if ( isset($input['hs_portal']) )
            $new_input['hs_portal'] = sanitize_text_field($input['hs_portal']);

        if ( isset($input['hs_installed']) )
            $new_input['hs_installed'] = sanitize_text_field($input['hs_installed']);

        if ( isset($input['hs_version']) )
            $new_input['hs_version'] = sanitize_text_field($input['hs_version']);

        return $new_input;
    }

    /**
     * Prints Hub ID input for settings page
     */
    function hs_portal_callback ()
    {
        $options = get_option('hs_settings');
        $hs_portal  = ( isset($options['hs_portal']) && $options['hs_portal'] ? $options['hs_portal'] : '' );

        printf(
            '<input id="hs_portal" type="text" id="title" name="hs_settings[hs_portal]" style="width: 400px;" value="%s"/>',
            $hs_portal
        );

    }
}

?>
