<?php
/**
 * Artists post type and admin interface for managing BuschFunk artists
 *
 * @author            Steffen Muecke <mail@quellkunst.de>
 * @copyright         2020 quellkunst multimedia
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BuschFunk Artists
 * Description:       Adds all artist-related features: profiles, relations to events, albums and more content.
 * Version:           0.1.0
 * Text Domain:       bufu-artists
 * Domain Path:       /languages
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Steffen Muecke, quellkunst multimedia
 * Author URI:        https://quellkunst.de/
 * License:           GPL v2 or later
 */

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once('bufu-artists/Bufu_Artists.php');

$bufuArtistsPluginInstance = new Bufu_Artists();

add_action('add_meta_boxes', [$bufuArtistsPluginInstance, 'hook_admin_add_meta_boxes']);
add_action('admin_init', [$bufuArtistsPluginInstance, 'hook_admin_init']);
add_action('init', [$bufuArtistsPluginInstance, 'hook_init']);
add_action('rest_api_init', [$bufuArtistsPluginInstance, 'hook_rest_api_init']);
add_action('plugins_loaded', [$bufuArtistsPluginInstance, 'hook_plugins_loaded']);
add_action('save_post', [$bufuArtistsPluginInstance, 'hook_save_post']);
add_action('the_post', [$bufuArtistsPluginInstance, 'hook_the_post']);
add_action( 'widgets_init', [$bufuArtistsPluginInstance, 'hook_widgets_init'] );

// hook into query creation using filters
add_filter( 'pre_get_posts', [$bufuArtistsPluginInstance, 'filter_pre_get_posts'] );

// hook into tribe_events_calendar on saving events (data migration)
// @TODO: remove later, when production is stable
add_action( 'tribe_events_event_save', [$bufuArtistsPluginInstance, 'hook_tribe_events_event_save'] );

// add filter for custom date formatting settings
add_filter( 'tribe_events_event_schedule_details_formatting', [$bufuArtistsPluginInstance, 'filter_tribe_events_event_schedule_details_formatting'] );

// add custom filter for artists to tribe filter bar
add_action( 'tribe_events_filters_create_filters', [$bufuArtistsPluginInstance, 'hook_tribe_filter_bar_create_filters'] );
add_filter( 'tribe_context_locations', [$bufuArtistsPluginInstance, 'hook_tribe_filter_bar_context_locations'] );
add_filter( 'tribe_events_filter_bar_context_to_filter_map', [$bufuArtistsPluginInstance, 'hook_tribe_filter_bar_map'] );

// add plugin assets
add_action( 'admin_enqueue_scripts', [$bufuArtistsPluginInstance, 'hook_admin_enqueue_scripts'] );

// ---------------------------------------------------------------------------------------------------------------------
// ----- theme/public methods ------------------------------------------------------------------------------------------

/**
 * get the ThemeHelper class
 *
 * @return Bufu_Artists_ThemeHelper
 */
function bufu_artists()
{
	global $bufuArtistsPluginInstance;
	return $bufuArtistsPluginInstance->getThemeHelper();
//	return $bufuArtistsPluginInstance->getAllArtists_selectOptions();
}