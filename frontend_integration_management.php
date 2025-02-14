<?php
/*********************************************************************************************
 * Summary for the frontend_integration_management.php file
 * Sommaire pour le fichier frontend_integration_management.php
 *********************************************************************************************
 *
 * 1. usqp_fb_feed_integration_page() - Displays the frontend integration page with CSS modification options and shortcode generator.
 *                                      / Affiche la page d'intégration frontend avec les options de modification du CSS et le générateur de shortcode.
 * 2. handle_shortcode_generation() - Handles the logic for generating the shortcode based on form input.
 *                                      / Gère la logique de génération du shortcode en fonction des entrées du formulaire.
 * 3. handle_css_modification() - Handles the logic for modifying and resetting the CSS.
 *                                      / Gère la logique de modification et de réinitialisation du CSS.
 * 4. enqueue_code_mirror() - Enqueues the necessary scripts and styles for CodeMirror editor.
 *                                      / Enfile les scripts et styles nécessaires pour l'éditeur CodeMirror.
 *  5. enqueue_frontend_integration_management_js () - Enqueues the custom JavaScript for frontend integration management, handles the Ajax request, and initializes CodeMirror
*                                       / Enfile le JavaScript personnalisé pour la gestion de l'intégration frontend, gère la requête Ajax et initialise CodeMirror
*
*********************************************************************************************/

// 1 . usqp_fb_feed_integration_page()
// Displays the frontend integration page with CSS modification options and shortcode generator.
// / Affiche la page d'intégration frontend avec les options de modification du CSS et le générateur de shortcode.
function usqp_fb_feed_integration_page() {
    $css_file = plugin_dir_path(__FILE__) . 'css/frontend_integration.css';
    $custom_css_file = plugin_dir_path(__FILE__) . 'css/custom_frontend_integration.css';

    // Retrieve CSS content (either custom or default)
    $css_content = get_css_content($css_file, $custom_css_file);

    // Process form actions
    $shortcode = '';
    $message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Generate the shortcode only if the "Generate Shortcode" button is pressed
        if (isset($_POST['generate_shortcode'])) {
            $shortcode = handle_shortcode_generation();
        }
    }

    // Enqueue CodeMirror scripts and styles
    enqueue_code_mirror();

    // Page HTML
    ?>
    <div class="wrap">
        <h1>Frontend Integration</h1>
        <p>Modify your CSS to customize the display of the feed.</p>

        <!-- Form to modify CSS -->
        <?php display_css_modification_form($css_content); ?>

        <!-- Shortcode Generator -->
        <?php display_shortcode_generator_form(); ?>

        <!-- Display the generated shortcode only after submission -->
        <?php if (!empty($shortcode)): ?>
            <div class="updated">
                <p><strong>Generated shortcode:</strong></p>
                <textarea rows="1" cols="100" readonly><?php echo esc_textarea($shortcode); ?></textarea>
            </div>
        <?php endif; ?>

        <!-- Notification message -->
        <?php if (!empty($message)): ?>
            <div class="updated">
                <p><strong><?php echo esc_html($message); ?></strong></p>
            </div>
        <?php endif; ?>

    </div>
    <?php
}


// 2. handle_shortcode_generation()
// Handles the logic for generating the shortcode based on form input.
// / Gère la logique de génération du shortcode en fonction des entrées du formulaire.
function handle_shortcode_generation() {
    if (isset($_POST['generate_shortcode'])) {
        $atts = [
            'limit' => isset($_POST['limit']) ? intval($_POST['limit']) : -1,
            'order' => isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC',
            'word_limit' => isset($_POST['word_limit']) ? intval($_POST['word_limit']) : 75,
            'display' => isset($_POST['display']) ? sanitize_text_field($_POST['display']) : 'list',
            'type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all',
        ];

        // Generate the shortcode with the selected attributes
        $shortcode = '[usqp_fb_feed ';
        foreach ($atts as $key => $value) {
            $shortcode .= $key . '="' . esc_attr($value) . '" ';
        }
        $shortcode .= ']';
        return $shortcode; // Return the generated shortcode
    }
    return ''; // Return an empty string if no shortcode was generated
}

// Displays the shortcode generator form
function display_shortcode_generator_form() {
    ?>
    <div class="postbox">
        <h2>Shortcode Generator</h2>
        <p>Use this generator to create a custom shortcode for the Facebook feed.</p>
        <p>Select the options below to customize the feed display.</p>
        
        <form method="post">
            <!-- Limit -->
            <label for="limit">Limit:</label><br>
            <input type="number" id="limit" name="limit" value="-1" min="-1" placeholder="Number of items to display (default: -1 to display all)"><br>
            <small>Set the maximum number of items to display in the feed. Use -1 to display all items.</small><br><br>

            <!-- Order -->
            <label for="order">Order:</label><br>
            <select id="order" name="order">
                <option value="DESC">Descending (newest to oldest)</option>
                <option value="ASC">Ascending (oldest to newest)</option>
            </select><br>
            <small>Choose the order of items in the feed. By default, items are sorted from newest to oldest.</small><br><br>

            <!-- Word limit -->
            <label for="word_limit">Word Limit:</label><br>
            <input type="number" id="word_limit" name="word_limit" value="75" min="-1" placeholder="Limit the number of words displayed in each post"><br>
            <small>Limit the number of words displayed for each item. Use -1 to display the full content.</small><br><br>

            <!-- Display -->
            <label for="display">Display:</label><br>
            <select id="display" name="display">
                <option value="list">List</option>
                <option value="slider">Slider (Carousel)</option>
            </select><br>
            <small>Choose the display mode for the feed. By default, it is displayed as a list. Select "Slider" for a carousel.</small><br><br>

            <!-- Content type -->
            <label for="type">Content Type:</label><br>
            <select id="type" name="type">
                <option value="all">All content (posts and reels)</option>
                <option value="reels">Reels only</option>
                <option value="posts">Posts only</option>
            </select><br>
            <small>Choose the type of content to display. By default, both types (posts and reels) are shown.</small><br><br>

            <!-- Generate button -->
            <input type="submit" name="generate_shortcode" value="Generate Shortcode" class="button button-primary"><br><br>
        </form>
    </div>
    <?php
}


// 3. handle_css_modification()
// Handles the logic for modifying and resetting the CSS.
// / Gère la logique de modification et de réinitialisation du CSS.
add_action('wp_ajax_handle_css_modification', 'handle_css_modification_ajax');
add_action('wp_ajax_nopriv_handle_css_modification', 'handle_css_modification_ajax');

// Function that handles CSS modification via Ajax
function handle_css_modification_ajax() {
    $css_file = plugin_dir_path(__FILE__) . 'css/frontend_integration.css'; // Path to the default CSS file
    $custom_css_file = plugin_dir_path(__FILE__) . 'css/custom_frontend_integration.css'; // Path to the custom CSS file

    $message = '';

    // Process CSS modification
    if (isset($_POST['css_content'])) {
        // If the custom file does not exist, create it
        if (!file_exists($custom_css_file)) {
            file_put_contents($custom_css_file, stripslashes($_POST['css_content']));
            $message = 'CSS has been successfully updated.';
        } else {
            // If the custom file exists, replace it
            file_put_contents($custom_css_file, stripslashes($_POST['css_content']));
            $message = 'CSS has been successfully updated.';
        }
    }

    // CSS Reset
    if (isset($_POST['reset_css'])) {
        if (file_exists($custom_css_file)) {
            unlink($custom_css_file); // Delete the custom file
            $message = 'CSS has been reset.';
        } else {
            $message = 'No custom CSS to reset.';
        }
    }

    // Retrieve CSS content after modification
    $css_content = get_css_content($css_file, $custom_css_file);

    // Return an Ajax response
    wp_send_json_success([
        'message' => $message,
        'css_content' => $css_content
    ]);
}

// Retrieves the CSS content based on the existence of the custom file
function get_css_content($css_file, $custom_css_file) {
    // If the custom CSS file exists, use it
    if (file_exists($custom_css_file)) {
        return file_get_contents($custom_css_file);
    }
    // Otherwise, load the default CSS file
    else {
        return file_get_contents($css_file);
    }
}

// Displays the form to modify the CSS
function display_css_modification_form($css_content) {
    ?>
    <div class="postbox">
        <h2>Modify CSS</h2>
        <p>Edit your CSS to customize the feed display.</p>
        <form id="css-modification-form" method="post">
            <label for="css_content">CSS Content:</label><br>
            <textarea id="css_content" name="css_content" rows="10" cols="100"><?php echo esc_textarea($css_content); ?></textarea><br><br>
            <input type="submit" value="Save Changes" class="button button-primary">
            <input type="button" id="reset_css" value="Reset" class="button button-secondary">
        </form>
    </div>
    <?php
}

// 4. enqueue_code_mirror()
// Enqueues the necessary scripts and styles for the CodeMirror editor.
// / Enfile les scripts et styles nécessaires pour l'éditeur CodeMirror.
function enqueue_code_mirror() {
    wp_enqueue_style('codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css');
    wp_enqueue_style('codemirror-theme', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/dracula.min.css');
    wp_enqueue_script('codemirror', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js', array(), null, true);
    wp_enqueue_script('codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/css/css.min.js', array('codemirror'), null, true);
}

// Enqueue CodeMirror scripts and styles
add_action('admin_enqueue_scripts', 'enqueue_code_mirror');

// 5. enqueue_frontend_integration_management_js ()
// Enqueues the custom JavaScript for frontend integration management, handles the Ajax request, and initializes CodeMirror
// / Enfile le JavaScript personnalisé pour la gestion de l'intégration frontend, gère la requête Ajax et initialise CodeMirror
function enqueue_frontend_integration_management_js() {
    wp_enqueue_script(
        'frontend-integration-management', 
        plugin_dir_url(__FILE__) . 'js/frontend_integration_management.js', 
        array('jquery', 'codemirror'), 
        null, 
        true 
    );
}
add_action('admin_enqueue_scripts', 'enqueue_frontend_integration_management_js');




