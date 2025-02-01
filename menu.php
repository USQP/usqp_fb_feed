<?php
/*********************************************************************************************
 * Summary for the admin_menu.php file
 * Sommaire pour le fichier admin_menu.php
 *********************************************************************************************
 *
 * 1. usqp_fb_feed_admin_menu() - Function to add the main menu and submenus in the WordPress admin dashboard.
 *                                      / Fonction pour ajouter le menu principal et les sous-menus dans le tableau de bord WordPress.
 * 2. usqp_fb_feed_main_page() - Callback function to display the main page content for the USQP Facebook Feed settings.
 *                                      / Fonction de rappel pour afficher le contenu de la page principale des paramètres de USQP Facebook Feed.
 *
 *********************************************************************************************/

/*****************************************************************************************************************************************************/
// 1. usqp_fb_feed_admin_menu()
// This function adds the main menu and submenus under the WordPress admin panel for the Facebook feed plugin.
// / Cette fonction ajoute le menu principal et les sous-menus sous le panneau d'administration WordPress pour le plugin de flux Facebook.
function usqp_fb_feed_admin_menu() {
    // Add the main menu page for USQP Facebook Feed
    add_menu_page(
        'USQP Facebook Feed', // Page title
        'USQP Facebook Feed', // Menu title
        'manage_options',     // Capability required to access the menu
        'usqp_fb_feed',       // Menu slug (URL)
        'usqp_fb_feed_main_page', // Callback function to display the page content
        'dashicons-facebook', // Icon for the menu
        100                   // Position in the menu
    );

    // Add the submenu page for Token Management under the main menu
    add_submenu_page(
        'usqp_fb_feed',                     // Parent slug (the main menu)
        'Connexion et Gestion du Token',    // Page title
        'Gestion du Token',                 // Menu title
        'manage_options',                   // Capability required to access the menu
        'usqp_fb_feed_token_management',    // Menu slug (URL)
        'usqp_fb_feed_token_page'           // Callback function for the submenu page
    );

    // Add the submenu page for Cache Management under the main menu
    add_submenu_page(
        'usqp_fb_feed',                     // Parent slug (the main menu)
        'Gestion du Cache',                 // Page title
        'Gestion du Cache',                 // Menu title
        'manage_options',                   // Capability required to access the menu
        'usqp_fb_feed_cache_management',    // Menu slug (URL)
        'usqp_fb_feed_cache_page'           // Callback function for the submenu page
    );
}
add_action('admin_menu', 'usqp_fb_feed_admin_menu');

/*****************************************************************************************************************************************************/
// 2. usqp_fb_feed_main_page()
// Callback function for displaying the main settings page content for the plugin.
// / Fonction de rappel pour afficher le contenu de la page principale des paramètres du plugin.
function usqp_fb_feed_main_page() {
    echo '<div class="wrap">';
    echo '<h1>USQP Facebook Feed</h1>';
    echo '<p>Welcome to the main settings page of USQP Facebook Feed.</p>';
    echo '</div>';
}
