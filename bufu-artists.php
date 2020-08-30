<?php
/**
 * Artists post type and admin interface for managing BuschFunk artists
 *
 * @author            Steffen Muecke
 * @copyright         2020 quellkunst multimedia
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BuschFunk Artists
 * Description:       Plugin to manage BuschFunk artist portfolio.
 * Version:           0.1.0
 * Text Domain:       bufu-artists
 * Domain Path:       /languages
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Steffen Muecke
 * Author URI:        https://quellkunst.de/
 * License:           GPL v2 or later
 */

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once('bufu-artists/Bufu_Artists.php');

$instance = new Bufu_Artists();

add_action('admin_init', [$instance, 'hook_admin_init']);
add_action('init', [$instance, 'hook_init']);
add_action( 'plugins_loaded', [$instance, 'hook_plugins_loaded']);
add_action('save_post', [$instance, 'hook_save_post']);