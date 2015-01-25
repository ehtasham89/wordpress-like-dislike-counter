<?php
/*
Plugin Name: iDev WordPress Like Dislike Counter
Plugin URI: https://github.com/alijange/idev-wordpress-like-dislike-counter/
Description: Like dislike counter for posts and comments
Author: Ehtasham Nasir
Version: 1.1.0
Author URI: http://www.idevstudio.com
License: GPLv2 or later
*/

// Runs when plugin is activated and creates new database field
register_activation_hook(__FILE__,'like_dislike_counter_install');
add_action('admin_init', 'ldc_plugin_redirect');
function ldc_plugin_activate() {
    add_option('ldc_plugin_do_activation_redirect', true);
}

function ldc_plugin_redirect() {
    if (get_option('ldc_plugin_do_activation_redirect', false)) {
        delete_option('ldc_plugin_do_activation_redirect');
        wp_redirect('plugins.php');
    }
}
function like_dislike_counter_install() 
{
	ldc_plugin_activate();
}


function ldc_get_version(){
	if ( ! function_exists( 'get_plugins' ) )
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	$plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
	$plugin_file = basename( ( __FILE__ ) );
	return $plugin_folder[$plugin_file]['Version'];
}

function ldc_lite_enqueue_css(){
	if(!is_admin()){
		wp_register_style($handle = 'like-dislike', plugins_url( 'like-dislike-counter-styles.css' , __FILE__ ), $deps = array(), $ver = '1.0.0', $media = 'all');
		wp_enqueue_style('like-dislike');
	}
}
add_action('wp_print_styles', 'ldc_lite_enqueue_css');


require_once('like.func.php');
require_once('dislike.func.php');
