<?php
/*
	Plugin Name: Flash API
	Description: This plugin serves as a faux webservice that outputs data from the WP Database to a flash application
	Version: 2.0.2
	Author: Cameron Tullos - Illumifi Interactive
	Author URI: http://illumifi.net/

	Copyright 2010  Illumifi Interactive  (email: c.tullos at illumifi dot net)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
	
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

// Definitions
define('FAPI_PLUGIN_URL', WP_PLUGIN_URL . '/flash-api');
define('FAPI_PLUGIN_DIR', WP_PLUGIN_DIR . '/flash-api');
define('SITE', get_bloginfo('wpurl') . '/'); 
define('FAPI_DEFAULT_KEY', md5(rand(1000, 9000))); 

// Include jquery
wp_enqueue_script('jquery');

// Init
add_action('plugins_loaded', 'flash_api_init');

function flash_api_init() {
	$apiKey = get_option('flash_api_key');
	
	delete_option('flash_api_tag');
	
	if (!$apiKey) { $apiKey = add_option('flash_api_key', FAPI_DEFAULT_KEY); }

	define('FAPI_KEY', $apiKey);
	
	$style = FAPI_PLUGIN_URL . '/style.css';
	wp_register_style('flashapiStyleSheet', $style);
}

// Menu
add_action('admin_menu', 'flash_api_menu');
function flash_api_menu() {
	$opt_plugin = add_options_page('Flash API', 'Flash API', 'administrator', __FILE__, 'flash_api_form');
	
	add_action('admin_print_scripts-'.$opt_plugin, 'flash_api_scripts'); 
	add_action('admin_print_styles-' . $opt_plugin, 'flash_api_style');
}

// load scripts
function flash_api_scripts() { 
	wp_enqueue_script('jquery');
	wp_enqueue_script('md5', FAPI_PLUGIN_URL . '/js/MD5.js');
	wp_register_script('md5', FAPI_PLUGIN_URL . '/js/MD5.js');
	wp_enqueue_script('flash_api', FAPI_PLUGIN_URL . '/js/flash_api.js', array('jquery', 'md5'));
	wp_register_script('flash_api', FAPI_PLUGIN_URL . '/js/flash_api.js');
}

// load styles
function flash_api_style() { 
	wp_enqueue_style('flashapiStyleSheet'); 
}

// draw the admin form
function flash_api_form() {
	$api_key = get_option('flash_api_key');
	$warning = (FAPI_KEY == FAPI_DEFAULT_KEY) ? flash_api_help(1) : ''; 
	$func = FAPI_PLUGIN_URL . '/wsrv.php';
	?>
	<div class="wrap">
		<form name="flash_api_form" method="post" action="options.php">
		<h2><?php _e('Flash API Settings'); ?></h2>
		<table class="form-table" style="width: 620px">
			<tr valign="top">
				<td colspan="3"><?php _e(flash_api_help(0)); ?></td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Application Key'); ?></th>
				<td><input type="text" name="flash_api_key" value="<?php echo $api_key; ?>" size="55" id="flash_api_key" /><?php _e($warning); ?></td>
				<td><input type="button" class="button-secondary" name="generate" id="generate" value="<?php _e('Generate'); ?>" /></td>
			</tr>
			<tr>
				<th scope="row">Web Service</th>
				<td colspan="2"><a href="<?php echo $func; ?>" target="_blank"><?php echo $func; ?></a></td>
			</tr>
			<tr>
				<td colspan="3">
					<input type="submit" class="button-primary" name="Submit" value="<?php _e('Update'); ?>" />
					<input type="hidden" name="action" value="update">
					<input type="hidden" name="page_options" value="flash_api_key" />            
					<?php wp_nonce_field('update-options'); ?>
				</td>
			</tr>
		</table>
		</form>
	</div>
<?php
}


// help information
function flash_api_help($id) { 
	$help = array();
	array_push($help, "Create an Application Key below. This key can be sent from your flash appliation via <b>GET</b> or <b>POST</b>. In order to send or receive data from your web service use the <b>apiKey</b> and <b>service</b> url variable; plus any other variables required by the service.");
	array_push($help, "<br><small>It's highly recommended that you change your API Key.</small>");
	array_push($help, "<div>Add documentation to your API functions by creating a new <a href='post-new.php'>post</a> and adding it to the '<b><span id='cdx_cat'>".FAPI_TAG."</span></b>' category.<br>Your post will then show up in the section below.</div><br><hr><br><br>"); 

	return __($help[$id]);
}

// User profile hook
add_action('show_user_profile', 'fapi_user_profile_hook');
add_action('edit_user_profile', 'fapi_user_profile_hook');

function fapi_user_profile_hook($user) {
	$apiKey = get_user_meta($user->ID, 'apiKey', true);	
	$apiUrl = get_user_meta($user->ID, 'apiUrl', true);
	$perm = current_user_can('add_users'); 
	$readOnly = (!$perm) ? 'readonly="readonly"' : '';
	
	echo '<h3>Flash API</h3>
	<script src="'.FAPI_PLUGIN_URL.'/js/MD5.js" type="text/javascript"></script>
	<script src="'.FAPI_PLUGIN_URL.'/js/flash_api.js" type="text/javascript"></script>
	<table class="form-table">
		<tr>
			<th><label>API Domain</label></th>
			<td><input type="text" name="flash_api_url" id="flash_api_url" value="'.$apiUrl.'" class="regular-text" '.$readOnly.' /><i>Example: '.$_SERVER['HTTP_HOST'].'</td>
		</tr>
		<tr>
			<th><label>API Key</label></th>
			<td><input type="text" name="flash_api_key" id="flash_api_key" value="'.$apiKey.'" class="regular-text" '.$readOnly.' />';
			if ($perm) { echo '<input type="button" class="button-secondary" name="generate" id="generate" value="'.__("Generate").'" /></td>'; }
		echo '</tr>
	</table>';
}

// User profile update
add_action('personal_options_update', 'fapi_user_apiKey_save');
add_action('edit_user_profile_update', 'fapi_user_apiKey_save');

function fapi_user_apiKey_save($user_id) {
	if (!current_user_can('edit_user', $user_id)) { return false; }
	
	$apiKey = $_POST['flash_api_key'];
	$apiUrl = $_POST['flash_api_url'];
	$apiUrl = str_replace('https://', '', $apiUrl);
	$apiUrl = str_replace('http://', '', $apiUrl);
	$apiUrlARR = explode('/', $apiUrl); 
	$apiUrl = $apiUrlARR[0];
	
	update_usermeta($user_id, 'apiUrl', $apiUrl);
	update_usermeta($user_id, 'apiKey', $apiKey);
}

// Custom posts
// register post type: Flash API -> fapi
add_action('init', 'flash_api_register');

function flash_api_register() {
	
	$labels = array('add_new' => _('Add Function'),
		'add_new_item' => __('Add New Function'),
		'edit' => _('Edit'),
		'edit_item' => __('Edit Function'),
		'name' => __('API Functions'),
		'new_item' => __('New Function'),
		'not_found' => __('No functions found'),
		'not_found_in_trash' => __('No functions found in trash'),
		'search_items' => __('Search Functions'),
		'singular_name' => _('Function'),
		'view' => __('View Function'),
		'view_item' => __('View Function'));

	$args = array(
		'public' => true,
		'show_ui' => true,
		'capability_type' => 'post',
		'hierarchical' => false,
		'rewrite' => array('slug'=>'API', 'with_front'=>false),
		'labels' => $labels,
		'supports' => array('title', 'editor', 'comments', 'author'),
		'taxonomies' => array('post_tag'));

	register_post_type('fapi',$args);		
}

// disable the autosave
function disable_autosave() {
	wp_deregister_script('autosave');
}
add_action('wp_print_scripts', 'disable_autosave');

// options 
add_action("admin_init", "fapi_admin_init");
add_action('save_post', 'fapi_save_options');

function fapi_admin_init() {
	add_meta_box("fapi-meta", "Flash API Options", "fapi_metabox", "fapi", "side", "high");
}

function fapi_metabox() {
	global $post;
	$custom = get_post_custom($post->ID);
	$incPath = $custom['fapi-include-path'][0];
	?>
	
	<b>Include Path</b><br />
	<input name="fapi-include-path" value="<?php echo $incPath; ?>" class="postbox" style="width: 80% !important; margin-top: 8px !important;" />
	<?php
}

function fapi_save_options() {
	global $post;
	if ($post->post_type != 'fapi') { return; }
	$fields = array('fapi-include-path'); 
	foreach($fields as $field) { update_post_meta($post->ID, $field, $_POST[$field]); }
}

// Extensions (functions that can be used through out wp) 

/**
 * @desc API function list
 * @return Returns an associative array of each api call and it's include path
 */
function flash_api_functions() { /** this function will need to be changed after custom post types have been added **/
	$is_editor = (current_user_can('publish_posts')) ? true : false; 
	$services = array(); 
	$args = array(
		'nopaging' => true,
		'order' => 'ASC',
		'orderby' => 'title',
		'post_status' => ($is_editor) ? 'any' : 'publish',
		'post_type' => 'fapi'
	);
	
	$loop = get_posts($args); 
	
	foreach ($loop as $item) { 
		setup_postdata($item); 
		
		// skip unnecessary post types
		if ($is_editor && ($item->post_type == 'trash' || $item->post_type == 'auto-draft')) { continue; }
		
		// check if we have the function name
		if (!$item->post_title) { continue; }
		
		// get the meta data
		$cust = get_post_custom($item->ID);
		
		// check if we have the include path
		if (strlen($cust['fapi-include-path'][0]) < 5) { continue; }
		
		// add the service to the array
		$services[$item->post_title] = $cust['fapi-include-path'][0]; 
	}
	
	ksort($services);
	return $services;
}

?>