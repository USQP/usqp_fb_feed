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

    // Function to connect to Facebook
    window.connect_to_facebook = function() {
        document.getElementById('facebook-info').innerHTML = '';
        FB.login(function(response) {
            if (response.authResponse) {
                get_page_id(response.authResponse.accessToken);
            }
        }, {scope: 'public_profile,email,pages_show_list,pages_read_engagement,pages_read_user_content'});
    };

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

    // Function to renew the access token
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

    // Function to disconnect from Facebook
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

    // Load Facebook information from the database
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


