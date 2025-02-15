/*********************************************************************************************
 * Summary for the frontend_integration_management.js file
 * Sommaire pour le fichier frontend_integration_management.js
 *********************************************************************************************
 *
 * 1. CodeMirror Initialization - Initializes the CodeMirror editor for the textarea with id 'css_content'.
 *                                      / Initialise l'éditeur CodeMirror pour la zone de texte avec l'ID 'css_content'.
 * 2. CSS Modification Form Submission - Handles the submission of the CSS modification form via Ajax.
 *                                      / Gère l'envoi du formulaire de modification CSS via Ajax.
 * 3. CSS Reset Functionality - Resets the CSS content to its default state via Ajax.
 *                                      / Fonctionnalité de réinitialisation du CSS à son état par défaut via Ajax.
 *
 *********************************************************************************************/

// 1. CodeMirror Initialization
// Initializes the CodeMirror editor for the textarea with id "css_content".
// / Initialise l'éditeur CodeMirror pour la zone de texte avec l'ID "css_content".
    jQuery(document).ready(function($) {
        var editor = CodeMirror.fromTextArea(document.getElementById("css_content"), {
            mode: "css",
            theme: "dracula",
            lineNumbers: true,
            tabSize: 2
    });

// 2. CSS Modification Form Submission
// Handles the submission of the CSS modification form via Ajax.
// / Gère l'envoi du formulaire de modification CSS via Ajax.
    $('#css-modification-form').submit(function(e) {
        e.preventDefault();

        var cssContent = editor.getValue(); // Use the value from CodeMirror

        $.ajax({
            url: ajaxurl, 
            method: 'POST',
            data: {
                action: 'handle_css_modification',
                css_content: cssContent
            },
            success: function(response) {
                if (response.success) {
                    $('.notice').remove(); 
                    $('.wrap').prepend('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    editor.setValue(response.data.css_content); 
                    $('html, body').animate({ scrollTop: 0 }, 'slow'); 
                }
            }
        });
    });

// 3. CSS Reset Functionality
// Resets the CSS content to its default state via Ajax.
// / Fonctionnalité de réinitialisation du CSS à son état par défaut via Ajax.
    $('#reset_css').click(function(e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl, 
            method: 'POST',
            data: {
                action: 'handle_css_modification',
                reset_css: true
            },
            success: function(response) {
                if (response.success) {
                    $('.notice').remove(); 
                    $('.wrap').prepend('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                    editor.setValue(response.data.css_content); 
                    $('html, body').animate({ scrollTop: 0 }, 'slow'); 
                }
            }
        });
    });
});

// 4. Manages field for Custom class
// Function to show or hide custom class field
// / Fonction pour afficher ou masquer le champ de classe personnalisée
document.addEventListener('DOMContentLoaded', function() {
    const integrationSelect = document.getElementById('integration');
    const customClassContainer = document.getElementById('custom-class-container');

    function toggleCustomClassInput() {
        if (integrationSelect.value === 'custom_class_integration') {
            customClassContainer.style.display = 'block'; 
        } else {
            customClassContainer.style.display = 'none'; 
        }
    }

    toggleCustomClassInput();
    integrationSelect.addEventListener('change', toggleCustomClassInput);
});