<?php 
/*
Plugin Name: p2pConverter
Plugin URI: http://www.briandgoad.com/blog/p2pConverter
Version: 0.2
Author: <a href="http://www.briandgoad.com/blog">Brian D. Goad</a>
Description: This plugin allows you to easily convert a post to a page and vice versa through an easy to use interface.
*/
require_once('managepages.php');
add_action('init', 'update_convert');
function update_convert() {
$ready = false;
if(@$_GET['convertid']) :
	if(@$_GET['ptype']) :
		$p_id = @$_GET['convertid'];
		global $wpdb, $wp_rewrite;
		$table = $wpdb->prefix. "posts";
		$ptype = @$_GET['ptype'];
		$pupdate = "UPDATE " . $table . " SET post_type = '" . $ptype . "' WHERE ID='" . $p_id . "'";
		$wpdb->query($pupdate);
		$wp_rewrite->flush_rules();
	endif;
endif;
}

add_filter('manage_posts_columns', 'add_convert_column_post'); 
function add_convert_column_post($defaults) {
$defaults ['convert_post']  = __('Convert to Page');
return $defaults;
}

add_filter('manage_pages_columns', 'add_convert_column_page'); 
function add_convert_column_page($defaults) {
$defaults ['convert_page']  = __('Convert to Post');
return $defaults;
}

add_action('manage_posts_custom_column', 'pop_convert_column_post', 7, 2);
function pop_convert_column_post($column_name, $post_id){
if( $column_name == 'convert_post' ) {
echo  '<a href="?convertid=' . $post_id . '&amp;ptype=page">Convert!';
}
}

add_action('manage_pages_custom_column', 'pop_convert_column_page', 7, 2);
function pop_convert_column_page($column_name, $post_id){
if( $column_name == 'convert_page' ) {
echo  '<a href="?convertid=' . $post_id . '&amp;ptype=post">Convert!';
}
}

?>