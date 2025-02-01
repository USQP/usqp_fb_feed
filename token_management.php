<?php
/*********************************************************************************************
 * Summary for the token_management.php file 
 * Sommaire pour le fichier token_management.php
 *********************************************************************************************
 *
 * 1. add_facebook_sdk() - Function to add the Facebook SDK to the page and handle the login. 
 *                                      / Fonction pour ajouter le SDK Facebook sur la page et gérer la connexion.
 * 2. save_token() - Function to save the access token and the associated page in the database.
 *                                      / Fonction pour enregistrer le jeton d'accès et la page associée dans la base de données.
 * 3. renew_token_action() - Function to manually renew the access token.
 *                                      / Fonction pour renouveler manuellement le jeton d'accès.
 * 4. auto_renew_token_cron_handler() - Function to automatically renew the token at the expiration date.
 *                                      / Fonction pour renouveler automatiquement le jeton avant la date d'expiration.
 * 5. disconnect_facebook_action() - Function to delete Facebook information and log out the user.
 *                                      / Fonction pour supprimer les informations Facebook et déconnecter l'utilisateur.
 * 6. usqp_fb_feed_token_page() - Function to display the token management page in the WordPress admin.
 *                                      / Fonction pour afficher la page de gestion du jeton dans l'admin WordPress.
 * 7. display_facebook_info() - Function to display the access token and the associated page information.
 *                                      / Fonction pour afficher les informations du jeton d'accès et la page associée.
 *
 *********************************************************************************************/


/*****************************************************************************************************************************************************/
// 1. Function to add the Facebook SDK to the page
// This function inserts the necessary scripts to integrate the Facebook SDK and initializes the methods 
// for login and token management. It also includes buttons to connect, renew, and disconnect a user from Facebook.
/** 
 * Fonction pour ajouter le SDK Facebook sur la page
 * Cette fonction insère les scripts nécessaires pour intégrer le SDK Facebook et initialise les méthodes 
 * de connexion et de gestion de jeton. Elle inclut également des boutons pour connecter, renouveler 
 * et déconnecter un utilisateur de Facebook.
 */
function add_facebook_sdk() {
    if (!wp_script_is('facebook-jssdk', 'enqueued')) {
        ?>
        <script async defer src="https://connect.facebook.net/en_US/sdk.js"></script>
        <script>
          window.fbAsyncInit = function() {
            FB.init({
              appId      : "<?php echo getenv('FB_APP_ID'); ?>", // Inject App ID from PHP
              cookie     : true,
              xfbml      : true,
              version    : 'v21.0'
            });
            FB.AppEvents.logPageView();   
          };

          function connect_facebook() {
            document.getElementById('facebook-info').innerHTML = '';
            FB.login(function(response) {
              if (response.authResponse) {
                retrieve_page_id(response.authResponse.accessToken);
              }
            }, {scope: 'public_profile,email,pages_show_list,pages_read_engagement,pages_read_user_content'}); 
          }

          function retrieve_page_id(accessToken) {
            FB.api('/me/accounts', { access_token: accessToken }, function(response) {
              if (response && !response.error && response.data.length > 0) {
                var pageInfo = response.data[0];
                save_token(pageInfo.access_token, pageInfo.id);
              }
            });
          }

          function save_token(accessToken, pageId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
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

        window.onload = function() {
            load_facebook_info();
        }

        function load_facebook_info() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                    }
                }
            };
            xhr.send('action=load_facebook_info');
        }

        function renew_token() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
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
        }

        function disconnect_facebook() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
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
        }
        </script>
        <?php
    }
}

/*****************************************************************************************************************************************************/
// 2. Function to save the access token and the associated page in the database
// This function retrieves the user's access_token and page_id from Facebook, 
// checks if the token is valid, and saves it in the database.
// If necessary, it converts the token into a long-term token and updates the database.
/**
 * Fonction pour enregistrer le jeton d'accès et la page associée dans la base de données
 * Cette fonction récupère l'access_token de l'utilisateur et le page_id depuis Facebook, 
 * vérifie si le jeton est valide et l'enregistre dans la base de données.
 * Si nécessaire, elle convertit le jeton en un jeton à long terme et met à jour la base de données.
 */
function save_token() {
    if (isset($_POST['access_token']) && isset($_POST['page_id'])) {
        global $wpdb;
        $access_token = sanitize_text_field($_POST['access_token']);
        $page_id = sanitize_text_field($_POST['page_id']);

        // Check if the token is a long-lived token or convert it if necessary
        // Fetch environment variables
        $app_id = getenv('FB_APP_ID'); 
        $app_secret = getenv('FB_APP_SECRET');
        $debug_url = "https://graph.facebook.com/v21.0/debug_token?input_token={$access_token}&access_token={$app_id}|{$app_secret}";
        
        // Call the API to check if the token is already a long-lived token
        $response = wp_remote_get($debug_url);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Error checking the token.']);
            wp_die();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // If the token is valid and long, we can proceed
        if (isset($data['data']['expires_at'])) {
            // If it's already a long token, no need to renew it
            $long_access_token = $access_token; // The token is already long-lived
            $expiration_time = date('Y-m-d H:i:s', $data['data']['expires_at']); // Expiration date
            // Calculate the next renewal date 30 days before the actual expiration date
            $next_renewal_time = date('Y-m-d H:i:s', strtotime($expiration_time) - (60 * 60 * 24 * 30)); // Renew 30 days before expiration
        } else {
            // Otherwise, convert the token to a long-lived token
            $long_token_url = "https://graph.facebook.com/v21.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token={$access_token}";

            $response = wp_remote_get($long_token_url);
            if (is_wp_error($response)) {
                wp_send_json_error(['message' => 'Error converting the token to a long-lived token.']);
                wp_die();
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['access_token'])) {
                // If the long-lived token was obtained, use it
                $long_access_token = $data['access_token'];
                // The token expires in 60 days (e.g., for a Facebook long-lived token)
                $expiration_time = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 60)); // 60 days from now
                // Calculate the next renewal date 30 days before the actual expiration date
                $next_renewal_time = date('Y-m-d H:i:s', strtotime($expiration_time) - (60 * 60 * 24 * 30)); // Renew 30 days before expiration
            } else {
                wp_send_json_error(['message' => 'The token could not be converted to a long-lived token.']);
                wp_die();
            }
        }

        // Save or update in the database
        $table_name = $wpdb->prefix . 'usqp_facebook_feed';
        $existing_entry = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE page_id = %s", $page_id)
        );

        if ($existing_entry) {
            // If the entry exists, update the values
            $wpdb->update(
                $table_name,
                array(
                    'page_access_token' => $long_access_token,
                    'expiration_time' => $expiration_time,
                    'next_renewal_time' => $next_renewal_time,
                ),
                array('page_id' => $page_id),
                array('%s', '%s', '%s'),
                array('%s')
            );
        } else {
            // Otherwise, insert a new entry into the database
            $wpdb->insert(
                $table_name,
                array(
                    'page_id' => $page_id,
                    'page_access_token' => $long_access_token,
                    'expiration_time' => $expiration_time,
                    'next_renewal_time' => $next_renewal_time,
                ),
                array('%s', '%s', '%s', '%s')
            );
        }

        // Call the function to update the Facebook cache asynchronously
        wp_remote_post(admin_url('admin-ajax.php'), array(
            'body'      => array('action' => 'fetch_and_update_facebook_cache'),
            'timeout'   => 0, // No timeout
            'blocking'  => false, //   Asynchronous
        ));

        // Schedule the Cron job to update the cache (default: hourly)
        if ( ! wp_next_scheduled( 'usqp_facebook_feed_cache_update_cron' ) ) {
            wp_schedule_event( time(), 'hourly', 'usqp_facebook_feed_cache_update_cron' );
        }
        
        // Immediately call the token renewal function to simulate manual renewal
        renew_token_action(); // This will renew the token after saving

        // Display updated Facebook information
        ob_start();
        display_facebook_info();
        $updated_html = ob_get_clean();

        wp_send_json_success([
            'updated_html' => $updated_html
        ]);
    }
    wp_die();
}
add_action('wp_ajax_save_token', 'save_token');

/*****************************************************************************************************************************************************/
// 3. Function to manually renew the access token
// This function allows you to manually renew an access token by using Facebook's short-term to long-term 
// token conversion method.
/**
 * Fonction pour renouveler manuellement le jeton d'accès
 * Cette fonction permet de renouveler un jeton d'accès manuellement en utilisant la méthode de conversion 
 * d'un jeton court terme en un jeton long terme de Facebook.
 */
function renew_token_action() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    if ($row) {
        $page_id = $row->page_id;
        $access_token = $row->page_access_token;

        // Repeat the steps to renew the token by transforming it into a long-lived one
        // Fetch environment variables
        $app_id = getenv('FB_APP_ID');
        $app_secret = getenv('FB_APP_SECRET'); 
        $long_token_url = "https://graph.facebook.com/v21.0/oauth/access_token?grant_type=fb_exchange_token&client_id={$app_id}&client_secret={$app_secret}&fb_exchange_token={$access_token}";

        $response = wp_remote_get($long_token_url);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Error while converting the token.']);
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (isset($data['access_token'])) {
                $new_access_token = $data['access_token'];
                $new_expiration_time = date('Y-m-d H:i:s', time() + (60 * 60 * 24 * 60)); // 60 days

                $next_renewal_time = date('Y-m-d H:i:s', strtotime($new_expiration_time) - (60 * 60 * 24 * 30)); // Renew 30 days before expiration

                // Update the database with the new token and expiration time
                $wpdb->update(
                    $table_name,
                    array(
                        'page_access_token' => $new_access_token,
                        'expiration_time' => $new_expiration_time,
                        'next_renewal_time' => $next_renewal_time,
                    ),
                    array('page_id' => $page_id),
                    array('%s', '%s', '%s'),
                    array('%s')
                );

                // Schedule the cron job for automatic token renewal
                $next_renewal_timestamp = strtotime($next_renewal_time);
                wp_schedule_single_event($next_renewal_timestamp, 'usqp_facebook_feed_token_update_cron');

                // Display updated Facebook information
                ob_start();
                display_facebook_info();
                $updated_html = ob_get_clean();

                wp_send_json_success([
                    'updated_html' => $updated_html
                ]);
            } else {
                wp_send_json_error(['message' => 'The token could not be renewed.']);
            }
        }
    } else {
        wp_send_json_error(['message' => 'No information available.']);
    }
    wp_die();
}
add_action('wp_ajax_renew_token', 'renew_token_action');

/*****************************************************************************************************************************************************/
// 4. Function to automatically renew the token at the expiration date
// This function is executed via a cron task to automatically renew it
// if needed. It then updates the database with the new information.
/**
 * Fonction pour renouveler automatiquement le jeton avant la date d'expiration
 * Cette fonction est exécutée via une tâche cron afin de renouveler 
 * automatiquement en cas de besoin. Elle met ensuite à jour la base de données avec les nouvelles informations.
 */
function auto_renew_token_cron_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';

    $row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    if ($row) {
        $page_id = $row->page_id;
        $access_token = $row->page_access_token;
        $url = "https://graph.facebook.com/v21.0/{$page_id}?fields=access_token&access_token={$access_token}";
        $response = wp_remote_get($url);

        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (isset($data->access_token)) {
                $new_access_token = $data->access_token;
                $new_expiration_time = time() + (60 * 60 * 24 * 60); // 60 days
                $next_renewal_time = time() + (60 * 60 * 24 * 30); // 30 days before expiration

                // Update the token in the database
                $wpdb->update(
                    $table_name,
                    array(
                        'page_access_token' => $new_access_token,
                        'expiration_time' => date('Y-m-d H:i:s', $new_expiration_time),
                        'next_renewal_time' => date('Y-m-d H:i:s', $next_renewal_time),
                    ),
                    array('page_id' => $page_id),
                    array('%s', '%s', '%s'),
                    array('%s')
                );

                // Reschedule the cron for the next renewal with the new expiration time
                wp_schedule_single_event($next_renewal_time, 'usqp_facebook_feed_token_update_cron');
            }
        }
    }
}
add_action('usqp_facebook_feed_token_update_cron', 'auto_renew_token_cron_handler');

/*****************************************************************************************************************************************************/
// 5. Function to disconnect the user and delete Facebook-related information
// This function deletes the stored information from the database, 
// disables the associated cron tasks, and updates the user interface to indicate the disconnection.
/**
 * Fonction pour déconnecter l'utilisateur et supprimer les informations liées à Facebook
 * Cette fonction supprime les informations enregistrées dans la base de données, 
 * désactive les tâches cron associées et met à jour l'interface utilisateur pour indiquer que la déconnexion a été effectuée.
 */
function disconnect_facebook_action() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';

        // Define the cache directory path
        $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';
    
        //  Delete the cache files
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . "*"); // Get all files in the directory
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Delete the file
                }
            }
        }

    // Delete the data from the database
    $wpdb->query("DELETE FROM $table_name");

    // List of cron job hooks to remove
    $tasks_to_remove = [
        'usqp_facebook_feed_cache_update_cron', // Hook for Facebook cache update
        'usqp_facebook_feed_token_update_cron', // Token update cron
    ];

    // Remove all scheduled cron jobs for these specific hooks
    $crons = _get_cron_array();

    foreach ($crons as $timestamp => $cron) {
        foreach ($cron as $hook => $data) {
            if (in_array($hook, $tasks_to_remove)) {
                wp_unschedule_event($timestamp, $hook); // Unscheduled events for these hooks
            }
        }
    }

    // Prevent automatic rescheduling of cron jobs (if applicable)
    wp_clear_scheduled_hook('usqp_facebook_feed_update_cache_cron');
    wp_clear_scheduled_hook('usqp_facebook_feed_token_update_cron');

    wp_send_json_success([ 
        'updated_html' => '<p>You are now disconnected from Facebook and all information has been cleared.</p>'
    ]);
}

// Register the action for the AJAX process
add_action('wp_ajax_disconnect_facebook', 'disconnect_facebook_action');

/********************************************************************************************************************************************************** */
// 6. Function to display the token management page in the WordPress admin interface
// This function generates a user interface with buttons to connect Facebook, display the 
// current token information, manually renew the token, or log out.
/**
 * Fonction pour afficher la page de gestion du jeton dans l'interface d'administration de WordPress
 * Cette fonction génère une interface utilisateur avec des boutons pour connecter Facebook, afficher les 
 * informations du jeton actuel, renouveler manuellement le jeton ou se déconnecter.
 */
function usqp_fb_feed_token_page() {
    ?>
    <div class="wrap">
        <h1>Token Management</h1>
        <p>Please log in to Facebook to retrieve your page information.</p>

        <button id="connect-facebook" class="button-primary" onclick="connect_to_facebook()">Log in to Facebook</button>
        <div id="facebook-info">
            <?php display_facebook_info(); // Display Facebook info directly with PHP ?>
        </div>
    </div>
    
    <script>
        // Load the Facebook SDK and initialize functions
        function load_facebook_sdk() {
            if (typeof FB === 'undefined') {
                var script = document.createElement('script');
                script.src = "https://connect.facebook.net/en_US/sdk.js";
                script.async = true;
                script.onload = function() {
                    FB.init({
                        appId      : "<?php echo getenv('FB_APP_ID'); ?>", // Inject App ID from PHP
                        cookie     : true,
                        xfbml      : true,
                        version    : 'v21.0'
                    });
                    FB.AppEvents.logPageView();
                    console.log('Facebook SDK loaded');
                };
                document.head.appendChild(script);
            } else {
                console.log('Facebook SDK already loaded');
            }
        }

        load_facebook_sdk();

        // Function to connect to Facebook
        function connect_to_facebook() {
            document.getElementById('facebook-info').innerHTML = '';
            FB.login(function(response) {
                if (response.authResponse) {
                    get_page_id(response.authResponse.accessToken);
                }
            }, {scope: 'public_profile,email,pages_show_list,pages_read_engagement,pages_read_user_content'}); 
        }

        // Get the page_id after login
        function get_page_id(accessToken) {
            FB.api('/me/accounts', { access_token: accessToken }, function(response) {
                if (response && !response.error && response.data.length > 0) {
                    var pageInfo = response.data[0];
                    save_token(pageInfo.access_token, pageInfo.id);
                }
            });
        }

        // Save the access token in the database
        function save_token(accessToken, pageId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
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

        // Function to renew the access token
        function renew_token() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
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
        }

        // Function to disconnect from Facebook
        function disconnect_facebook() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
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
        }

        // Load Facebook information when the page loads
        window.onload = function() {
            load_facebook_information();
        };

        // Load Facebook information from the database
        function load_facebook_information() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('facebook-info').innerHTML = response.data.updated_html;
                    }
                }
            };
            xhr.send('action=load_facebook_information');
        }
    </script>
    <?php
}

// 7. Function to display the Facebook token-related information
// This function retrieves the token information (access, page ID, expiration, etc.) 
// and displays it to the user in the admin interface.
/**
 * Fonction pour afficher les informations liées au jeton Facebook
 * Cette fonction récupère les informations du jeton (accès, ID de la page, expiration, etc.) 
 * et les affiche à l'utilisateur dans l'interface d'administration.
 */
function display_facebook_info() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';

    // Retrieve the token information from the database
    $row = $wpdb->get_row("SELECT * FROM $table_name LIMIT 1");

    // Define the cache directory and file paths
    $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';
    $page_info_file = $cache_dir . 'page_info.json';
    $profile_picture_file = $cache_dir . 'profile_picture.jpg';

    // Check if the JSON file and profile picture exist in the cache
    if (file_exists($page_info_file) && file_exists($profile_picture_file)) {
        // Load the information from the cache
        $page_info = json_decode(file_get_contents($page_info_file), true);
        $page_name = $page_info['name'] ?? 'Unknown Page';
        $profile_picture_url = wp_upload_dir()['baseurl'] . '/usqp/facebook-feed/profile_picture.jpg';
    } else {
        // If the files do not exist, call the Graph API to retrieve the information
        // Graph API URL to retrieve page information (name and picture)
        $url = "https://graph.facebook.com/{$row->page_id}?fields=name,picture&access_token={$row->page_access_token}";

        // Make a request to the Facebook Graph API
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            // If the API call fails, set default values
            $page_name = 'No page information found';
            $profile_picture_url = ''; // Leave empty if no profile picture
        } else {
            $data = wp_remote_retrieve_body($response);
            $page_info = json_decode($data, true);

            // Check if the response contains the expected information
            if (isset($page_info['name']) && isset($page_info['picture']['data']['url'])) {
                // Save the information in the cache
                if (!file_exists($cache_dir)) {
                    mkdir($cache_dir, 0777, true); // Create the directory if it doesn't exist
                }

                // Save the page information in a JSON file
                file_put_contents($page_info_file, json_encode($page_info));

                // Save the profile picture in the cache
                $profile_picture_url = $page_info['picture']['data']['url'] ?? '';
                if ($profile_picture_url) {
                    // Download the profile picture and save it in the cache
                    $image_data = file_get_contents($profile_picture_url);
                    file_put_contents($profile_picture_file, $image_data);
                }

                // Update the variables with the retrieved data
                $page_name = $page_info['name'] ?? 'Unknown Page';
            } else {
                // If the Graph API call doesn't return valid data, set default values
                $page_name = 'No page information found';
                $profile_picture_url = ''; // Leave empty if no profile picture
            }
        }
    }

    // Display the information in the admin interface
    ?>
    <div style=margin-top:20px;>
        <?php if ($row): ?>
            <div class="postbox">
                <h2><span>Facebook Access Informations</span></h2>
                <p><img src="<?php echo esc_url($profile_picture_url); ?>" alt="Profile Picture" width="50" height="50"></p>
                <p><strong>Page Name:</strong> <?php echo esc_html($page_name); ?></p>
                <p><strong>Page ID:</strong> <?php echo esc_html($row->page_id); ?></p>
                <p><strong>Access Token:</strong> <?php echo esc_html($row->page_access_token); ?></p>
                <p><strong>Expiration Time:</strong> <?php echo esc_html($row->expiration_time); ?></p>
                <?php if ($row->next_renewal_time): ?>
                    <p><strong>Next Automatic Renewal:</strong> <?php echo esc_html($row->next_renewal_time); ?></p>
                <?php else: ?>
                    <p><strong>Next Automatic Renewal:</strong> Not defined.</p>
                <?php endif; ?>
                <button id="renew-token" class="button-primary" onclick="renew_token()">Renew access token manually</button>
                <button id="disconnect-facebook" class="button-primary" onclick="disconnect_facebook()">Log out of Facebook</button>
            </div>
        </div>
        <?php else: ?>
            <div class="error">
                <p>No information available. Please connect to Facebook.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
