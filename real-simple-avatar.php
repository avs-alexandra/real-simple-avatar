<?php
/**
 * Plugin Name: Real Simple Avatar
 * Plugin URI: https://github.com/avs-alexandra/real-simple-avatar
 * Description: Upload avatar for users and set default avatar via media library.
 * Version: 1.0.0
 * Author: avsalexandra
 * Author URI: https://github.com/avs-alexandra
 * Text Domain: real-simple-avatar
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;
// Загрузка перевода плагина
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'real-simple-avatar',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});

function rsava_get_user_avatar_id( $user_id ) {
    return intval( get_user_meta( $user_id, 'rsava_user_avatar', true ) );
}

/**
 * Get custom user avatar URL (or empty string)
 */
function rsava_get_user_avatar_url( $user_id, $size = 96 ) {
    $avatar_id = rsava_get_user_avatar_id( $user_id );
    if ( $avatar_id && get_post_mime_type( $avatar_id ) && strpos( get_post_mime_type( $avatar_id ), 'image/' ) === 0 ) {
        $url = wp_get_attachment_image_url( $avatar_id, [ $size, $size ] );
        return $url ? $url : '';
    }
    return '';
}

/**
 * Get custom default avatar URL (or empty string)
 */
function rsava_get_default_avatar_url() {
    $avatar_id = (int)get_option('rsava_default_avatar_id', 0);
    if ($avatar_id) {
        $avatar_url = wp_get_attachment_url($avatar_id);
        if ($avatar_url) return $avatar_url;
    }
    return '';
}

/**
 * Echo avatar <img> tag (custom or standard)
 */
function rsava_avatar_img( $user_id, $size = 80, $attrs = [] ) {
    $avatar_id = rsava_get_user_avatar_id( $user_id );
    if ( $avatar_id ) {
        echo wp_get_attachment_image( $avatar_id, [ $size, $size ], false, $attrs );
    } else {
        echo get_avatar(
            $user_id,
            $size,
            '',
            'avatar',
            $attrs
        );
    }
}

// --- Media scripts for admin (old logic for admin) ---
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, ['profile.php', 'user-edit.php', 'options-discussion.php'])) {
        wp_enqueue_media();
        wp_enqueue_script(
            'rsava-avatar-media',
            plugins_url('assets/js/rsava-avatar-media.js', __FILE__),
            ['jquery'],
            '1.7.1',
            true
        );
    }
});

// --- Media scripts for frontend (shortcode) + styles ---
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'rsava-avatar-media',
            plugins_url('assets/js/rsava-avatar-media.js', __FILE__),
            ['jquery'],
            '1.7.1',
            true
        );
        // Передаём переводимые строки из PHP в JS:
        wp_localize_script('rsava-avatar-media', 'rsavaL10n', [
            'chooseDefaultAvatar' => __('Choose or upload default avatar', 'real-simple-avatar'),
            'chooseUserAvatar'    => __('Choose or upload user avatar', 'real-simple-avatar'),
            'use'                => __('Use', 'real-simple-avatar'),
            'fileTooLarge'       => __('File is larger than', 'real-simple-avatar'),
            'mb'                 => __('MB', 'real-simple-avatar'),
            'onlyImage'          => __('Only images can be uploaded (png, jpg, jpeg, heic)', 'real-simple-avatar'),
            'selected'           => __('Selected:', 'real-simple-avatar'),
            'uploadAvatar'       => __('Upload Avatar', 'real-simple-avatar'),
            'deleteImage'        => __('Delete image?', 'real-simple-avatar')
        ]);
        wp_enqueue_style(
            'rsava-avatar-custom',
            plugins_url('assets/css/rsava-avatar-custom.css', __FILE__),
            [],
            '1.0.0'
        );
    }
});

// --- User avatar field in user profile ---
add_action( 'show_user_profile', 'rsava_show_avatar_field' );
add_action( 'edit_user_profile', 'rsava_show_avatar_field' );
function rsava_show_avatar_field( $user ) {
    $avatar_id = rsava_get_user_avatar_id( $user->ID );
    ?>
    <table class="form-table">
        <tr>
            <th><label><?php esc_html_e( 'Upload Avatar', 'real-simple-avatar' ); ?></label></th>
            <td>
                <div id="rsava-user-avatar-preview" style="margin-bottom:10px;">
                    <?php
                    if ($avatar_id) {
                        echo wp_get_attachment_image($avatar_id, [80, 80], false, [
                            'style' => 'max-width:80px;display:block;margin-bottom:10px;',
                            'alt' => '',
                        ]);
                    }
                    ?>
                </div>
                <input type="hidden" name="rsava_user_avatar" id="rsava_user_avatar" value="<?php echo esc_attr($avatar_id); ?>" />
                <button type="button" class="button" id="rsava_select_avatar"><?php esc_html_e('Choose File', 'real-simple-avatar'); ?></button>
                <?php if ($avatar_id): ?>
                    <br><label><input type="checkbox" name="rsava_user_avatar_delete" value="1"> <?php esc_html_e( 'Delete Avatar', 'real-simple-avatar' ); ?></label>
                <?php endif; ?>
                <?php wp_nonce_field('rsava_avatar_admin_save', 'rsava_avatar_admin_nonce'); ?>
            </td>
        </tr>
    </table>
    <?php
}

// --- Save/delete user avatar ---
add_action( 'personal_options_update', 'rsava_save_avatar_field' );
add_action( 'edit_user_profile_update', 'rsava_save_avatar_field' );
function rsava_save_avatar_field( $user_id ) {
    if ( ! current_user_can( 'edit_user', $user_id ) ) return;
    $nonce = isset($_POST['rsava_avatar_admin_nonce']) ? sanitize_text_field(wp_unslash($_POST['rsava_avatar_admin_nonce'])) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rsava_avatar_admin_save' ) ) return;

    if ( ! empty( $_POST['rsava_user_avatar_delete'] ) ) {
        $old_avatar_id = rsava_get_user_avatar_id( $user_id );
        if ( $old_avatar_id ) {
            wp_delete_attachment( $old_avatar_id, true );
            delete_user_meta( $user_id, 'rsava_user_avatar' );
        }
        return;
    }
    if ( isset( $_POST['rsava_user_avatar'] ) ) {
        $new_avatar_id = intval( $_POST['rsava_user_avatar'] );
        $old_avatar_id = rsava_get_user_avatar_id( $user_id );
        if ( $new_avatar_id && $new_avatar_id !== $old_avatar_id ) {
            if ( $old_avatar_id ) {
                wp_delete_attachment( $old_avatar_id, true );
            }
            if ( get_post_mime_type($new_avatar_id) && strpos(get_post_mime_type($new_avatar_id), 'image/') === 0 ) {
                update_user_meta( $user_id, 'rsava_user_avatar', $new_avatar_id );
            } else {
                delete_user_meta( $user_id, 'rsava_user_avatar' );
            }
        }
    }
}

// --- Add custom default avatar as radio button via URL ---
add_filter('avatar_defaults', function($avatar_defaults) {
    $avatar_url = rsava_get_default_avatar_url();
    if ($avatar_url) {
        $avatar_defaults[$avatar_url] = __('Custom Avatar', 'real-simple-avatar');
    }
    return $avatar_defaults;
});

// --- Custom user avatar always takes priority ---
add_filter('get_avatar_url', function($url, $id_or_email, $args) {
    $user = false;
    if (is_object($id_or_email) && $id_or_email instanceof WP_User) {
        $user = $id_or_email;
    } elseif (is_numeric($id_or_email)) {
        $user = get_user_by('id', (int)$id_or_email);
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user = get_user_by('id', (int)$id_or_email->user_id);
    } elseif (is_string($id_or_email) && strpos($id_or_email, '@') !== false) {
        $user = get_user_by('email', $id_or_email);
    } else {
        return $url;
    }

    if ($user && is_object($user)) {
        $img_url = rsava_get_user_avatar_url($user->ID, isset($args['size']) ? $args['size'] : 96);
        if ($img_url) return $img_url;
    }
    return $url;
}, 10, 3);

// --- Default avatar upload field (Settings → Discussion) ---
add_action('admin_init', function() {
    register_setting('discussion', 'rsava_default_avatar_id', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 0
    ]);
    add_settings_field(
        'rsava_default_avatar',
        __('Default Avatar', 'real-simple-avatar'),
        'rsava_default_avatar_field_html',
        'discussion'
    );

    // New field: max avatar file size for shortcode (MB)
    register_setting('discussion', 'rsava_avatar_max_filesize_mb', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4
    ]);
    add_settings_field(
    'rsava_avatar_max_filesize_mb',
    __('Max avatar file size (MB, for shortcode only)', 'real-simple-avatar'),
    function() {
        $value = (int)get_option('rsava_avatar_max_filesize_mb', 4);
        echo '<input type="number" min="1" step="1" name="rsava_avatar_max_filesize_mb" value="' . esc_attr($value) . '" style="width:70px;"> ';
        echo '<span class="description">' . esc_html__('Default — 4 MB.', 'real-simple-avatar') . '</span>';
    },
    'discussion'
);
});
function rsava_default_avatar_field_html() {
    $avatar_id = (int)get_option('rsava_default_avatar_id', 0);
    ?>
    <div id="rsava-default-avatar-preview" style="margin-bottom:10px;">
        <?php
        if ($avatar_id) {
            echo wp_get_attachment_image($avatar_id, [90, 90], false, [
                'style' => 'max-width:90px;display:block;',
                'alt' => '',
            ]);
        } else {
            echo '<em style="color:#c00;">No default image selected.</em>';
        }
        ?>
    </div>
    <input type="hidden" id="rsava_default_avatar_id" name="rsava_default_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
    <button type="button" class="button" id="rsava_select_default_avatar"><?php esc_html_e('Upload', 'real-simple-avatar'); ?></button>
    <?php
}

// === Frontend: avatar upload/delete handling and shortcode ===

// --- Handle avatar upload (frontend) ---
add_action('admin_post_rsava_front_upload_avatar', 'rsava_front_handle_avatar_upload');
function rsava_front_handle_avatar_upload() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url() );
        exit;
    }
    $nonce = isset($_POST['rsava_avatar_front_nonce']) ? sanitize_text_field(wp_unslash($_POST['rsava_avatar_front_nonce'])) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rsava_avatar_front_save' ) ) {
        wp_safe_redirect( add_query_arg('rsava_error', 'nonce', wp_get_referer()) );
        exit;
    }

    $user_id = get_current_user_id();
    $redirect_url = wp_get_referer();

    $max_mb = (int)get_option('rsava_avatar_max_filesize_mb', 4);
    $max_bytes = $max_mb * 1024 * 1024;

    if (!empty($_FILES['rsava_user_avatar']['name'])) {
        if (isset($_FILES['rsava_user_avatar']['size']) && $_FILES['rsava_user_avatar']['size'] > $max_bytes) {
            wp_safe_redirect( add_query_arg('rsava_error', 'size', $redirect_url) );
            exit;
        }
        // Check for tmp_name key and it's not empty
        if (
            !isset($_FILES['rsava_user_avatar']['tmp_name']) ||
            empty($_FILES['rsava_user_avatar']['tmp_name'])
        ) {
            wp_safe_redirect( add_query_arg('rsava_error', 'type', $redirect_url) );
            exit;
        }
        // Validate mime-type on server (sanitize path for validation only, not for output)
        $tmp_name = filter_var($_FILES['rsava_user_avatar']['tmp_name'], FILTER_SANITIZE_STRING);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        if (strpos($mime, 'image/') !== 0) {
            wp_safe_redirect( add_query_arg('rsava_error', 'type', $redirect_url) );
            exit;
        }

        $old_avatar_id = rsava_get_user_avatar_id($user_id);
        if ($old_avatar_id) {
            wp_delete_attachment($old_avatar_id, true);
            delete_user_meta($user_id, 'rsava_user_avatar');
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_id = media_handle_upload('rsava_user_avatar', 0);
        if (!is_wp_error($attachment_id)) {
            update_user_meta($user_id, 'rsava_user_avatar', $attachment_id);
        }
    }

    wp_safe_redirect($redirect_url);
    exit;
}

// --- Handle avatar delete (frontend) ---
add_action('admin_post_rsava_front_delete_avatar', 'rsava_front_handle_avatar_delete');
function rsava_front_handle_avatar_delete() {
    if ( ! is_user_logged_in() ) {
        wp_safe_redirect( home_url() );
        exit;
    }
    $nonce = isset($_POST['rsava_avatar_front_nonce']) ? sanitize_text_field(wp_unslash($_POST['rsava_avatar_front_nonce'])) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rsava_avatar_front_save' ) ) {
        wp_safe_redirect( add_query_arg('rsava_error', 'nonce', wp_get_referer()) );
        exit;
    }
    $user_id = get_current_user_id();
    $old_avatar_id = rsava_get_user_avatar_id($user_id);
    if ($old_avatar_id) {
        wp_delete_attachment($old_avatar_id, true);
        delete_user_meta($user_id, 'rsava_user_avatar');
    }
    $redirect_url = wp_get_referer();
    wp_safe_redirect($redirect_url);
    exit;
}

// --- Avatar upload form shortcode ---
add_shortcode('rsava_avatar_form', 'rsava_avatar_shortcode');
function rsava_avatar_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="rsava-server-error" style="color:red;">Only for logged-in users.</div>';
    }

    $user_id = get_current_user_id();
    $avatar_id = rsava_get_user_avatar_id($user_id);
    $avatar_url = rsava_get_user_avatar_url($user_id, 96);
    if (!$avatar_url) $avatar_url = get_avatar_url($user_id, ['size'=>96]);
    $error = '';
    if ( isset($_GET['rsava_error']) ) {
        $error = sanitize_text_field( wp_unslash($_GET['rsava_error']) );
    }
    $max_mb = (int)get_option('rsava_avatar_max_filesize_mb', 4);
    $max_bytes = $max_mb * 1024 * 1024;

    ob_start();
    ?>
    <div class="rsava-avatar-upload-form">
        <div style="margin-bottom:1px;">
    <?php
    if ($avatar_id && get_post_mime_type($avatar_id) && strpos(get_post_mime_type($avatar_id), 'image/') === 0) {
        echo wp_get_attachment_image(
            $avatar_id,
            [80, 80],
            false,
            [
                'id' => 'rsava-current-avatar',
                'class' => 'rsava-avatar-img',
                'style' => 'width:80px;height:80px;border-radius:50%;object-fit:cover;',
                'alt' => 'avatar'
            ]
        );
    } else {
        echo get_avatar(
            $user_id,
            80,
            '',
            'avatar',
            [
                'id'    => 'rsava-current-avatar',
                'class' => 'rsava-avatar-img',
                'style' => 'width:80px;height:80px;border-radius:50%;object-fit:cover;'
            ]
        );
    }
    ?>
</div>
        <input type="hidden" id="rsava-user-avatar-url" value="<?php echo esc_url($avatar_url); ?>">
        <div class="rsava-avatar-maxsize" style="color: #666; font-size: 13px; margin-bottom: 8px;">
            <?php esc_html_e('Max file size', 'real-simple-avatar'); ?>: <?php echo esc_html($max_mb); ?> MB
        </div>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="rsavaAvatarForm">
            <input type="file" name="rsava_user_avatar" accept=".png,.jpeg,.jpg,.heic" id="rsavaUserAvatarFile" data-maxsize="<?php echo esc_attr($max_bytes); ?>">
            <input type="hidden" name="action" value="rsava_front_upload_avatar">
            <?php wp_nonce_field('rsava_avatar_front_save', 'rsava_avatar_front_nonce'); ?>
            <button type="button" id="rsavaCustomFileBtn" class="rsava-addfile"><?php esc_html_e('Upload Avatar', 'real-simple-avatar'); ?></button>
            <span id="rsavaCustomFileLabel" class="rsava-file-label"></span>
            <input type="submit" value="<?php esc_attr_e('Save', 'real-simple-avatar'); ?>" class="rsava-save-button">
            <div id="rsava-avatar-error" style="color:red;margin-top:6px;"></div>
        </form>
        <?php if ($avatar_id): ?>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" onsubmit="return confirm('<?php esc_attr_e('Delete image?', 'real-simple-avatar'); ?>');" style="margin-top:10px;">
            <input type="hidden" name="action" value="rsava_front_delete_avatar">
            <?php wp_nonce_field('rsava_avatar_front_save', 'rsava_avatar_front_nonce'); ?>
            <input type="submit" value="<?php esc_attr_e('Delete Avatar', 'real-simple-avatar'); ?>" class="rsava-default-button">
        </form>
        <?php endif; ?>
        <?php if ($error === 'size'): ?>
            <div class="rsava-server-error" style="color:red;"><?php esc_html_e('File is larger than', 'real-simple-avatar'); ?> <?php echo esc_html($max_mb); ?> MB</div>
        <?php elseif ($error === 'nonce'): ?>
            <div class="rsava-server-error" style="color:red;"><?php esc_html_e('Security check failed (nonce). Please refresh the page and try again.', 'real-simple-avatar'); ?></div>
        <?php elseif ($error === 'type'): ?>
            <div class="rsava-server-error" style="color:red;"><?php esc_html_e('Only images can be uploaded.', 'real-simple-avatar'); ?></div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
