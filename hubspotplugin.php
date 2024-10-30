<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
  Plugin Name: HubPress - Wordpress to HubSpot Publisher Tracker
  Plugin URI: http://kidesign.io
  Description: HubPress integrates WordPress content and tracking directly into the HubSpot Social Publishing pipeline
  Version: 2.1.0 
  Author: Wael Hassan 
  Author URI: http://waelhassan.com 
 */


//=============================================
// Define Constants
//=============================================

if ( !defined('HUBSPOT_TRACKING_CODE_PATH') )
    define('HUBSPOT_TRACKING_CODE_PATH', untrailingslashit(plugins_url('', __FILE__ )));

if ( !defined('HUBSPOT_TRACKING_CODE_PLUGIN_DIR') )
	define('HUBSPOT_TRACKING_CODE_PLUGIN_DIR', untrailingslashit(dirname( __FILE__ )));

if ( !defined('HUBSPOT_TRACKING_CODE_PLUGIN_SLUG') )
	define('HUBSPOT_TRACKING_CODE_PLUGIN_SLUG', basename(dirname(__FILE__)));

if ( !defined('HUBSPOT_TRACKING_CODE_PLUGIN_VERSION') )
	define('HUBSPOT_TRACKING_CODE_PLUGIN_VERSION', '1.1.0');


require_once dirname(__FILE__) . '/XmlParser.php';

class HubspotPlugin {

    public function init() {
        if (isset($_GET['sync_hub_spot'])) {
            $this->syncHubSpot();
            
        }
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('save_post', array($this, 'onSave'));
    }

    function onSave($post_id) {
        if (wp_is_post_revision($post_id))
            return;

        $this->syncHubSpot();
        
    }
    
    public function getBetween($left, $right, $source, $offset = 1) {
        $step1 = explode($left, $source);
        if (count($step1) < 2 + $offset - 1) {
            return false;
        }
        $step2 = explode($right, $step1[1 + $offset - 1]);
        if (isset($step2[0])) {
            return trim(preg_replace('/\s\s+/', ' ', $step2[0]));
        }
        return false;
    }

    public function getCurl() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_REFERER, 'http://google.pl');
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/6.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
        curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookies.txt');
        curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookies.txt');
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        return $curl;
    }

    public function social_media_message() {
        $rss_url = get_option('rss_url');

        $posts = $this->getPosts($rss_url);

        $hubspot_api_key = get_option('hubspot_api_key');

        $url = 'https://api.hubapi.com/broadcast/v1/broadcasts?hapikey=' . $hubspot_api_key;
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, $url);
        $all_messages = json_decode(curl_exec($curl));

        $publishing_channel = get_option('publishing_channel');
        if (empty($publishing_channel)) {
            return false;
        }

        

        foreach ($posts as $post) {
            $description = $post->getNode('contentencoded')->getValue();
            $title = trim($post->getNode('title')->getValue());

            $body = trim($description);

            $add = true;
            foreach ($all_messages as $message) {
                $cb = trim($message->content->originalBody);
                if ($cb === $body) {
                    $add = false;
                }
            }

            if ($add) {
                foreach ($publishing_channel as $pc) {
                    $url = 'https://api.hubapi.com/broadcast/v1/broadcasts?hapikey=' . $hubspot_api_key;
                    $curl = $this->getCurl();
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_POST, true);
                    $data = array(
                        'channelGuid' => $pc,
                        'content' => array(
                            'body' => $body
                        )
                    );
                    $payload = json_encode($data);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                    $id = json_decode(curl_exec($curl));
                }
                $this->addToLog('The "' . $title . '" has been added as Social Message (Hubspot).');
            }
        }
    }

    public function getPosts() {
        $rss_url = get_option('rss_url');
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, $rss_url);
        $rss = curl_exec($curl);
        $parser = new XmlParser($rss);
        $posts = $parser->getNode('channel')->getNodes('item');
        return $posts;
    }
    
    public function syncPardot()
    {
        $use_pardot = get_option('use_pardot');
        $pardot_added = get_option('pardot_added');
        if(!$pardot_added)
        {            
            $pardot_added = update_option('pardot_added',array());
            $pardot_added = get_option('pardot_added');
            
        }
        
        if($use_pardot!=='yes')
        {
            return true;
        }
        $rss_url = get_option('rss_url');
        $posts = $this->getPosts($rss_url);
      
        
        foreach($posts as $post)
        {
            
        }
        
        
    }

    public function syncHubSpot() {
        $hubspot_api_key = get_option('hubspot_api_key');
        $use_hubspot = get_option('use_hubspot');
        if($use_hubspot!=='yes')
        {
            return true;
        }
        $rss_url = get_option('rss_url');
        $post_as = get_option('post_as');
        set_time_limit(3600);

        if (trim($hubspot_api_key) != '' && trim($rss_url) != '') {

            if ($post_as == 'social_media_message') {
                $this->social_media_message();
              
            }

            $url = 'https://api.hubapi.com/content/api/v2/blogs?hapikey=' . $hubspot_api_key;
            $curl = $this->getCurl();
            curl_setopt($curl, CURLOPT_URL, $url);
            $all_blogs = json_decode(curl_exec($curl));

            $url = 'https://api.hubapi.com/blogs/v3/blog-authors?hapikey=' . $hubspot_api_key;
            $curl = $this->getCurl();
            curl_setopt($curl, CURLOPT_URL, $url);
            $all_authors = json_decode(curl_exec($curl));

            $url = 'https://api.hubapi.com/content/api/v2/blog-posts?hapikey=' . $hubspot_api_key . '&limit=200';

            $curl = $this->getCurl();
            curl_setopt($curl, CURLOPT_URL, $url);
            $all_posts = json_decode(curl_exec($curl));

            $posts = $this->getPosts($rss_url);

            $new_post_status = get_option('new_post_status');

            if (!empty($posts) && !empty($all_blogs->objects) && !empty($all_authors->objects)) {
                foreach ($posts as $post) {
                    $description = $post->getNode('contentencoded')->getValue();

                    $title = trim($post->getNode('title')->getValue());
                    $pubDate = strtotime($post->getNode('pubDate')->getValue());

                    $add = true;

                    foreach ($all_posts->objects as $p) {
                        $t = trim(strip_tags($p->html_title));

                        if ($t == $title) {
                            $add = false;
                        }
                    }



                    if ($add) {

                        $url = 'https://api.hubapi.com/content/api/v2/blog-posts?hapikey=' . $hubspot_api_key;
                        $curl = $this->getCurl();
                        curl_setopt($curl, CURLOPT_URL, $url);
                        curl_setopt($curl, CURLOPT_POST, true);
                        $data = array(
                            'post_body' => $description,
                            'blog_author_id' => $all_authors->objects[0]->id,
                            'name' => $title,
                            'meta_description' => $title,
                            'publish_date' => $pubDate * 1000,
                            'content_group_id' => $all_blogs->objects[0]->id
                        );
                        $payload = json_encode($data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                        $id = json_decode(curl_exec($curl));

                        $this->addToLog('The "' . $title . '" has been added to blog (Hubspot)');

                        if ($new_post_status == 'publish') {
                            /* publish the post */
                            $url = 'https://api.hubapi.com/content/api/v2/blog-posts/' . $id->id . '/publish-action?hapikey=' . $hubspot_api_key;
                            $data = array(
                                'action' => 'schedule-publish',
                                'blog_post_id' => $id->id
                            );
                            $payload = json_encode($data);

                            $curl = $this->getCurl();
                            curl_setopt($curl, CURLOPT_URL, $url);
                            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
                            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                            curl_setopt($curl, CURLOPT_POST, true);
                            $res = curl_exec($curl);
                            $this->addToLog('The "' . $title . '" has been published (Hubspot)');
                        }
                    }
                }
            }
            //var_dump($all_posts->objects);
        }
    }

    public function addToLog($line) {
        $l = '[' . time() . ']' . $line . "\n";

        file_put_contents(dirname(__FILE__) . '/log.txt', $l, FILE_APPEND);
    }

    public function log() {
        $log = file_get_contents(dirname(__FILE__) . '/log.txt');
        $lines = explode("\n", $log);
        array_reverse($lines);

        $lines = array_slice($lines, 0, 200);

        require dirname(__FILE__) . '/tmpl/log.php';
    }

    public function adminMenu() {
        add_menu_page('Hubspot Plugin', 'Hubspot Plugin', 'manage_options', 'hubspot_plugin', array($this, 'settings'));
        add_submenu_page('hubspot_plugin', 'FAQ', 'FAQ', 'manage_options', 'faq', array($this, 'faq'));
        add_submenu_page('hubspot_plugin', 'Log', 'Log', 'manage_options', 'log', array($this, 'log'));
        add_submenu_page('hubspot_plugin', 'Support', 'Support', 'manage_options', 'support', array($this, 'support'));
        add_submenu_page('hubspot_plugin', 'Tracking Code', 'Tracking Code', 'manage_options', 'tracking-code', array($this, 'hubspot_plugin_options'));
    }
    
    public function support() {
        require dirname(__FILE__) . '/tmpl/support.php';
    }

    public function faq() {
        require dirname(__FILE__) . '/tmpl/faq.php';
    }
    
    /**
     * Creates settings page
     */
    function hubspot_plugin_options ()
    {
        ?>
        <div class="wrap">
        	<div class="dashboard-widgets-wrap">
	            <h2><?php _e ( esc_html('HubSpot Tracking Code Settings'), 'hubspot-plugin-advance' );  ?></h2>
                <form method="POST" action="options.php">
                	<div id="dashboard-widgets" class="metabox-holder">
	                	<div class="postbox-container" style="width:60%;">
	                		<div class="meta-box-sortables ui-sortable">
						        <div class="postbox">
						        	<h3 class="hndle"><span><?php _e ( esc_html('Settings'), 'hubspot-plugin-advance' );  ?></span></h3>
						        	<div class="inside">
						        		<?php _e ( esc_html('Enter your Hub ID below to track your WordPress site in HubSpot\'s analytics system.'), 'hubspot-plugin-advance' );  ?>
						        		<?php
					                        settings_fields('hubspot_settings_options');
					                        do_settings_sections(HUBSPOT_TRACKING_CODE_ADMIN_PATH);
					                    ?>
						        	</div>

						        </div>
						    </div>
							<?php submit_button('Save Settings'); ?>
			            </div>

			            <div class="postbox-container" style="width:40%;">
			            	<div class="meta-box-sortables ui-sortable">
						        <div class="postbox">
						        <h3 class="hndle"><span><?php _e ( esc_html('Where is my HubSpot Hub ID?'), 'hubspot-plugin-advance' );  ?></span></h3>
						        	<div class="inside">
										<?php _e ('<p><b>I\'m setting up HubSpot for myself</b><br><a target=\'_blank\' href=\'https://app.hubspot.com/\'>Log in to HubSpot</a>. Your Hub ID is in the upper right corner of the screen.</p>
										<img style="max-width: 100%;" src="http://cdn2.hubspot.net/hubfs/250707/CRM_Knowledge/Sidekick/HubID.jpg?t=1437426192644"/>
										<p><b>I\'m setting up HubSpot for someone else</b><br>If you received a "HubSpot Tracking Code Instructions" email, this contains the Hub ID.</p>
										<p><b>I\'m interested in trying HubSpot</b><br> <a target=\'_blank\' href=\'http://offers.hubspot.com/free-trial\'>Sign up for a free 30-day trial</a> to get your Hub ID assigned.</a></p>
                                        ', 'hubspot-plugin-advance');
                                        ?>
						        	</div>
						        </div>
						    </div>
					    </div>
			        </div>
                </form>
	        </div>
        </div>
        <?php
    }

    public function settings() {
        
        if (isset($_POST['save-hubspot']) &&  current_user_can('manage_options') ) {
            
            if ( check_admin_referer('hubspot_action','hubspot_nonce_field') ){
                
                if(isset($_POST['use_hubspot'])){
                    
                    if($_POST['use_hubspot'] == "no" || $_POST['use_hubspot'] == "yes"){
                        
                        $db_array_data['use_hubspot'] = $_POST['use_hubspot'];
                        
                    }else{
                        
                        $errors .= 'Something wrong with Hubspot activation.<br/><br/>';
                        
                    }
                    
                }else{
                    
                    $errors .= 'Hubspot activation couldn\'t be empty.<br/><br/>';
                    
                }
                
                if(isset($_POST['new_post_status'])){
                    
                    $_post_options = array( 'draft','publish');
                    
                    if( in_array($_POST['new_post_status'], $_post_options)  ){
                        
                        $db_array_data['new_post_status'] = $_POST['new_post_status'];
                        
                    }else{
                        
                        $errors .= 'Something wrong with New Post Status.<br/><br/>';
                        
                    }
                    
                }else{
                    
                    $errors .= 'New Post Status couldn\'t be empty.<br/><br/>';
                    
                }
                
                if(isset($_POST['hubspot_api_key']) && !empty($_POST['hubspot_api_key']) ){
                    
                    $db_array_data['hubspot_api_key'] = sanitize_text_field($_POST['hubspot_api_key']); 
                    
                    if ($db_array_data['hubspot_api_key'] == "" || !is_string($db_array_data['hubspot_api_key']) ) {
                        
                        $errors .= 'Hubspot Api Key validation error<br/><br/>';
                        
                    }
                    
                }
                
                
                if(isset($_POST['post_as'])){
                    
                    $_post_options = array( 'blog_post','social_media_message');
                    
                    if( in_array($_POST['post_as'], $_post_options)  ){
                        
                        $db_array_data['post_as'] = $_POST['post_as'];
                        
                    }else{
                        
                        $errors .= 'Something wrong with Post As.<br/><br/>';
                        
                    }
                    
                }else{
                    
                    $errors .= 'Post As couldn\'t be empty.<br/><br/>';
                    
                }
                
                if(isset($_POST['rss_url']) && !empty($_POST['rss_url']) ){
                    
                    $url = esc_url($_POST['rss_url']);

                    // Validate url
                    if ( empty($url) ) {
                        
                        $errors .= "$url is not a valid URL.<br/><br/>";
                        
                    } else{
                        
                        $db_array_data['rss_url'] = $url;
                        
                    }
                    
                }
                
                if(isset($_POST['pardot_api_key']) && !empty($_POST['pardot_api_key']) ){
                        
                    $db_array_data['pardot_api_key'] = sanitize_text_field($_POST['pardot_api_key']); 
                    
                    if ($db_array_data['pardot_api_key'] == "" || !is_string($db_array_data['pardot_api_key']) ) {
                        
                        $errors .= 'Pardot Api Key validation error<br/><br/>';
                        
                    }
                    
                }
                
                if($errors == false){
                    
                    update_option('use_hubspot', esc_sql($db_array_data['use_hubspot']));
                    update_option('new_post_status', esc_sql($db_array_data['new_post_status']));
                    update_option('hubspot_api_key', esc_sql($db_array_data['hubspot_api_key']));
                    update_option('post_as', esc_sql($db_array_data['post_as']));
                    
                    
                    
                    if(isset($db_array_data['rss_url'])){
                        update_option('rss_url', esc_sql($db_array_data['rss_url']));
                    }else{
                        update_option('rss_url', '');
                    }
                    
                    update_option('pardot_api_key', esc_sql($db_array_data['pardot_api_key']));
                    update_option('publishing_channel', esc_sql($_POST['publishing_channel']));
                    
                    
                }else{
                    
                    echo "<div style='color:red'>".$errors."</div>";
                    
                }
                
            }
               
        }
        $rss_url = get_option('rss_url');
        $hubspot_api_key = get_option('hubspot_api_key');
        $new_post_status = get_option('new_post_status');
        $post_as = get_option('post_as');
        $publishing_channel = get_option('publishing_channel');
        
        $use_hubspot = get_option('use_hubspot');
        $use_pardot = get_option('use_pardot');

        /* var_dump($post_as); */
        if (trim($rss_url) == '') {
            $url = get_home_url() . '/rss';
            update_option('rss_url', $url);
            $rss_url = get_option('rss_url');
        }

        if (trim($hubspot_api_key) != '') {
            $url = 'https://api.hubapi.com/broadcast/v1/channels/setting/publish/current?hapikey=' . $hubspot_api_key;
            $curl = $this->getCurl();
            curl_setopt($curl, CURLOPT_URL, $url);
            $channels = json_decode(curl_exec($curl));
        }

        require dirname(__FILE__) . '/tmpl/settings.php';
    }

    function getPardot()
    {
        $curl = $this->getCurl();
        curl_setopt($curl, CURLOPT_URL, 'https://pi.pardot.com/api/login/version/3');
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array(
            'email'=>$_POST['pardot_email'],
            'password'=>$_POST['pardot_password'],
            'user_key'=>$_POST['pardot_user_api_key']
        ));
        $result = curl_exec($curl);
       
        
        $key = $this->getBetween('<api_key>', '</api_key>', $result);
        if($key)
        {
            echo $key;
        }
        else
        {
            echo '-1';
        }
        die();
    }
    
}

$hp = new HubspotPlugin();
add_action('init', array($hp, 'init'));
add_action('wp_ajax_get_pardot',array($hp,'getPardot'));


//=============================================
// Include Needed Files
//=============================================

require_once(HUBSPOT_TRACKING_CODE_PLUGIN_DIR . '/inc/hubspot-tracking-code-functions.php');
require_once(HUBSPOT_TRACKING_CODE_PLUGIN_DIR . '/inc/class-hubspot-tracking-code.php');
require_once(HUBSPOT_TRACKING_CODE_PLUGIN_DIR . '/inc/class-hubspot-tracking-code-analytics.php');
require_once(HUBSPOT_TRACKING_CODE_PLUGIN_DIR . '/admin/hubspot-tracking-code-admin.php');

//=============================================
// Hooks & Filters
//=============================================

/**
 * Activate the plugin
 */
function hubspot_tracking_code_activate ( $network_wide )
{
	// Check activation on entire network or one blog
	if ( is_multisite() && $network_wide )
	{
		global $wpdb;

		// Get this so we can switch back to it later
		$current_blog = $wpdb->blogid;

		// Get all blogs in the network and activate plugin on each one
		$q = "SELECT blog_id FROM $wpdb->blogs";
		$blog_ids = $wpdb->get_col($q);
		foreach ( $blog_ids as $blog_id )
		{
			switch_to_blog($blog_id);
			hubspot_tracking_code_setup_plugin();
		}

		// Switch back to the current blog
		switch_to_blog($current_blog);
	}
	else
	{
		hubspot_tracking_code_setup_plugin();
	}
}

/**
 * Check Super Simple Landing Pages installation and register custom post type
 */
function hubspot_tracking_code_setup_plugin ( )
{
	$options = get_option('hs_settings');

	if ( ! isset($options['hs_installed']) || $options['hs_installed'] != "on" || (!is_array($options)) )
	{
		$opt = array(
			'hs_installed'	=> "on",
			'hs_version'	=> HUBSPOT_TRACKING_CODE_PLUGIN_VERSION
		);

		// this is a hack because multisite doesn't recognize local options using either update_option or update_site_option...
		if ( is_multisite() )
		{
			global $wpdb;

			$multisite_prefix = ( is_multisite() ? $wpdb->prefix : '' );
			$q = $wpdb->prepare("
				INSERT INTO " . $multisite_prefix . "options
			        ( option_name, option_value )
			    VALUES ('hs_settings', %s)", serialize($opt));
			$wpdb->query($q);
		}
		else
			update_option('hs_settings', $opt);
	}
}

function hubspot_tracking_code_activate_on_new_blog ( $blog_id, $user_id, $domain, $path, $site_id, $meta )
{
	global $wpdb;

	if ( is_plugin_active_for_network('hubspotplugin/hubspotplugin.php') )
	{
		$current_blog = $wpdb->blogid;
		switch_to_blog($blog_id);
		hubspot_tracking_code_setup_plugin();
		switch_to_blog($current_blog);
	}
}

/**
 * Checks the stored database version against the current data version + updates if needed
 */
function hubspot_tracking_code_init ()
{
	if ( is_plugin_active('hubspot/hubspot.php') )
	{
		remove_action( 'plugins_loaded', 'hubspot_tracking_code_init' );
     	deactivate_plugins(plugin_basename( __FILE__ ));

		add_action( 'admin_notices', 'deactivate_hubspot_tracking_code_notice' );
	    return;
	}

    $hubspot_wp = new HubSpotTrackingCode();
}

add_action( 'plugins_loaded', 'hubspot_tracking_code_init', 14 );

if ( is_admin() )
{
	// Activate + install Super Simple Landing Pages
	register_activation_hook( __FILE__, 'hubspot_tracking_code_activate');

	// Activate on newly created wpmu blog
	add_action('wpmu_new_blog', 'hubspot_tracking_code_activate_on_new_blog', 10, 6);
}

function deactivate_hubspot_tracking_code_notice ()
{
    ?>
    <div id="message" class="error">
        <?php _e(
        	'<p><h3>HubSpot Tracking Code plugin wasn\'t activated because your HubSpot for WordPress plugin is still activated...</h3></p>' .
        		'<p>HubSpot Tracking Code and HubSpot for WordPress are like two rival siblings - they don\'t play nice together, but don\'t panic - it\'s an easy fix. Deactivate <b><i>HubSpot for WordPress</i></b> and then try activating <b><i>HubSpot Tracking Code for WordPress</i></b> again, and everything should work fine.</p>' .
        	'<p>By the way - make sure you replace all your form and CTA shortcodes with <a href="http://help.hubspot.com/articles/KCS_Article/Integrations/How-to-switch-from-the-HubSpot-for-Wordpress-plugin-to-the-HubSpot-Tracking-code-for-Wordpress-plugin" target="_blank">HubSpot embed codes</a></p>',
        	'hubspot-plugin-advance'
        ); ?>
    </div>
    <?php
}