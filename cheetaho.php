<?php
/**
 * Plugin Name: CheetahO Image Optimizer
 * Plugin URI: https://cheetaho.com/
 * Description: CheetahO optimizes images automatically. Check your <a href="options-general.php?page=cheetaho-image-optimizer" target="_blank">Settings &gt; CheetahO</a> page on how to start optimizing your image library and make your website load faster. Do not forget to update these settings after plugin update.
 * Version: 1.5.1
 * Author: CheetahO
 * Author URI: https://cheetaho.com
 * Text Domain: cheetaho-image-optimizer
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'CHEETAHO_VERSION', '1.5.1' );
define( 'CHEETAHO_DB_VERSION', '1.1' );

define( 'CHEETAHO_ASSETS_IMG_URL', realpath( plugin_dir_url( __FILE__ ) . 'img/' ) . '/img' );
define( 'CHEETAHO_APP_URL', 'https://app.cheetaho.com/' );
define( 'CHEETAHO_SETTINGS_LINK', admin_url( 'options-general.php?page=cheetaho-image-optimizer' ) );
define( 'CHEETAHO_PLUGIN_FILE', __FILE__ );
define( 'CHEETAHO_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'CHEETAHO_PLUGIN_ADMIN_ASSETS', plugins_url( '/admin/assets/', __FILE__ ) );



$uploads = wp_upload_dir();
define( 'CHEETAHO_UPLOADS_BASE', $uploads['basedir'] );

$backup_base = is_main_site() ? CHEETAHO_UPLOADS_BASE : dirname( dirname( CHEETAHO_UPLOADS_BASE ) );
$site_id     = get_current_blog_id();

define( 'CHEETAHO_BACKUP', 'CheetahoBackups' );
define( 'CHEETAHO_BACKUP_FOLDER', $backup_base . '/' . CHEETAHO_BACKUP . '/' . $site_id );
define( 'CHEETAHO_PLUGIN_NAME', 'cheetaho-image-optimizer' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-cheetaho.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_cheetaho() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cheetaho-activator.php';

	CheetahO_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_cheetaho() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-cheetaho-deactivator.php';

	CheetahO_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_cheetaho' );
register_deactivation_hook( __FILE__, 'deactivate_cheetaho' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function run_cheetaho() {
	$plugin = new CheetahO();
	$plugin->run();
}

run_cheetaho();
