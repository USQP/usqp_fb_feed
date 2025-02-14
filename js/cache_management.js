/*********************************************************************************************
 * Summary for the cache_management.js file
 * Sommaire pour le fichier cache_management.js
 *********************************************************************************************
 *
 * 1. Check Cache Update Status - Verifies if the Facebook cache update is in progress and updates the UI accordingly.
 *                                      / Vérifie si la mise à jour du cache Facebook est en cours et met à jour l'interface utilisateur en conséquence.
 * 2. Auto-check Cache Status - Automatically checks the cache update status on page load and at regular intervals.
 *                                      / Vérifie automatiquement l'état de mise à jour du cache au chargement de la page et à intervalles réguliers.
 * 3. Update Selections - Handles the update process when users select items and trigger a cache update via AJAX.
 *                                      / Gère le processus de mise à jour lorsque les utilisateurs sélectionnent des éléments et déclenchent une mise à jour du cache via AJAX.
 *
 *********************************************************************************************/

// 1. Check Cache Update Status
// Verifies if the Facebook cache update is in progress and updates the UI accordingly.
// / Vérifie si la mise à jour du cache Facebook est en cours et met à jour l'interface utilisateur en conséquence.
function check_cache_update_status() {
    jQuery.ajax({
        url: cacheManagementData.ajaxurl,
        type: 'POST',
        data: {
            action: 'check_facebook_cache_update',
            _wpnonce: cacheManagementData.nonce
        },
        success: function(response) {
            if (response.trim() === 'in_progress') {
                // If cache update is in progress, show a notice and disable buttons
                if (jQuery('#cache-update-notice').length === 0) {
                    jQuery('.wrap').prepend('<div class="notice notice-warning is-dismissible" id="cache-update-notice"><p><strong>Cache update is in progress.</strong> Please wait...</p></div>');
                }
                jQuery('input[name="update_facebook_cache"]').prop('disabled', true);
                jQuery('input[name="delete_facebook_cache"]').prop('disabled', true);
            } else {
                // Remove notice and re-enable buttons when update is complete
                jQuery('#cache-update-notice').remove();
                jQuery('input[name="update_facebook_cache"]').prop('disabled', false);
                jQuery('input[name="delete_facebook_cache"]').prop('disabled', false);
            }
        }
    });
}

// 2. Auto-check Cache Status
// Automatically checks the cache update status on page load and at regular intervals.
// / Vérifie automatiquement l'état de mise à jour du cache au chargement de la page et à intervalles réguliers.
jQuery(document).ready(function() {
    // Initial check on page load
    if (cacheStatus === 'in_progress') {
        check_cache_update_status(); // Vérification initiale si le cache est en cours de mise à jour
    }
    setInterval(check_cache_update_status, 1000); // Vérification toutes les secondes
});

// 3. Update Selections
// Handles the update process when users select items and trigger a cache update via AJAX.
// / Gère le processus de mise à jour lorsque les utilisateurs sélectionnent des éléments et déclenchent une mise à jour du cache via AJAX.
jQuery(document).ready(function($) {
    $('#update-selections-btn').on('click', function(event) {
        event.preventDefault();

        var selectedItems = [];
        $('input[name="selected_items[]"]:checked').each(function() {
            selectedItems.push($(this).val()); // Collect selected item IDs
        });

        // Perform the AJAX request
        $.ajax({
            url: ajaxurl, // URL for the WordPress AJAX action
            method: 'POST',
            data: {
                action: 'update_facebook_cache',
                nonce: cacheManagement.nonce, // Use localized nonce
                selected_items: selectedItems
            },
            success: function(response) {
                // Remove all existing notifications
                $('.updated, .error').remove();

                // Check if response is success or error
                var message = response.success ? response.data.message : response.data.message;
                var className = response.success ? 'updated' : 'error'; // Success or error CSS class

                // Add notification to the admin panel
                $('.wrap').prepend('<div class="' + className + '"><p>' + message + '</p></div>');

                // Scroll to the top of the page
                window.scrollTo({ top: 0, behavior: 'smooth' }); // Smooth scroll to top
            },
            error: function() {
                alert('An error occurred while updating the selections.');
            }
        });
    });
});
