<?php 
/*
Plugin Name: p2pConverter
Plugin URI: http://www.briandgoad.com/blog/p2pConverter
Version: 0.5
Author: Brian D. Goad, aka bbbco
Description: This plugin allows you to easily convert a post to a page and vice versa through an easy to use interface. Just click on your Manage tab in Administration, and you will see a Convert option under 
		Posts and Pages sub-tabs. This only works for roles with the capability to edit posts (i.e. Administrators and Editors).
Author URI: http://www.briandgoad.com
*/
	
add_action('init', 'update_convert');
	
//Updates Database if valid info is passed
function update_convert() {
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && (current_user_can('delete_posts') || current_user_can('delete_pages')) ) {
		$ready = false;
		if(@$_GET['convertid']) :
			if(@$_GET['ptype']) :
				$p_id = attribute_escape(@$_GET['convertid']);
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



//Adds Column in Manage Posts
add_filter('manage_posts_columns', 'add_convert_column_post'); 
function add_convert_column_post($defaults) {
	$defaults ['convert_post']  = '<div style="text-align: center">' . __('Convert to Page') . '</div>';
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && !current_user_can('delete_posts')) {
		unset($defaults['convert_post']);
	}
	return $defaults;
}

//Populates Convert option in Manage Posts
add_action('manage_posts_custom_column', 'pop_convert_column_post', 7, 2);
function pop_convert_column_post($column_name, $post_id){
	if( $column_name == 'convert_post' ) {
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this Post, ' . $title . ', into a static Page?';
		$con_div = '<a class="edit" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="?convertid=' . $post_id . '&amp;ptype=page"; }\'>'.__("Convert!").'</a>';
		echo $con_div;
	}
}

//Adds Column in Manage Pages (thanks Scompt!)
add_filter('manage_pages_columns', 'add_convert_column_page'); 
function add_convert_column_page($defaults) {
	$defaults ['convert_page']  = '<div style="text-align: center">' . __('Convert to Post') . '</div>';
	// Checks if appropriate Role has Capability to Edit Post
	if ( function_exists('current_user_can') && (!current_user_can('delete_pages'))) {
		unset($defaults['convert_page']);
	}
	return $defaults;
}
	
//Populates Convert option in Manage Pages (thanks Scompt!)
add_action('manage_pages_custom_column', 'pop_convert_column_page', 7, 2);
function pop_convert_column_page($column_name, $post_id){
	if( $column_name == 'convert_page' ) {
		$title = preg_replace("/\r?\n/", "\\n", addslashes(strip_tags(the_title("", "", false)))); 
		$message = 'Are you sure you really want to convert this static Page, ' . $title . ', into a Post?';
		$con_div = '<a class="edit" href="javascript:void(null)" onClick=\'if (confirm("' . $message . '")) {window.location.href="?convertid=' . $post_id . '&amp;ptype=post"; }\'>'.__("Convert!").'</a>';
		echo $con_div;
	}
}



?>