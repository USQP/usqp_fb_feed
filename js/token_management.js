/*********************************************************************************************
 * Summary for the token_management.js file
 * Sommaire pour le fichier token_management.js
 *********************************************************************************************
 *
 * 1. Load Facebook SDK - Loads the Facebook SDK and initializes the SDK functions.
 *                                      / Charge le SDK Facebook et initialise les fonctions du SDK.
 * 2. Connect to Facebook - Handles the login process and retrieves the access token.
 *                                      / Gère le processus de connexion et récupère le token d'accès.
 * 3. Get Page ID - Retrieves the page ID using the access token after successful login.
 *                                      / Récupère l'ID de la page en utilisant le token d'accès après une connexion réussie.
 * 4. Save Token - Saves the access token and page ID to the database via Ajax.
 *                                      / Sauvegarde le token d'accès et l'ID de la page dans la base de données via Ajax.
 * 5. Renew Token - Renews the Facebook access token via Ajax.
 *                                      / Renouvelle le token d'accès Facebook via Ajax.
 * 6. Disconnect from Facebook - Disconnects the user from Facebook and updates the page.
 *                                      / Déconnecte l'utilisateur de Facebook et met à jour la page.
 * 7. Load Facebook Information - Loads Facebook information from the database via Ajax.
 *                                      / Charge les informations Facebook depuis la base de données via Ajax.
 *
 *********************************************************************************************/

// 1. Load Facebook SDK
// Loads the Facebook SDK and initializes the SDK functions.
// / Charge le SDK Facebook et initialise les fonctions du SDK.
document.addEventListener('DOMContentLoaded', function() {
    load_facebook_sdk();

    // Load the Facebook SDK and initialize functions
    function load_facebook_sdk() {
        if (typeof FB === 'undefined') {
            var script = document.createElement('script');
            script.src = "https://connect.facebook.net/en_US/sdk.js";
            script.async = true;
            script.onload = function() {
                FB.init({
                    appId      : facebookData.app_id, // App ID passed from PHP
                    cookie     : true,
                    xfbml      : true,
                    version    : 'v22.0'
                });
                FB.AppEvents.logPageView();
                console.log('Facebook SDK loaded');
            };
            document.head.appendChild(script);
        } else {
            console.log('Facebook SDK already loaded');
        }
    }

// 2. Connect to Facebook
// Handles the login process and retrieves the access token.
// / Gère le processus de connexion et récupère le token d'accès.
    window.connect_to_facebook = function() {
        document.getElementById('facebook-info').innerHTML = '';
        FB.login(function(response) {
            if (response.authResponse) {
                get_page_id(response.authResponse.accessToken);
            }
        }, {scope: 'public_profile,email,pages_show_list,pages_read_engagement,pages_read_user_content'});
    };

// 3. Get Page ID
// Retrieves the page ID using the access token after successful login.
// / Récupère l'ID de la page en utilisant le token d'accès après une connexion réussie.
    function get_page_id(accessToken) {
        FB.api('/me/accounts', { access_token: accessToken }, function(response) {
            if (response && !response.error && response.data.length > 0) {
                var pageInfo = response.data[0];
                save_token(pageInfo.access_token, pageInfo.id);
            }
        });
    }

// 4. Save Token
// Saves the access token and page ID to the database via Ajax.
// / Sauvegarde le token d'accès et l'ID de la page dans la base de données via Ajax.
    function save_token(accessToken, pageId) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', facebookData.ajax_url, true); // AJAX URL passed from PHP
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                }
            }
        };
        xhr.send('action=save_token&access_token=' + accessToken + '&page_id=' + pageId);
    }

// 5. Renew Token
// Renews the Facebook access token via Ajax.
// / Renouvelle le token d'accès Facebook via Ajax.
    window.renew_token = function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', facebookData.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                }
            }
        };
        xhr.send('action=renew_token');
    };

// 6. Disconnect from Facebook
// Disconnects the user from Facebook and updates the page.
// / Déconnecte l'utilisateur de Facebook et met à jour la page.
    window.disconnect_facebook = function() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', facebookData.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                }
            }
        };
        xhr.send('action=disconnect_facebook');
    };

// 7. Load Facebook Information
// Loads Facebook information from the database via Ajax.
// / Charge les informations Facebook depuis la base de données via Ajax.
    function load_facebook_information() {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', facebookData.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                }
            }
        };
    }
});
