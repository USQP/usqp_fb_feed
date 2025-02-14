/*********************************************************************************************
 * Summary for the frontend_integration.js file
 * Sommaire pour le fichier frontend_integration.js
 *********************************************************************************************
 *
 * 1. Read More / Read Less Toggle - Handles the functionality to show and hide full text in feed items.
 *                                      / Gère la fonctionnalité pour afficher et masquer le texte complet dans les éléments du flux.
 * 2. Slick Slider Initialization - Initializes the Slick slider when the 'slider' mode is selected in the Facebook feed.
 *                                      / Initialise le slider Slick lorsque le mode 'slider' est sélectionné dans le flux Facebook.
 * 
 *********************************************************************************************/

// 1. Read More / Read Less Toggle
// Handles the functionality to show and hide full text in feed items.
// / Gère la fonctionnalité pour afficher et masquer le texte complet dans les éléments du flux.
jQuery(document).ready(function() {
    // Handle 'Read more' and 'Read less' toggle
    jQuery('.facebook-feed').on('click', '.read-more', function(e) {
        e.preventDefault();
        var parent = jQuery(this).closest('.fb_content');
        parent.find('.short-text').hide();
        parent.find('.full-text').show();
        jQuery(this).hide();
        parent.find('.read-less').show();
    });

    jQuery('.facebook-feed').on('click', '.read-less', function(e) {
        e.preventDefault();
        var parent = jQuery(this).closest('.fb_content');
        parent.find('.full-text').hide();
        parent.find('.short-text').show();
        jQuery(this).hide();
        parent.find('.read-more').show();
    });

// 2. Slick Slider Initialization
//  Initializes the Slick slider when the 'slider' mode is selected in the Facebook feed.
// / Initialise le slider Slick lorsque le mode 'slider' est sélectionné dans le flux Facebook.
    if (jQuery('.facebook-feed.slider').length > 0) {
        jQuery('.facebook-feed.slider').slick({
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            autoplay: true,
            autoplaySpeed: 3000,
            dots: true,
            arrows: true,
            fade: false,
            speed: 500,
            pauseOnHover: true,
            pauseOnFocus: true,
            prevArrow: '<button type="button" class="slick-prev"></button>',
            nextArrow: '<button type="button" class="slick-next"></button>' 
        });
    }
});
