<?php
/*********************************************************************************************
 * Summary for the frontend_integration.php file
 * Sommaire pour le fichier frontend_integration.php
 *********************************************************************************************
 *
 * 1. display_feed_frontend() - Function to display the Facebook feed on the frontend by loading cached data (posts, reels, page info).
 *                                      / Fonction pour afficher le flux Facebook sur le frontend en chargeant les données mises en cache (publications, reels, informations de page).
 * 
 *********************************************************************************************/

/*****************************************************************************************************************************************************/

// 1. display_feed_frontend()
// This function displays the Facebook feed on the frontend by loading cached data (posts, reels, page info) from cache files selected. 
// It checks whether the required cache files exist, loads their content (posts, reels, page information), and sorts the feed by date. 
// It then displays the feed with appropriate media (image or video), page information, and links to the original posts or reels on Facebook.
// / Cette fonction affiche le flux Facebook sur le frontend en chargeant les données mises en cache (publications, reels, informations de la page) à partir des fichiers de cache sélectionné. 
// Elle vérifie si les fichiers de cache requis existent, charge leur contenu (publications, reels, informations de la page), et trie le flux par date. 
// Ensuite, elle affiche le flux avec les médias appropriés (image ou vidéo), les informations de la page, et les liens vers les publications ou reels originaux sur Facebook.

function display_feed_frontend() {
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
    $page_info_file = $cache_dir . 'page_info.json';
    $selected_content_file = $cache_dir . 'selected_content.json';

    if (!file_exists($reels_file) || !file_exists($posts_file) || !file_exists($page_info_file) || !file_exists($selected_content_file)) {
        echo "<p>No cache content found. Please update the cache.</p>";
        return;
    }

    // Load data from cache
    $reels = json_decode(file_get_contents($reels_file), true);
    $posts = json_decode(file_get_contents($posts_file), true);
    $page_info = json_decode(file_get_contents($page_info_file), true);
    $selected_content = json_decode(file_get_contents($selected_content_file), true);

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
                return "{$count} {$unit}" . ($count > 1 ? 's' : '') ;
            }
        }

        return "less than a minute ago";
    }

    // Load profile picture (logo of the page)
    $profile_picture_url = esc_url(wp_upload_dir()['baseurl'] . '/usqp/facebook-feed/profile_picture.jpg');

    // Load page title
    $page_title = isset($page_info['name']) ? esc_html($page_info['name']) : 'Facebook Page';

    // Merge and sort posts and reels
    $feed = [];

    // Process selected Reels
    foreach ($reels as $reel) {
        if (in_array($reel['id'], $selected_content)) {
            $reel_permalink = "https://www.facebook.com/{$page_id}/videos/{$reel['id']}/";

            $feed[] = [
                'type' => 'reel',
                'id' => $reel['id'],
                'description' => isset($reel['description']) ? esc_html($reel['description']) : 'No description',
                'date' => isset($reel['updated_time']) ? strtotime($reel['updated_time']) : 0,
                'video_url' => esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/video_{$reel['id']}.mp4"),
                'thumbnail_url' => esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/thumbnail_video_{$reel['id']}.jpg"),
                'permalink' => esc_url($reel_permalink),
            ];
        }
    }

    // Process selected Posts
    foreach ($posts['data'] as $post) {
        if (in_array($post['id'], $selected_content)) {
            $image_url = esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/image_post_{$post['id']}.jpg");
            $video_url = isset($post['attachments']['data'][0]['media']['source']) ? esc_url($post['attachments']['data'][0]['media']['source']) : '';
            $post_permalink = "https://www.facebook.com/{$page_id}/posts/{$post['id']}";

            $feed[] = [
                'type' => 'post',
                'id' => $post['id'],
                'message' => isset($post['message']) ? esc_html($post['message']) : 'No message',
                'date' => isset($post['created_time']) ? strtotime($post['created_time']) : 0,
                'image_url' => $image_url,
                'video_url' => $video_url,
                'permalink' => esc_url($post_permalink),

            ];
        }
    }

    // Sort feed by date (descending order: newest first)
    usort($feed, function($a, $b) {
        return $b['date'] - $a['date'];
    });

    // Display the feed
    echo "<div class='facebook-feed'>";
    if (!empty($feed)) {
        foreach ($feed as $item) {
            $itemType = $item['type'];
            $itemId = $item['id'];
            echo "<div class='feed-item {$itemType}' id='{$itemId}'>";
            
            // Section média (photo ou vidéo)
            echo "<div class='fb_media'>";
            if (!empty($item['image_url'])) {
                echo "<img src='{$item['image_url']}' alt='Post Image'>";
            }
            if (!empty($item['video_url'])) {
                echo "<video src='{$item['video_url']}' controls></video>";
            }
            echo "</div>";

            // Section globale avec logo, nom de la page et date
            echo "<div class='fb_global_info'>";
            echo "<div class='page-logo'><img src='{$profile_picture_url}' alt='Profile Picture' width='50' height='50'></div>";
            echo "<h3>{$page_title}</h3>";
            echo "<p>Posted: " . time_elapsed(date('Y-m-d H:i:s', $item['date'])) . " ago</p>";
            echo "</div>";
            
            // Section contenu (description ou message)
            echo "<div class='fb_content'>";
            if ($itemType === 'reel') {
                echo "<p>{$item['description']}</p>";
            } elseif ($itemType === 'post') {
                echo "<p>{$item['message']}</p>";
            }
            echo "<a href='{$item['permalink']}' target='_blank' class='fb-link'>Voir sur Facebook</a>";
            echo "</div>";
            
            echo "</div>";
        }
    } else {
        echo "<p>No selected content to display.</p>";
    }
}    

// Register the shortcode : [usqp_fb_feed]
add_shortcode('usqp_fb_feed', 'display_feed_frontend');