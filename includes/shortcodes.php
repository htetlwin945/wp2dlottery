<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Renders the customer portal form via a shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string The HTML output for the form.
 */
function custom_lottery_portal_shortcode_callback($atts) {
    // Start output buffering
    ob_start();
    ?>
    <div id="lottery-portal-wrapper">
        <h2><?php echo esc_html__('Check Your Lottery Numbers', 'custom-lottery'); ?></h2>
        <form id="lottery-portal-form">
            <p>
                <label for="portal-phone-number"><?php echo esc_html__('Enter your phone number:', 'custom-lottery'); ?></label>
                <input type="text" id="portal-phone-number" name="phone_number" required>
            </p>
            <?php wp_nonce_field('lottery_portal_nonce_action', 'lottery_portal_nonce'); ?>
            <p>
                <button type="submit"><?php echo esc_html__('Check My Numbers', 'custom-lottery'); ?></button>
            </p>
        </form>
        <div id="lottery-portal-results" style="margin-top: 20px;">
            <!-- Results will be loaded here via AJAX -->
        </div>
    </div>
    <?php
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('lottery_portal', 'custom_lottery_portal_shortcode_callback');