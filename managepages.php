<?php
/**
 * Main file for Manage Pages Custom Columns.  Includes everything needed to
 * enable the 'manage_pages_custom_column' action and the 'manage_pages_column' 
 * filter.  Upon inclusion, all actions/filters will have already been hooked.
 *
 * Usage:
 *    require_once('managepages.php');
 *
 * That's it!
 *
 * LICENSE
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Manage Pages Custom Columns
 * @author     Edward Dale <scompt@scompt.com>
 * @copyright  Copyright 2007 Edward Dale
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt LGPL 3.0
 * @version    $Id: managepages.php 22724 2007-10-21 10:22:55Z scompt $
 * @link       http://www.scompt.com/projects/manage-pages-custom-columns-in-wordpress
 * @since      1.0
 */

if( !class_exists('Services_JSON') )
    require_once('JSON.php');

// If the class is already defined, then we assume that everything is already
// handled and basically do nothing.
if( !class_exists('Scompt_Manage_Pages') ) {

    /**
     * This class enables the 'manage_pages_custom_column' action and the
     * 'manage_pages_column' filter to provide custom columns on the Manage Pages
     * subpanel of the administration screen.  Usage is analageous to that of
     * the 'manage_posts_custom_column' action and the 'manage_pages_column'
     * filter, which can be read about at 
     * http://scompt.com/archives/2007/10/20/adding-custom-columns-to-the-wordpress-manage-posts-screen
     */
    class Scompt_Manage_Pages {
    
        /**
         * Initializes things and hooks onto the load-edit-pages.php action.
         */
        function Scompt_Manage_Pages() {
            add_action( 'load-edit-pages.php', array(&$this, 'load_edit_pages'));

            $this->changes = array('deletions' => array(), 
                                   'additions' => array(),
                                        'data' => array());
        }
    
        /**
         * Figures out all the content that should be added after the page is loaded.
         *
         * This function is called by the load-edit-pages.php action hook.  It applies
         * the manage_pages_columns filter to get the column changes the user wants to 
         * make.  If there are any changes, then the changes are saved to be replayed 
         * by Javascript later.  The data for the columns is then generated and saved
         * also.
         *
         * If anything needs to be done, then the admin_head action is hooked onto.
         */
        function load_edit_pages() {
            // Defaults copied from edit-pages.php line 34
            $default_columns = array('id' => __('ID'),
                                  'title' => __('Title'),
                                  'owner' => __('Owner'),
                                'updated' => __('Updated'));

            // Let the user change the list
            $columns = apply_filters('manage_pages_columns', $default_columns);
    
            // Check if they made any changes at all first.  If not, then we're done.
            if( $default_columns !== $columns ) {
                if( $diff = array_diff($default_columns, $columns) ) {
                    // Grab the deleted columns and save the reverse-sorted indices for later
                    $deletions = array();
                    foreach( $diff as $diff_key=>$diff_value ) {
                        $deletions []= array_search($diff_key, array_keys($default_columns));
                    }
                    rsort($deletions);
                    $this->changes['deletions'] = $deletions;
                }
                if( $diff = array_diff($columns, $default_columns) ) {
                    // Any column additions can just be saved as is for later
                    $this->changes['additions'] = $diff;
                }

                // Set things up for the header display
                add_action('admin_footer', array(&$this, 'output'));
            	wp_enqueue_script('jquery');

                // When the wp action is run, we'll have access to all the pages 
                // which are being displayed, so we can go through them and 
                // grab our extra data
                add_action('wp', array(&$this,'wp'));
            }
        }

        /**
         * Capture the data for the columns.
         *
         * Called by the wp action.
         */
        function wp($the_wp) {
            global $posts, $wp_version;

            if( strpos($wp_version, '2.3') === 0 ) {
                // From WordPress Version 2.3 edit-pages.php line ~20
                $h2_search = isset($_GET['s']) && $_GET['s'] ? ' ' . sprintf(__('matching &#8220;%s&#8221;'), wp_specialchars( stripslashes( $_GET['s'] ) ) ) : '';
            	$post_status_q = '&post_status=' . $_GET['post_status'];
                $all = !( $h2_search || $post_status_q );
            } else {
                // From WordPress version 2.2 edit-pages.php line 24
                if ( $_GET['s'] )
                	$all = false;
                else
                	$all = true;
            }
            
            if ($posts) {
                $this->page_rows(0, 0, $posts, $all);
            }
        }

        /**
         * Captures data for the columns for an individual row.
         *
         * Copied and modified from edit-pages.php.
         */
        function page_rows( $parent = 0, $level = 0, $pages = 0, $hierarchy = true ) {
        	global $wpdb, $class, $post;

        	if (!$pages )
        		$pages = get_pages( 'sort_column=menu_order' );

        	if (! $pages )
        		return false;

        	foreach ( $pages as $post) {
        		setup_postdata( $post);
        		if ( $hierarchy && ($post->post_parent != $parent) )
        			continue;

        		$post->post_title = wp_specialchars( $post->post_title );
        		$id = (int) $post->ID;
 
                foreach( $this->changes['additions'] as $column_name=>$column_display_name) {
                    // For each addition, do the 'manage_pages_custom_column' action
                    // capturing the results and storing them for the Javascript later
                    ob_start();
                    do_action('manage_pages_custom_column', $column_name, $id);
                    $output = ob_get_clean();
                    $this->changes['data'][$id][$column_name] = $output;
                }

        		if ( $hierarchy ) $this->page_rows( $id, $level + 1, $pages );
        	}
        }

        /**
         * Outputs everything to the pages and executes it on page load.
         *
         * Goes through all the data stored in the $changes variable, converts
         * it to JSON and writes it on the page.  On page load, jQuery runs through
         * all the data adding and deleting columns and data.
         *
         * Called by the admin_footer action.
         */
        function output() {
            // Convert everything to JSON
            $json = new Services_JSON();
            $deletions_json = $json->encode($this->changes['deletions']);
            $additions_json = $json->encode(array_reverse($this->changes['additions']));
            $data_json = $json->encode($this->changes['data']);
            // magic number for where a cell should be inserted to account for action buttons
            $position = 5-count($this->changes['deletions']);

            ?>
            <script type="text/javascript">
            //<![CDATA[
                addLoadEvent( function() {
                    <?php
                    echo "var additions=$additions_json;\n";
                    echo "var deletions=$deletions_json;\n";
                    echo "var data=$data_json;\n";            
                    echo "var position=$position;\n";            
                    ?>

                    deletions.forEach(function(x,i,a){
                        x++;
                        jQuery('thead/tr/*:nth-child('+x+')').remove();
                        jQuery('tbody/tr/*:nth-child('+x+')').remove();
                    });
            
                    for( a in additions ) {
                        jQuery('<th>'+additions[a]+'</th>').insertBefore('thead/tr/th:last');
                    }
            
            
                    for( d in data ) {
                        for( e in data[d] ) {
                            jQuery('<td>'+data[d][e]+'</td>').insertBefore('#page-'+d+'/td:nth-child('+position+')');
                        }
                    }
                });
            //]]>
            </script>
            <?php
        }
    }

    // Get things going when the edit-pages.php file is loaded
    new Scompt_Manage_Pages();
}
?>