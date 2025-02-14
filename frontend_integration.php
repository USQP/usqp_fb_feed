<?php
/*********************************************************************************************
 * Summary for the frontend_integration.php file
 * Sommaire pour le fichier frontend_integration.php
 *********************************************************************************************
 *
 * 1. enqueue_slick_slider() - Loads the necessary Slick slider scripts and styles.
 *                                      / Charge les scripts et styles nécessaires pour le slider Slick.
 * 2. add_shortcode('usqp_fb_feed', 'display_feed_frontend') - Registers a shortcode [usqp_fb_feed] that outputs the Facebook feed by calling the display_feed_frontend() function.
 *                                     / Enregistre un shortcode [usqp_fb_feed] qui affiche le flux Facebook en appelant la fonction display_feed_frontend().
 * 3. enqueue_font_awesome() - Loads the necessary Font Awesome styles.
 *                                     / Charge les styles nécessaires pour Font Awesome.
 * 4. enqueue_facebook_feed_styles() - Function to include the specific Facebook feed CSS file in the frontend.
 *                                     / Fonction pour inclure le fichier CSS spécifique au flux Facebook dans le frontend.
 * 5. render_facebook_feed() - Displays the Facebook feed by loading cached data (posts, reels, page info).
 *                                      / Affiche le flux Facebook en chargeant les données mises en cache (publications, reels, infos page).
 * 6. enqueue_frontend_integration_js() - Enqueues the custom JavaScript for frontend integration, handling the "Read more" / "Read less" functionality and the Slick slider.
 *                                     / Charge le JavaScript personnalisé pour l'intégration frontend, gérant la fonctionnalité "Lire plus" / "Lire moins" et le slider Slick.
 * 
 *********************************************************************************************/

// 1. enqueue_slick_slider()
// Loads the necessary Slick slider scripts and styles.
// / Charge les scripts et styles nécessaires pour le slider Slick.
function enqueue_slick_slider() {
    // Add the CSS files for Slick.js
    wp_enqueue_style('slick-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');
    wp_enqueue_style('slick-theme-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css');

    // Add the JS file for Slick.js
    wp_enqueue_script('slick-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', ['jquery'], null, true);
}

add_action('wp_enqueue_scripts', 'enqueue_slick_slider');

// 2. add_shortcode('usqp_fb_feed', 'display_feed_frontend')
// Registers a shortcode [usqp_fb_feed] that outputs the Facebook feed by calling the display_feed_frontend() function.
// / Enregistre un shortcode [usqp_fb_feed] qui affiche le flux Facebook en appelant la fonction display_feed_frontend().
add_shortcode('usqp_fb_feed', 'display_feed_frontend');

// 3. enqueue_font_awesome()
// Loads the necessary Font Awesome styles.
// / Charge les styles nécessaires pour Font Awesome.
function enqueue_font_awesome() {
    // Add the CSS file for Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');
}

add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

// 4. enqueue_facebook_feed_styles()
// Fonction pour inclure le fichier CSS spécifique au flux Facebook dans le frontend.
// / Cette fonction charge le fichier CSS 'facebook-feed.css' uniquement sur les pages où le shortcode [usqp_fb_feed] est utilisé.
function enqueue_facebook_feed_styles() {
    // Define the paths for the CSS files
    $default_css_file = plugin_dir_url(__FILE__) . 'css/frontend_integration.css';
    $custom_css_file = plugin_dir_url(__FILE__) . 'css/custom_frontend_integration.css';

    // Check if the custom CSS file exists
    if (file_exists(plugin_dir_path(__FILE__) . 'css/custom_frontend_integration.css')) {
        // If the custom file exists, load custom_frontend_integration.css
        wp_enqueue_style('facebook-feed-style', $custom_css_file);
    } else {
        // Otherwise, load the default CSS file
        wp_enqueue_style('facebook-feed-style', $default_css_file);
    }
}

add_action('wp_enqueue_scripts', 'enqueue_facebook_feed_styles');

// 5. display_feed_frontend()
// Displays the Facebook feed by loading cached data (posts, reels, page info).
// / Affiche le flux Facebook en chargeant les données mises en cache (publications, reels, infos page).
function display_feed_frontend($atts) {
    global $wpdb;

    // Add the 'display' and 'type' attributes to choose between list and slider, and filter by type
    $atts = shortcode_atts(
        [
            'limit' => -1,
            // Defines the number of items to display. By default, there is no limit (all items are displayed).
            // Possible values: a positive integer (e.g., 5, 10), or -1 to show all items without any limit.
            
            'order' => 'DESC',
            // Defines the order in which the items are sorted (DESC for descending, ASC for ascending).
            // Possible values: 'ASC' (ascending), 'DESC' (descending).
    
            'word_limit' => 75, 
            // Limits the number of words displayed in a post's text.
            // Possible values: a positive integer (e.g., 50, 100), a specific integer like 20, or -1 to show the full text without limit.
    
            'display' => 'list', 
            // Defines the display mode for the feed (list or slider).
            // Possible values: 'list' (displayed as a list), 'slider' (displayed as a carousel).
            
            'type' => 'all', 
            // Defines the type of content to display. Filters for reels, posts, or both.
            // Possible values: 'all' (both reels and posts), 'reels' (only reels), 'posts' (only posts).

            'integration' => 'default_frontend_integration' 
            // Defines the integration type used.
            // Possible values: 'default_frontend_integration' (default), 'elementor_integration' : embeds the widget inside a div with the same class name
        ], 
        $atts,
        'usqp_fb_feed'
    );

    $limit = intval($atts['limit']);
    $order = strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC';
    $word_limit = intval($atts['word_limit']);
    $display = strtolower($atts['display']); 
    $type_filter = strtolower($atts['type']); 
    $integration = strtolower($atts['integration']); 

    $table_name = $wpdb->prefix . 'usqp_facebook_feed';
    $row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
    $page_id = isset($row->page_id) ? $row->page_id : '';

    $cache_dir = wp_upload_dir()['basedir'] . '/usqp/facebook-feed/';
    $reels_file = $cache_dir . 'reels.json';
    $posts_file = $cache_dir . 'posts.json';
    $page_info_file = $cache_dir . 'page_info.json';
    $selected_content_file = $cache_dir . 'selected_content.json';

    if (!file_exists($reels_file) || !file_exists($posts_file) || !file_exists($page_info_file) || !file_exists($selected_content_file)) {
        return "<p>No cache content found. Please update the cache.</p>";
    }

    $reels = json_decode(file_get_contents($reels_file), true);
    $posts = json_decode(file_get_contents($posts_file), true);
    $page_info = json_decode(file_get_contents($page_info_file), true);
    $selected_content = json_decode(file_get_contents($selected_content_file), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return "<p>Error in decoding JSON data. Please check the cache files.</p>";
    }

    $profile_picture_url = esc_url(wp_upload_dir()['baseurl'] . '/usqp/facebook-feed/profile_picture.jpg');
    $page_title = isset($page_info['name']) ? esc_html($page_info['name']) : 'Facebook Page';

    $feed = [];

    // Function to handle elapsed time
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
                return " {$count} {$unit}" . ($count > 1 ? 's' : '') . " ago";
            }
        }

        return "less than a minute ago";
    }

    // Filter reels if necessary
    if ($type_filter === 'all' || $type_filter === 'reels') {
        foreach ($reels as $reel) {
            if (in_array($reel['id'], $selected_content)) {
                $feed[] = [
                    'type' => 'reel',
                    'id' => $reel['id'],
                    'description' => isset($reel['description']) ? esc_html($reel['description']) : 'No description',
                    'date' => isset($reel['created_time']) ? strtotime($reel['created_time']) : 0,
                    'video_url' => esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/video_{$reel['id']}.mp4"),
                    'thumbnail_url' => esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/thumbnail_video_{$reel['id']}.jpg"),
                    'permalink' => esc_url("https://www.facebook.com/{$page_id}/videos/{$reel['id']}/"),
                ];
            }
        }
    }

    // Filter posts if necessary
    if ($type_filter === 'all' || $type_filter === 'posts') {
        foreach ($posts['data'] as $post) {
            if (in_array($post['id'], $selected_content)) {
                $feed[] = [
                    'type' => 'post',
                    'id' => $post['id'],
                    'message' => isset($post['message']) ? esc_html($post['message']) : 'No message',
                    'date' => isset($post['created_time']) ? strtotime($post['created_time']) : 0,
                    'image_url' => esc_url(wp_upload_dir()['baseurl'] . "/usqp/facebook-feed/image_post_{$post['id']}.jpg"),
                    'video_url' => isset($post['attachments']['data'][0]['media']['source']) ? esc_url($post['attachments']['data'][0]['media']['source']) : '',
                    'permalink' => esc_url("https://www.facebook.com/{$page_id}/posts/{$post['id']}")
                ];
            }
        }
    }

    // Sort the items
    usort($feed, function($a, $b) use ($order) {
        return $order === 'DESC' ? $b['date'] - $a['date'] : $a['date'] - $b['date'];
    });

    if ($limit > 0) {
        $feed = array_slice($feed, 0, $limit);
    }

    // Function to truncate the text and add a "Read more" / "Read less" link (handled by the JS script below)
    function truncate_text($text, $limit, $permalink) {
        if ($limit === -1) {
            return $text;
        }
        $words = explode(' ', $text);
        if (count($words) > $limit) {
            $short_text = implode(' ', array_slice($words, 0, $limit)) . "...";
            $full_text = esc_html($text);

            return "<span class='short-text'>{$short_text}</span> 
                    <span class='full-text' style='display:none;'>{$full_text}</span> 
                    <a href='#' target='_blank' class='read-more' data-toggle='full-text'>Read more</a>
                    <a href='#' class='read-less' style='display:none;'>Read less</a>";
        }
        return $text;
    }

    // Start HTML output
    $output .= "<div class='{$integration} facebook-feed {$display} '>"; // Class based on the display and integration mode

    foreach ($feed as $item) {
        $output .= "<div class='feed-item {$item['type']}' id='{$item['id']}'>";
        $output .= "<div class='fb_media'>";
        if (!empty($item['image_url'])) {
            $output .= "<img src='{$item['image_url']}' alt='Post Image'>";
        }
        if (!empty($item['video_url'])) {
            $output .= "<video controls>
                            <source src='{$item['video_url']}' type='video/mp4'>
                            Your browser does not support the video tag.
                        </video>";
        }
        $output .= "</div>";

        $output .= "<div class='fb_global_info'>";
        $output .= "<div class='page-logo'><img src='{$profile_picture_url}' alt='Profile Picture' width='50' height='50'></div>";
        $output .= "<div class='post-info'>";
        $output .= "<h3>{$page_title}</h3>";
        $output .= "<p>Posted: " . time_elapsed(date('Y-m-d H:i:s', $item['date'])) . "</p>";
        $output .= "</div>";
        $output .= "</div>";

        $output .= "<div class='fb_content'>";
        $output .= "<p>" . truncate_text($item['message'] ?? $item['description'], $word_limit, $item['permalink']) . "</p>";
        $output .= "</div>";

        $output .= "<div class='fb_view-link'>";
        $output .= "<a href='{$item['permalink']}' target='_blank' class='view-on-facebook'>View on Facebook</a>";
        $output .= "</div>";
        $output .= "<script src='" . plugin_dir_url(__FILE__) . "js/frontend_integration.js'></script>";


        $output .= "</div>";
    }

    $output .= "</div>";
    return $output;
}


