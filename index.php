<?php
/*********************************************************************************************
 * Plugin Name: USQP Facebook Feed
 * Description: A custom plugin to display Facebook feeds / Un plugin personnalisé pour afficher les flux Facebook.
 * Version: 1.0
 * Author: Aurélien Béguin - Un site qui Peps
 * Author URI: https://unsitequipeps.fr
 * Contributors: Aurélien Béguin
 *********************************************************************************************/

/*********************************************************************************************
 * Summary for the index.php file
 * Sommaire pour le fichier index.php
 *********************************************************************************************

 * 1. Exit if accessed directly
 *          / Sortie si accès direct
 *    - Prevents direct access to the plugin file.
 *          / Empêche l'accès direct au fichier du plugin */

if ( !defined('ABSPATH') ) {
    exit;
}

/*********************************************************************************************
 * Function to load environment variables from .env file
 *          / Fonction pour charger les variables d'environnement depuis le fichier .env
 *********************************************************************************************/

 function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return false; 
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignore comments
        if (strpos($line, '#') === 0) {
            continue;
        }
        // Split the line into key and value
        list($name, $value) = explode('=', $line, 2);
        // Clean up spaces and add the variable to $_ENV
        $name = trim($name);
        $value = trim($value);
        // Set the variable in $_ENV and $GLOBALS if necessary
        $_ENV[$name] = $value;
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
    return true;
}


/*********************************************************************************************
 * Load environment variables from the .env file
 *          / Charger les variables d'environnement depuis le fichier .env
 *********************************************************************************************/

// Load the .env file
loadEnv(__DIR__ . '/.env');

/*********************************************************************************************
 * Define plugin constants
 *          / Définir les constantes du plugin
 *    - USQP_FB_FEED_DIR: Path to the plugin directory.
 *          / Chemin du répertoire du plugin.
 *    - USQP_FB_FEED_URL: URL to the plugin resources.
 *          / URL vers les ressources du plugin. */

define('USQP_FB_FEED_DIR', plugin_dir_path(__FILE__));
define('USQP_FB_FEED_URL', plugin_dir_url(__FILE__));

/*********************************************************************************************
 * Include required files
 *          / Inclure les fichiers nécessaires
 *    - menu.php: Handles the WordPress admin menu for the plugin.
 *          / Gère le menu d'administration WordPress pour le plugin.
 *    - token_management.php: Manages the Facebook access token.
 *          / Gère le jeton d'accès Facebook.
 *    - cache_management.php: Manages Facebook feed cache.
 *          / Gère le cache des flux Facebook.
 *    - frontend_integration.php: Handles the frontend display of the Facebook feed, 
 *          / Gère l'affichage frontend du flux Facebook */

require_once USQP_FB_FEED_DIR . 'menu.php';
require_once USQP_FB_FEED_DIR . 'token_management.php';
require_once USQP_FB_FEED_DIR . 'cache_management.php';
require_once USQP_FB_FEED_DIR . 'frontend_integration.php';

/*********************************************************************************************
 * Create custom table when plugin is activated
 *          / Créer une table personnalisée lors de l'activation du plugin
 *    - Function that creates a custom table for storing Facebook feed information.
 *          / Fonction qui crée une table personnalisée pour stocker les informations du flux Facebook. */

function create_usqp_facebook_feed_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the table
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        page_id VARCHAR(255) NOT NULL,
        page_access_token LONGTEXT NOT NULL,
        expiration_time DATETIME NOT NULL,
        next_renewal_time DATETIME NOT NULL,
        cache_frequency VARCHAR(50) DEFAULT 'hourly',
        last_cache_update DATETIME DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); 
}

/*********************************************************************************************
 * Register activation hook for the plugin
 *          / Enregistrer le hook d'activation pour le plugin
 *    - Registers the `create_usqp_facebook_feed_table` function to be run on plugin activation.
 *          / Enregistre la fonction `create_usqp_facebook_feed_table` pour l'activation du plugin. */

register_activation_hook(__FILE__, 'create_usqp_facebook_feed_table');

/*********************************************************************************************
 * Action Hook for menu setup and other functionalities
 *          / Hook d'action pour la configuration du menu et d'autres fonctionnalités
 *    - Adds the plugin menu to the WordPress admin interface.
 *          / Ajoute le menu du plugin dans l'interface d'administration de WordPress. */

add_action('admin_menu', 'usqp_fb_feed_admin_menu');

/*********************************************************************************************/


