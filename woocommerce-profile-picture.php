<?php
/**
 * Plugin Name: WooCommerce Profile Picture
 * Plugin URI: https://clickssmaster.com/
 * Description: Allows users to upload and set a profile picture in their WooCommerce account page, compatible with Simple Local Avatars.
 * Version: 1.0.0
 * Author: DeveloperAnonimous
 * Author URI: https://clickssmaster.com/
 * License: GPL-2.0+
 * Text Domain: woocommerce-profile-picture
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check for Simple Local Avatars dependency on plugin activation
 */
function wcpp_check_simple_local_avatars_dependency() {
    if ( ! is_plugin_active( 'simple-local-avatars/simple-local-avatars.php' ) ) {
        // Deactivate this plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // Display an error message
        wp_die(
            __( 'WooCommerce Profile Picture requires the Simple Local Avatars plugin to be installed and active. Please install and activate it before activating this plugin.', 'woocommerce-profile-picture' ),
            __( 'Plugin Activation Error', 'woocommerce-profile-picture' ),
            array( 'back_link' => true )
        );
    }
}
register_activation_hook( __FILE__, 'wcpp_check_simple_local_avatars_dependency' );

/**
 * Prevent plugin from running if Simple Local Avatars is not active
 */
function wcpp_check_plugin_requirements() {
    if ( ! is_plugin_active( 'simple-local-avatars/simple-local-avatars.php' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'WooCommerce Profile Picture requires the Simple Local Avatars plugin to function. Please install and activate it.', 'woocommerce-profile-picture' ) . '</p></div>';
        });

        // Deactivate this plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'admin_init', 'wcpp_check_plugin_requirements' );
/**
 * Add image upload field to the WooCommerce edit account form.
 */
function wcpp_add_profile_picture_field() {
    ?>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="image"><?php esc_html_e( 'Profile Picture', 'woocommerce-profile-picture' ); ?>&nbsp;<span class="required">*</span></label>
        <input type="file" class="woocommerce-Input" name="image" accept="image/x-png,image/gif,image/jpeg">
    </p>
    <?php
}
add_action( 'woocommerce_edit_account_form_start', 'wcpp_add_profile_picture_field' );

/**
 * Validate image upload during account details save.
 */
function wcpp_validate_profile_picture( $args ) {
    if ( isset( $_FILES['image'] ) && empty( $_FILES['image']['name'] ) ) {
        $args->add( 'image_error', __( 'Please upload a valid profile picture.', 'woocommerce-profile-picture' ) );
    }
}
add_action( 'woocommerce_save_account_details_errors', 'wcpp_validate_profile_picture', 10, 1 );

/**
 * Save the uploaded image and associate it as the user's profile picture.
 */
function wcpp_save_profile_picture( $user_id ) {
    if ( isset( $_FILES['image'] ) && ! empty( $_FILES['image']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Handle file upload
        $attachment_id = media_handle_upload( 'image', 0 );

        if ( is_wp_error( $attachment_id ) ) {
            error_log( 'Error uploading profile picture: ' . $attachment_id->get_error_message() );
        } else {
            // Get the attachment URL
            $attachment_url = wp_get_attachment_url( $attachment_id );

            // Prepare the array for Simple Local Avatars
            $avatar_data = [
                'full' => $attachment_url,
            ];

            // Save the avatar data in the 'simple_local_avatar' user meta
            update_user_meta( $user_id, 'simple_local_avatar', $avatar_data );

            // Optionally log for debugging
            error_log( "Avatar updated for user ID {$user_id}: {$attachment_url}" );
        }
    }
}
add_action( 'woocommerce_save_account_details', 'wcpp_save_profile_picture', 10, 1 );

/**
 * Add enctype to the WooCommerce edit account form to allow image upload.
 */
function wcpp_add_form_enctype() {
    echo 'enctype="multipart/form-data"';
}
add_action( 'woocommerce_edit_account_form_tag', 'wcpp_add_form_enctype' );
