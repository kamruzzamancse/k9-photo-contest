<?php
/*
Plugin Name: K9 Photo Contest
Description: A plugin for managing K9 photo contests.
Version: 1.0
Author: B-STA
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin directory.
define('K9_PHOTO_CONTEST_DIR', plugin_dir_path(__FILE__));
define('K9_PHOTO_CONTEST_URL', plugin_dir_url(__FILE__));

// Include necessary files.
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-cpt.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-form-handler.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-post-display.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-leaderboard.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-paypal.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-vote-cast.php';
include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-vote-handler.php';
//include_once K9_PHOTO_CONTEST_DIR . 'includes/k9-submission-template.php';

// Enqueue styles and scripts.
function k9_enqueue_scripts() {

    //Enqueue Styles
    wp_enqueue_style('k9-bootstrap-css', K9_PHOTO_CONTEST_URL . 'assets/css/bootstrap.min.css' );
    wp_enqueue_style('k9-styles', K9_PHOTO_CONTEST_URL . 'assets/css/style.css');

    // Enqueue Font Awesome
    wp_enqueue_style('k9-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', false, '6.4.2', 'all');

    // Enqueue google fonts
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:wght@400;700&family=Open+Sans:wght@400;700&display=swap', false);

    //Enqueue Scripts
    wp_enqueue_script('k9-bootstrap-js', K9_PHOTO_CONTEST_URL . 'assets/js/bootstrap.bundle.min.js');
    wp_enqueue_script('k9-scripts', K9_PHOTO_CONTEST_URL . 'assets/js/script.js', ['jquery'], false, true);    

    //Enqueue Scripts
    wp_enqueue_script('k9-voting', K9_PHOTO_CONTEST_URL . 'assets/js/k9-voting.js', array('jquery'), null, true);
    wp_localize_script('k9-voting', 'k9_voting_ajax', array(
        'ajax_url'    => admin_url('admin-ajax.php'),
        'nonce'       => wp_create_nonce('k9_voting_nonce'),
        'is_logged_in' => is_user_logged_in(), // Pass login status to JavaScript
    ));
}
add_action('wp_enqueue_scripts', 'k9_enqueue_scripts');

// Enqueue Scripts paid
function k9_enqueue_scripts_paid() {

    // Retrieve PayPal credentials
    $paypal_credentials = k9_get_paypal_credentials();
    $client_id = $paypal_credentials['client_id'];

    // Include PayPal SDK
    wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . $client_id . '&components=buttons', [], null, true);

    // Pass PayPal Client ID and AJAX URL to JavaScript
    wp_localize_script('k9-voting', 'k9_voting_ajax_paid', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'paypal_client_id' => $client_id
    ]);
}
add_action('wp_enqueue_scripts', 'k9_enqueue_scripts_paid');

function k9_admin_enqueues() {
    //Enqueue admin styles
    wp_enqueue_style('k9-admin-styles', K9_PHOTO_CONTEST_URL . 'assets/admin/css/admin-style.css');
}
add_action('admin_enqueue_scripts','k9_admin_enqueues');

// function for custom template
function custom_k9_submission_template($single_template) {
    global $post;

    // Ensure $post is set before accessing its properties
    if (!isset($post) || empty($post)) {
        return $single_template; // Return default template if no post is available
    }

    if ($post->post_type == 'k9_submission') {
        $plugin_dir = plugin_dir_path(__FILE__);
        $template_path = $plugin_dir . '/templates/single-k9-submission.php';

        if (file_exists($template_path)) {
            return $template_path;
        }
    }

    return $single_template;
}
add_filter('template_include', 'custom_k9_submission_template');

// Shortcode for displaying the purchase vote button
function k9_purchase_votes_button() {
    if (is_user_logged_in()) {
        ob_start();
        ?>
        <div class="k9-purchase-votes-container">
            <button id="purchase-votes" class="purchase-votes">Purchase Votes</button><br/><br/>
            <div id="paypal-button-container"></div>
        </div>
        <?php
        return ob_get_clean();
    } else {
        return '<p>You must be logged in to purchase votes.</p>';
    }
}
add_shortcode('k9_purchase_votes', 'k9_purchase_votes_button');

// Shortcode for displaying the donation button
function k9_donation_button() {
    if (is_user_logged_in()) {
        ob_start();
        ?>
        <div class="k9-donation-container">
            <button id="donation" class="donation">Donation</button><br/><br/>
            <div id="paypal-button-container-donation"></div>
        </div>
        <?php
        return ob_get_clean();
    } else {
        return '<p>You must be logged in to donate</p>';
    }
}
add_shortcode('k9_donation', 'k9_donation_button');

// Shortcode to display the number of purchased votes
function k9_display_purchased_votes() {
    $user_id = get_current_user_id();
    if ($user_id) {
        $purchased_votes = get_user_meta($user_id, 'k9_purchased_votes', true);
        $purchased_votes = empty($purchased_votes) ? 0 : intval($purchased_votes);
        return "<p>Your purchased Vote(s): $purchased_votes</p>";
    } else {
        return '<p>You must be logged in to view your purchased votes.</p>';
    }
}
add_shortcode('k9_display_purchased_votes', 'k9_display_purchased_votes');

