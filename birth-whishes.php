<?php
/**
 * Plugin Name
 *
 * @package           Birthday Whishes
 * @author            Birth
 * @copyright         Birth
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Birthday Whishes
 * Plugin URI:        #
 * Description:       Birthday Whishes.
 * Version:           1.3
 * Requires at least: 5.0
 * Requires PHP:      5.0
 * Author:            Birth
 * Author URI:        #
 * Text Domain:       birth
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt

*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo __('Hi there!  I\'m just a plugin, not much I can do when called directly.', 'birth');
	exit;
}

/* Plugin Constants */
if (!defined('BIRTH_WHISHES_URL')) {
    define('BIRTH_WHISHES_URL', plugin_dir_url(__FILE__));
}

if (!defined('BIRTH_WHISHES_PLUGIN_PATH')) {
    define('BIRTH_WHISHES_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/* Api Constant */
define( 'BIRTH_EMPLOYEES_API', "https://interview-assessment-1.realmdigital.co.za/employees");
define( 'BIRTH_DO_NOT_SEND_BIRTHDAY_EMAIL_API', "https://interview-assessment-1.realmdigital.co.za/do-not-send-birthday-wishes");

require_once (BIRTH_WHISHES_PLUGIN_PATH . '/includes/settings.php');

register_activation_hook( __FILE__, array('Birth_Wishes_Settings','birth_activation_hook') );

register_activation_hook( __FILE__, array('Birth_Wishes_Settings','birth_deactivation_hook') );


/**
 * MAIN CLASS
 */
class Birth_Whishes 
{
	function __construct()
	{
		Birth_Wishes_Settings::settings_init();
	}

}

$post_view = new Birth_Whishes();