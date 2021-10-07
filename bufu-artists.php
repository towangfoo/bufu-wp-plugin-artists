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
 * Version:           1.1.1
 * Text Domain:       bufu-artists
 * Domain Path:       /languages
 * Requires at least: 5.5
 * Requires PHP:      7.2
 * Author:            Steffen Muecke, quellkunst multimedia
 * Author URI:        https://quellkunst.de/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

require_once('bufu-artists/Bufu_Artists.php');

$bufuArtistsPluginInstance = new Bufu_Artists();
$bufuArtistsPluginInstance->initHooks();

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
}