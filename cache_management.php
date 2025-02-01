<?php
/*********************************************************************************************
 * Summary for the cache_management.php file 
 * Sommaire pour le fichier cache_management.php
 *********************************************************************************************
 *
 * 1. fetch_and_update_facebook_cache() - Function to fetch and update the cache with videos, images, and other data from Facebook.
 *                                      / Fonction pour récupérer et mettre à jour le cache avec les vidéos, images, et autres données depuis Facebook.
 * 2. enqueue_cache_update_check_script() // check_facebook_cache_update() - This function enqueues a script to check the status of the Facebook cache update and display a notification if the cache is being updated.
 *                                     / Ces fonctions enfilent un script pour vérifier l'état de la mise à jour du cache Facebook et afficher une notification si le cache est en cours de mise à jour.
 * 3. usqp_fb_feed_cache_page() - Function to display the cache management page in the WordPress admin.
 *                                      / Fonction pour afficher la page de gestion du cache dans l'admin WordPress.
 * 4. display_facebook_cache_admin() - Displays a table of cached Facebook posts and reels
 *                                      / Affiche les publications et reels Facebook mis en cache dans un tableau.
 * 5. schedule_cache_update_task() - Function to schedule the cache update task based on the selected frequency.
 *                                      / Fonction pour programmer la tâche de mise à jour du cache en fonction de la fréquence sélectionnée.
 * 6. add_custom_cron_intervals() - Function to add custom cron intervals for tasks like updating the cache every minute.
 *                                      / Fonction pour ajouter des intervalles cron personnalisés pour des tâches comme la mise à jour du cache toutes les minutes.
 * 7. fetch_and_update_facebook_cache_cron_handler() - Function to handle the cron job for updating the cache automatically.
 *                                      / Fonction pour gérer la tâche cron de mise à jour automatique du cache.
 *
 * 8. delete_facebook_cache() - Function to delete the cached Facebook feed data and associated media files.
 *                                     / Fonction pour supprimer les données du flux Facebook mises en cache et les fichiers multimédias associés.
 *********************************************************************************************/

/*****************************************************************************************************************************************************/
// 1. fetch_and_update_facebook_cache()
// This function fetches the Facebook feed data (videos, posts, page information) using the Facebook API. 
// It downloads and saves the videos, images, and other related media to a cache directory. It also updates the cache status in the database.
// / Cette fonction récupère les données du flux Facebook (vidéos, publications, informations de la page) en utilisant l'API Facebook. 
// Elle télécharge et sauvegarde les vidéos, images et autres médias associés dans un répertoire de cache. Elle met également à jour le statut du cache dans la base de données.
function fetch_and_update_facebook_cache() {
    global $wpdb;

    // Set a transient to indicate that the cache update is in progress
    set_transient('facebook_cache_update_in_progress', true, 5 * MINUTE_IN_SECONDS); // Cache for 5 minutes

    // Fetch Facebook page information from the table
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");

    if (!$row) {
        // Remove the transient after the operation
        delete_transient('facebook_cache_update_in_progress');
        return "No Facebook feed information found in the database.";
    }

    // Fetch token and page information
    $access_token = $row->page_access_token;
    $page_id = $row->page_id;

    // Cache directory
    $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';
    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // Call Facebook API to fetch videos and posts (excluding stories)
    $video_response = @file_get_contents("https://graph.facebook.com/v21.0/{$page_id}/videos?access_token={$access_token}");
    $post_response = @file_get_contents("https://graph.facebook.com/v21.0/{$page_id}/posts?access_token={$access_token}");

    // Check if video and post responses are valid
    if ($video_response === FALSE || $post_response === FALSE) {
        // Remove the transient after the operation
        delete_transient('facebook_cache_update_in_progress');
        return "Error while fetching videos or posts.";
    }

    // Fetch page information (name and profile picture)
    $page_info_response = @file_get_contents("https://graph.facebook.com/v21.0/{$page_id}?fields=name,picture&access_token={$access_token}");
    $page_info = json_decode($page_info_response, true);

    // Check if page data is valid
    if (!$page_info || !isset($page_info['picture']['data']['url'])) {
        // Remove the transient after the operation
        delete_transient('facebook_cache_update_in_progress');
        return "Error while fetching Facebook page information.";
    }

    // Handle profile picture
    $profile_picture_url = $page_info['picture']['data']['url'];
    $profile_picture_filename = $cache_dir . 'profile_picture.jpg';

    // Check if profile picture has changed
    $current_picture_hash = file_exists($profile_picture_filename) ? md5_file($profile_picture_filename) : null;
    $new_picture_contents = file_get_contents($profile_picture_url);

    if ($current_picture_hash !== md5($new_picture_contents)) {
        // Download and save the new profile picture
        file_put_contents($profile_picture_filename, $new_picture_contents);
    }

    // Save JSON files for posts and page information
    file_put_contents($cache_dir . 'posts.json', $post_response);
    file_put_contents($cache_dir . 'page_info.json', json_encode($page_info));

    // Save only reels (filtered videos)
    $videos = json_decode($video_response, true);
    $reels = [];

    if (isset($videos['data']) && !empty($videos['data'])) {
        foreach ($videos['data'] as $video) {
            if (isset($video['type']) && $video['type'] === 'story') {
                continue; 
            }

            $video_id = $video['id'];
            $video_details_response = @file_get_contents("https://graph.facebook.com/v21.0/{$video_id}?fields=source,title,description,likes.summary(true),permalink_url,updated_time,picture&access_token={$access_token}");
            $video_details = json_decode($video_details_response, true);

            if (isset($video_details['source'])) {
                // Verify if the video is a reel (check permalink for "/reel/") - keep only reels
                $is_reel = strpos($video_details['permalink_url'], '/reel/') !== false;

                if ($is_reel) {
                    // If it's a reel, adjust the permalink to point to the correct URL
                    $video_details['permalink_url'] = "https://www.facebook.com/reel/{$video_id}";

                    // Save the reel video
                    $video_url = $video_details['source'];
                    $video_filename = $cache_dir . "video_{$video_id}.mp4";
                    file_put_contents($video_filename, file_get_contents($video_url));

                    // Save associated image (thumbnail)
                    if (isset($video_details['picture'])) {
                        $thumbnail_url = $video_details['picture'];
                        $thumbnail_filename = $cache_dir . "thumbnail_video_{$video_id}.jpg";
                        file_put_contents($thumbnail_filename, file_get_contents($thumbnail_url));
                    }

                    // Add the reel video to the list
                    $reels[] = $video_details;
                }
            }
        }
    }

    // Save only reels to JSON file
    if (!empty($reels)) {
        file_put_contents($cache_dir . 'reels.json', json_encode($reels));
    }

    // Save post images
    $posts = json_decode($post_response, true);
    if (isset($posts['data']) && !empty($posts['data'])) {
        foreach ($posts['data'] as $post) {
            if (isset($post['type']) && $post['type'] === 'story') {
                continue;
            }

            $post_id = $post['id'];
            $post_details_response = @file_get_contents("https://graph.facebook.com/v21.0/{$post_id}?fields=message,permalink_url,updated_time,attachments,likes.summary(true)&access_token={$access_token}");
            $post_details = json_decode($post_details_response, true);

            // Download attached image or media (if available)
            if (!empty($post_details['attachments']['data'])) {
                $attachment = $post_details['attachments']['data'][0];
                if (isset($attachment['media']['image']['src'])) {
                    // It's an attached image
                    $image_url = $attachment['media']['image']['src'];
                    $image_filename = $cache_dir . "image_post_{$post_id}.jpg";
                    file_put_contents($image_filename, file_get_contents($image_url));
                } elseif (isset($attachment['media']['source'])) {
                    // It's an attached video, download the video
                    $video_url = $attachment['media']['source'];
                    $video_filename = $cache_dir . "video_post_{$post_id}.mp4";
                    file_put_contents($video_filename, file_get_contents($video_url));
                }
            }
        }
    }

    // Update the last cache update date
    $wpdb->update(
        $table_name,
        array(
            'last_cache_update' => current_time('mysql')  // Updates with the current date
        ),
        array('id' => $row->id),  // Updates the specific row
        array('%s'),  // Format of the column to be updated
        array('%d')  // Format of the ID
    );

    // Remove the transient after the operation
    delete_transient('facebook_cache_update_in_progress');

    return "Information and media have been successfully updated.";
}

/*****************************************************************************************************************************************************/
/*****************************************************************************************************************************************************/
// 2. enqueue_cache_update_check_script() // check_cache_update_status() // inject_cache_status_to_frontend() 
// This functions enqueues a script to check the status of the Facebook cache update and display a notification if the cache is being updated.
//  / Ces fonctions enfilent un script pour vérifier l'état de la mise à jour du cache Facebook et afficher une notification si le cache est en cours de mise à jour.

    function enqueue_cache_update_check_script() {
        // Vérifier si nous sommes dans l'environnement WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            return; // Si nous sommes dans WP-CLI, on ne charge pas le script
        }

// Check if we are on the "usqp_fb_feed" menu page or its sub-pages
$current_screen = get_current_screen();
// Check if the current page is the "usqp_fb_feed" menu page or one of its sub-pages
if (isset($current_screen->id) && (
    // Main page of the menu
    strpos($current_screen->id, 'usqp_fb_feed') !== false ||
    // Sub-page for token management
    strpos($current_screen->id, 'usqp_fb_feed_token_management') !== false ||
    // Sub-page for cache management
    strpos($current_screen->id, 'usqp_fb_feed_cache_management') !== false
)) {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Function to check the cache update status via AJAX
            function checkCacheUpdateStatus() {
                $.ajax({
                    url: ajaxurl, // The AJAX handler URL
                    type: 'POST', // Send a POST request
                    data: { action: 'check_facebook_cache_update' }, // Action to call the AJAX handler
                    success: function(response) {
                        // If the cache update is in progress, show the notification
                        if (response === 'in_progress') {
                            showNotice();
                            $('input[name="update_facebook_cache"]').prop('disabled', true); // Disable the button
                            $('input[name="delete_facebook_cache"]').prop('disabled', true); 
                        } else {
                            removeNotice();
                            $('input[name="update_facebook_cache"]').prop('disabled', false); // Enable the button
                            $('input[name="delete_facebook_cache"]').prop('disabled', fasle); 
                        }
                    }
                });
            }

            // Function to display the notification if it doesn't already exist
            function showNotice() {
                if ($('#cache-update-notice').length === 0) {
                    // Prepend the notification to the admin page
                    $('.wrap').prepend('<div class="notice notice-warning is-dismissible" id="cache-update-notice"><p><strong>Cache update is in progress.</strong> Please wait...</p></div>');
                }
            }

            // Function to remove the notification
            function removeNotice() {
                $('#cache-update-notice').fadeOut(500, function() {
                    $(this).remove(); // Remove the notification after it fades out
                });
            }

            // Immediate check for the cache update status when the page loads
            if (typeof cacheStatus !== 'undefined' && cacheStatus === 'in_progress') {
                showNotice();
                $('input[name="update_facebook_cache"]').prop('disabled', true); // Disable the button if cache update is in progress
            }

            // Check the cache status every 1 second
            setInterval(checkCacheUpdateStatus, 1000);
        });
    </script>
    <?php
}
}
// Add the function to the 'admin_footer' action to inject the script in the admin footer
add_action('admin_footer', 'enqueue_cache_update_check_script');

/**
 * AJAX callback function to check the cache update status.
 * This function is called by the JavaScript to check whether the cache update is in progress or completed.
 */
function check_cache_update_status() {
    // Check if the 'facebook_cache_update_in_progress' transient is set
    if (get_transient('facebook_cache_update_in_progress')) {
        echo 'in_progress'; // Return 'in_progress' if cache update is still ongoing
    } else {
        echo 'completed'; // Return 'completed' if the cache update is done
    }
    wp_die(); // Terminate the AJAX request
}
// Hook the function to the 'wp_ajax_' action to handle AJAX requests
add_action('wp_ajax_check_facebook_cache_update', 'check_cache_update_status');

/**
 * Inject the current cache status into frontend JavaScript for immediate use.
 * This ensures the page can immediately display the correct cache update status when it loads.
 */
function inject_cache_status_to_frontend() {
    // Determine if the cache update is in progress
    $cache_in_progress = get_transient('facebook_cache_update_in_progress') ? 'in_progress' : 'completed';
    ?>
    <script type="text/javascript">
        // Pass the cache status to JavaScript
        var cacheStatus = "<?php echo esc_js($cache_in_progress); ?>";
    </script>
    <?php
}
// Hook the function to the 'admin_footer' action to inject the cache status into the page
add_action('admin_footer', 'inject_cache_status_to_frontend');


/*****************************************************************************************************************************************************/
/*****************************************************************************************************************************************************/
// 3. usqp_fb_feed_cache_page()
// This function displays the cache management page where users can manually update the cache and modify the cache update frequency.
// / Cette fonction affiche la page de gestion du cache où les utilisateurs peuvent mettre à jour manuellement le cache et modifier la fréquence de mise à jour du cache.
function usqp_fb_feed_cache_page() {
    if (current_user_can('manage_options')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'usqp_facebook_feed';

        // Check if the user triggered a cache update
        if (isset($_POST['update_facebook_cache'])) {
            // Update the cache and store the update date
            $result = fetch_and_update_facebook_cache();
            echo '<div class="updated"><p>' . esc_html($result) . '</p></div>';
        }

        // Fetch the current cache update frequency
        $frequency = get_option('facebook_cache_frequency', 'hourly'); // Default is 'hourly'

        // Form to update the automatic update frequency
        if (isset($_POST['update_frequency'])) {
            $new_frequency = $_POST['update_frequency'];
            update_option('facebook_cache_frequency', $new_frequency);
            // Update the cron job according to the new frequency
            schedule_cache_update_task($new_frequency);
            
            // Notification of frequency update
            add_action('admin_notices', function() {
                echo '<div class="updated"><p>The cache update frequency has been updated.</p></div>';
            });

            // Update the $frequency variable to reflect the new setting
            $frequency = $new_frequency;
        }

        // Fectch the latest update from the database
        $row = $wpdb->get_row("SELECT last_cache_update FROM $table_name LIMIT 1");

        // Determine the last cache update
        $last_update = !empty($row->last_cache_update) ? date("Y-m-d H:i:s", strtotime($row->last_cache_update)) : 'No updates recorded yet';

        // Set update intervals
        $intervals = [
            'minute' => '+1 minute',
            'hourly' => '+1 hour',
            'daily' => '+1 day',
            'weekly' => '+1 week'
        ];

        // Calculate the next scheduled update
        $next_update = (!empty($row->last_cache_update) && isset($intervals[$frequency])) 
            ? date("Y-m-d H:i:s", strtotime($row->last_cache_update . " " . $intervals[$frequency])) 
            : 'Not scheduled yet';

        ?>
        <div class="wrap">
            <h1>Cache Management</h2>
            <p>Manage and update your Facebook feed cache settings here.</p>

            <div class="postbox">
            <h2><span>Cache Settings Details</span></h2>
            <!-- Current information -->
            <p><strong>Cache Update Frequency:</strong> <?php echo esc_html($frequency); ?></p>
            <p><strong>Last Cache Update:</strong> <?php echo esc_html($last_update); ?></p>
            <p><strong>Next Scheduled Update:</strong> <?php echo esc_html($next_update); ?></p>

           
            <form method="post" action="">
                <!-- Manual Cache Update Form -->
                <input type="submit" name="update_facebook_cache" class="button-primary" value="Update Cache Manually" />
                <!-- Manual Cache Deletion Form -->
                <input type="submit" name="delete_facebook_cache" class="button-primary" value="Delete Cache" onclick="return" />
            </form>

            <h3>Automatic Update Settings</h3>
            <!-- Form to modify the update frequency -->
            <form method="post" action="">
                <select name="update_frequency">
                    <option value="minute" <?php selected($frequency, 'minute'); ?>>Every 1 minute</option>
                    <option value="hourly" <?php selected($frequency, 'hourly'); ?>>Every 1 hour</option>
                    <option value="daily" <?php selected($frequency, 'daily'); ?>>Every 1 day</option>
                    <option value="weekly" <?php selected($frequency, 'weekly'); ?>>Every 1 week</option>
                </select>
                <input type="submit" class="button-primary" value="Save Frequency" />
            </form>
        </div>  
        
        <h2>Cache Content</h2>
            <?php display_facebook_cache_admin(); ?> 
        
        </div>
        <?php
    }
}

/*****************************************************************************************************************************************************/
/*****************************************************************************************************************************************************/
// 4. display_facebook_cache_admin()
// Displays a table of cached Facebook posts and reels
// / Affiche les publications et reels Facebook mis en cache dans un tableau.

function display_facebook_cache_admin() {
    global $wpdb;

    // Fetch Facebook page information from the table
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");

    // Retrieve `page_id` from the database
    $page_id = isset($row->page_id) ? $row->page_id : '';

    // Define cache directory
    $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';

    // Check if cache files exist
    $reels_file = $cache_dir . 'reels.json';
    $posts_file = $cache_dir . 'posts.json';

    if (!file_exists($reels_file) || !file_exists($posts_file)) {
        echo "<p>No cache content found. Please update the cache.</p>";
        return;
    }

    // Load data from cache
    $reels = json_decode(file_get_contents($reels_file), true);
    $posts = json_decode(file_get_contents($posts_file), true);

    // Check if JSON data is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<p>Error in decoding JSON data. Please check the cache files.</p>";
        return;
    }

    // Function to calculate elapsed time
    function time_elapsed($timestamp) {
        $timestamp = strtotime($timestamp);
        if (!$timestamp) {
            return "Invalid date";
        }

        $time_difference = time() - $timestamp;
        $units = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
        ];

        foreach ($units as $unit => $value) {
            if ($time_difference >= $value) {
                $count = floor($time_difference / $value);
                return "{$count} {$unit}" . ($count > 1 ? 's' : '');
            }
        }

        return "less than a minute ago";
    }

    // Function to sort by date (ISO 8601 format)
    function sort_by_date($a, $b) {
        $a_time = isset($a['updated_time']) ? strtotime($a['updated_time']) : (isset($a['created_time']) ? strtotime($a['created_time']) : 0);
        $b_time = isset($b['updated_time']) ? strtotime($b['updated_time']) : (isset($b['created_time']) ? strtotime($b['created_time']) : 0);
        
        return $b_time - $a_time;  // Sort descending (most recent first)
    }

    // Combine reels and posts into a single array
    $all_posts_and_reels = [];

    // **Add reels**
    if (isset($reels) && !empty($reels)) {
        foreach ($reels as $reel) {
            $all_posts_and_reels[] = [
                'type' => 'reel',
                'id' => isset($reel['id']) ? $reel['id'] : 'N/A',
                'created_time' => isset($reel['updated_time']) ? $reel['updated_time'] : (isset($reel['created_time']) ? $reel['created_time'] : 'Unknown'),
                'description' => isset($reel['description']) ? $reel['description'] : 'No description available',
                'permalink_url' => isset($reel['permalink_url']) ? $reel['permalink_url'] : '#',
            ];
        }
    }

    // **Add posts**
    if (isset($posts['data']) && !empty($posts['data'])) {
        foreach ($posts['data'] as $post) {
            $all_posts_and_reels[] = [
                'type' => 'post',
                'id' => isset($post['id']) ? $post['id'] : 'N/A',
                'created_time' => isset($post['created_time']) ? $post['created_time'] : 'Unknown',
                'message' => isset($post['message']) ? $post['message'] : 'No message',
                'page_id' => $page_id,
            ];
        }
    }

    // Sort posts and reels combined by publication date
    usort($all_posts_and_reels, 'sort_by_date');

    // Start display table
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>Type</th>
                <th>Thumbnail</th>
                <th>Content</th>
                <th>Published</th>
                <th>View</th>
            </tr>
          </thead>';
    echo '<tbody>';

    // **Display combined and sorted items**
    foreach ($all_posts_and_reels as $item) {
        $created_time = time_elapsed($item['created_time']);  // Calculate elapsed time
        $thumbnail_html = '';  // Initialize thumbnail variable

        // Display videos (Reels)
        if ($item['type'] == 'reel') {
            $video_id = $item['id'];
            $thumbnail_path = wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/thumbnail_video_{$video_id}.jpg";
            $thumbnail_html = file_exists($cache_dir . "thumbnail_video_{$video_id}.jpg")
                ? "<img src='{$thumbnail_path}' width='120' height='80' style='object-fit:cover;border-radius:5px;' />"
                : "<img src='https://via.placeholder.com/120x80?text=No+Preview' width='120' height='80' style='object-fit:cover;border-radius:5px;' />";
            echo "<tr>";
            echo "<td>🎥 Reel</td>";
            echo "<td>{$thumbnail_html}</td>";
            echo "<td>" . esc_html(mb_strimwidth($item['description'], 0, 100, "...")) . "</td>";
            echo "<td>posted {$created_time}</td>";
            echo "<td><a href='{$item['permalink_url']}' target='_blank'>🔗 View</a></td>";
            echo "</tr>";
        }

        // Display posts
        if ($item['type'] == 'post') {
            $post_id = $item['id'];
            $message = $item['message'];
            $thumbnail_path = wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/image_post_{$post_id}.jpg";
            $thumbnail_html = file_exists($cache_dir . "image_post_{$post_id}.jpg")
                ? "<img src='{$thumbnail_path}' width='120' height='80' style='object-fit:cover;border-radius:5px;' />"
                : '📝';
            $post_permalink = "https://www.facebook.com/{$item['page_id']}/posts/{$post_id}";

            echo "<tr>";
            echo "<td>📝 Post</td>";
            echo "<td>{$thumbnail_html}</td>";
            echo "<td>" . esc_html(mb_strimwidth($message, 0, 100, "...")) . "</td>";
            echo "<td>posted {$created_time}</td>";
            echo "<td><a href='{$post_permalink}' target='_blank'>🔗 View</a></td>";
            echo "</tr>";
        }
    }

    echo '</tbody>';
    echo '</table>';
}

/*****************************************************************************************************************************************************/
// 5. schedule_cache_update_task()
// This function schedules a cron job based on the selected update frequency to automatically update the cache.
// / Cette fonction programme une tâche cron en fonction de la fréquence de mise à jour sélectionnée pour mettre à jour automatiquement le cache.
function schedule_cache_update_task($frequency) {
    global $wpdb;
    
    // Fetch Facebook page information from the table
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");

    if ($row) {
        // Update the cache_frequency column based on the selected frequency
        $wpdb->update(
            $table_name,
            array(
                'cache_frequency' => $frequency, 
            ),
            array('id' => $row->id), 
            array('%s', '%s'),  
            array('%d') 
        );
    }
    
    // Remove any existing cron jobs
    $timestamp = wp_next_scheduled('usqp_facebook_feed_cache_update_cron');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'usqp_facebook_feed_cache_update_cron');
    }

    // Schedule a new cron job according to the frequency
    switch ($frequency) {
        case 'minute':
            wp_schedule_event(time(), 'every_minute', 'usqp_facebook_feed_cache_update_cron');
            break;
        case 'hourly':
            wp_schedule_event(time(), 'hourly', 'usqp_facebook_feed_cache_update_cron');
            break;
        case 'daily':
            wp_schedule_event(time(), 'daily', 'usqp_facebook_feed_cache_update_cron');
            break;
        case 'weekly':
            wp_schedule_event(time(), 'weekly', 'usqp_facebook_feed_cache_update_cron');
            break;
    }
}

/*****************************************************************************************************************************************************/
// 6. add_custom_cron_intervals()
// This function adds custom cron intervals such as 'every_minute' to allow updates every minute.
// / Cette fonction ajoute des intervalles cron personnalisés tels que 'every_minute' pour permettre des mises à jour toutes les minutes.
function add_custom_cron_intervals($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every 1 minute')
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_custom_cron_intervals');

/*****************************************************************************************************************************************************/
// 7. fetch_and_update_facebook_cache_cron_handler()
// This function handles the cron job for automatically updating the cache based on the scheduled frequency.
// / Cette fonction gère la tâche cron pour la mise à jour automatique du cache en fonction de la fréquence programmée.
function fetch_and_update_facebook_cache_cron_handler() {
    fetch_and_update_facebook_cache();
}
add_action('usqp_facebook_feed_cache_update_cron', 'fetch_and_update_facebook_cache_cron_handler');


/*****************************************************************************************************************************************************/
// 8. delete_facebook_cache()
// This function deletes the cached Facebook feed data and associated media files.
// / Cette fonction supprime les données du flux Facebook mises en cache et les fichiers multimédias associés.

// Check if the user triggered a cache deletion
if (isset($_POST['delete_facebook_cache'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';

    // Delete all cache files
    array_map('unlink', glob($cache_dir . "*"));

    // Set last_cache_update to NULL in the database
    $wpdb->query("UPDATE $table_name SET last_cache_update = NULL");

    echo '<div class="updated"><p>Cache has been successfully deleted from files and database.</p></div>';
}