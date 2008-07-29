<?php 
/*
Plugin Name: p2pConverter
Plugin URI: http://www.briandgoad.com/blog/p2pConverter
Version: 0.6
Author: Brian D. Goad, aka bbbco
Description: This plugin allows you to easily convert a post to a page and vice versa through an easy to use interface. You may either click on your Manage tab in Administration, and you will see a Convert option under 
		Posts and Pages sub-tabs, or click Convert while editing a post or page in the bottom right side bar. A p2pConverter role capability prevents unwanted users from converting pages (i.e. only Administrators and
		Editors have this ability), which can be adjusted by using a Role Manager plugin.
Author URI: http://www.briandgoad.com
*/
	
register_activation_hook(__FILE__,'p2p_install');
register_deactivation_hook(__FILE__,'p2p_uninstall');	
add_action('init', 'update_convert');

//Add p2p Capabilities to top two basic roles. Can be adjusted with Role Manager plugin.	
function p2p_install() {
	$role = get_role('administrator');
	$role->add_cap('p2pConverter');
	$role2 = get_role('editor');
	$role2->add_cap('p2pConverter');
}

//Removes p2p Capabilities from basic roles.
function p2p_uninstall() {
	$check_order = array("subscriber", "contributor", "author", "editor", "administrator");
	foreach ($check_order as $role) {
			$the_role = get_role($role);
			if ( empty($the_role) )
			continue;
			$the_role->remove_cap(p2pConverter) ;
	}
}

//Updates Database if valid info is passed
function update_convert() {
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && (current_user_can('p2pConverter'))) {
		$ready = false;
		if(@$_GET['post']) :
			if(@$_GET['ptype']) :
				$p_id = attribute_escape(@$_GET['post']);
				global $wpdb, $wp_rewrite;
				$table = $wpdb->prefix. "posts";
				$ptype = attribute_escape(@$_GET['ptype']);
				$pupdate = "UPDATE " . $table . " SET post_type = '" . $ptype . "' WHERE ID='" . $p_id . "'";
				$wpdb->query($pupdate);
				
				//Important! Rewrites permalinks for post/page files 
				$wp_rewrite->flush_rules();
			endif;
		endif;
	}
}

//Add Convert option while editing posts
add_action('submitpost_box', 'add_post_side_option');
function add_post_side_option(){
	if ( function_exists('current_user_can') && current_user_can('p2pConverter')) {
		global $post;
		$post_id = $post->ID;
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(get_the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this Post, ' . $title . ', into a static Page?';
		$con_div = '<a class="button button-highlighted" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="page.php?action=edit&post=' . $post_id . '&amp;ptype=page"; }\'>'.__("Convert to Page!").'</a>';
		echo $con_div;
	}
}

//Add Convert option while editing pages
add_action('submitpage_box', 'add_page_side_option');
function add_page_side_option(){
	if ( function_exists('current_user_can') && current_user_can('p2pConverter')) {
		global $post;
		$post_id = $post->ID;
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(get_the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this static Page, ' . $title . ', into a Post?';
		$con_div = '<a class="button button-highlighted" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="post.php?action=edit&post=' . $post_id . '&amp;ptype=post"; }\'>'.__("Convert to Post!").'</a>';
		echo $con_div;
	}
}

//Adds Column in Manage Posts
add_filter('manage_posts_columns', 'add_convert_column_post'); 
function add_convert_column_post($defaults) {
	$defaults ['convert_post']  = '<div style="text-align: center; width:100px;">' . __('Convert to Page') . '</div>';
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && !current_user_can('p2pConverter')) {
		unset($defaults['convert_post']);
	}
	return $defaults;
}

//Populates Convert option in Manage Posts
add_action('manage_posts_custom_column', 'pop_convert_column_post', 10, 2);
function pop_convert_column_post($column_name, $post_id){
	if( $column_name == 'convert_post' ) {
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this Post, ' . $title . ', into a static Page?';
		$pos = strpos($_SERVER["REQUEST_URI"], "?");
		if ($pos) {
			$char = "&amp;";
		} else {
			$char = "?";
		}
		$con_div = '<a class="edit" style="text-align: center;" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="' . $_SERVER["REQUEST_URI"] . $char . 'post=' . $post_id . '&amp;ptype=page"; }\'>'.__("Convert to Page!").'</a>';
		echo $con_div;
	}
}

//Adds Column in Manage Pages (thanks Scompt!)
add_filter('manage_pages_columns', 'add_convert_column_page'); 
function add_convert_column_page($defaults) {
	$defaults ['convert_page']  = '<div style="text-align: center; width:100px;">' . __('Convert to Post') . '</div>';
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && (!current_user_can('p2pConverter'))) {
		unset($defaults['convert_page']);
	}
	return $defaults;
}
	
//Populates Convert option in Manage Pages (thanks Scompt!)
add_action('manage_pages_custom_column', 'pop_convert_column_page', 10, 2);
function pop_convert_column_page($column_name, $post_id){
	if( $column_name == 'convert_page' ) {
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this static Page, ' . $title . ', into a Post?';
		$pos = strpos($_SERVER["REQUEST_URI"], "?");
		if ($pos) {
			$char = "&amp;";
		} else {
			$char = "?";
		}
		$con_div = '<a class="edit" style="text-align: center;" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="' . $_SERVER["REQUEST_URI"] . $char .'post=' . $post_id . '&amp;ptype=post"; }\'>'.__("Convert to Post!").'</a>';
		echo $con_div;
	}
}



?>