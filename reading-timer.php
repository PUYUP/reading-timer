<?php
/*
Plugin Name: Reading Timer
description: After X minute show code to reader
Version: 1.0
Author: PUYUP
Author URI: http://puyup.com
License: Private
*/

global $jal_db_version;
$jal_db_version = '1.0';

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'READING_TIMER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( READING_TIMER__PLUGIN_DIR . 'inc/admin.php' );
require_once( READING_TIMER__PLUGIN_DIR . 'inc/post.php' );

/**
 * Add scripts
 */
function reading_timer_load_statics() {
    // Styleshseet
    wp_enqueue_style( 'reading-timer-css', plugins_url( '/static/css/reading_timer.css', __FILE__ ) );

    // Javascript
    wp_enqueue_script( 'moment-js', plugins_url( '/static/js/moment.min.js', __FILE__ ), array(), true );
    wp_enqueue_script( 'jquery-countdown', plugins_url( '/static/vendors/jquery-countdown/jquery.countdown.min.js', __FILE__ ), array( 'jquery' ), true );
    wp_enqueue_script( 'reading-timer-js', plugins_url( '/static/js/reading-timer.js', __FILE__ ), array( 'jquery' ), true );

    $post_timer = get_post_meta( get_the_ID(), 'reading_timer', true );
    $global_countdown = get_option( 'reading_timer_countdown' );
    $countdown = $post_timer ? $post_timer : $global_countdown;

    wp_localize_script(
		'reading-timer-js',
		'readingTimerVar',
		array(
			'post_id'  => get_the_ID(),
            'countdown' => $countdown,
            'ajax_url' => admin_url( 'admin-ajax.php' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'reading_timer_load_statics' );


function jal_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'reading_timer';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		`id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `post_id` int(11) NOT NULL,
    `ip_address` varchar(255) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `code` varchar(255) DEFAULT NULL,
    `timer` varchar(11) DEFAULT NULL,
    `used` tinyint(1) NOT NULL DEFAULT 0,
    `create_at` datetime NOT NULL DEFAULT current_timestamp()
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );
}

register_activation_hook( __FILE__, 'jal_install' );