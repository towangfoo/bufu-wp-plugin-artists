<?php
/**
 * Plugin for custom integration of Rapidmail Newsletter Singup.
 *
 * @author            Steffen Muecke <mail@quellkunst.de>
 * @copyright         2020 quellkunst multimedia
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       BuschFunk Rapidmail
 * Description:       Rapidmail API integration for Newsletter Signup.
 * Version:           1.0.0-rc2
 * Text Domain:       bufu-rapidmail
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

require_once('bufu-rapidmail/Bufu_Rapidmail.php');

$bufuRapidmailPluginInstance = new Bufu_Rapidmail();
$bufuRapidmailPluginInstance->initHooks();


// ---------------------------------------------------------------------------------------------------------------------
// ----- theme/public methods ------------------------------------------------------------------------------------------

/**
 * get the ThemeHelper class
 *
 * @return Bufu_Rapidmail_ThemeHelper
 */
function bufu_rapidmail()
{
	global $bufuRapidmailPluginInstance;
	return $bufuRapidmailPluginInstance->getThemeHelper();
}