<?php
/**
 * Plugin Name: Real Simple Avatar
 * Plugin URI: https://github.com/avs-alexandra/real-simple-avatar
 * Description: Upload avatar for users and set default avatar via media library.
 * Version: 1.0.0
 * Author: avsalexandra
 * Author URI: https://github.com/avs-alexandra
 * Text Domain: real-simple-avatar
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// --- Media scripts for admin ---
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

// --- Media scripts for frontend (shortcode) ---
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'rsava-avatar-media',
            plugins_url('assets/js/rsava-avatar-media.js', __FILE__),
            ['jquery'],
            '1.7.1',
            true
        );
    }
});

// --- User avatar field ---
add_action( 'show_user_profile', 'rsava_show_avatar_field' );
add_action( 'edit_user_profile', 'rsava_show_avatar_field' );
function rsava_show_avatar_field( $user ) {
    $avatar_id = intval( get_user_meta( $user->ID, 'rsava_user_avatar', true ) );
    $avatar_url = ($avatar_id && get_post_mime_type($avatar_id) && strpos(get_post_mime_type($avatar_id), 'image/') === 0)
        ? wp_get_attachment_url( $avatar_id ) : '';
    ?>
    <table class="form-table">
        <tr>
            <th><label><?php esc_html_e( 'Загрузить аватар', 'real-simple-avatar' ); ?></label></th>
            <td>
                <div id="rsava-user-avatar-preview" style="margin-bottom:10px;">
                    <?php
                    if ($avatar_url) {
                        echo wp_get_attachment_image($avatar_id, [80, 80], false, [
                            'style' => 'max-width:80px;display:block;margin-bottom:10px;',
                            'alt' => '',
                        ]);
                    }
                    ?>
                </div>
                <input type="hidden" name="rsava_user_avatar" id="rsava_user_avatar" value="<?php echo esc_attr($avatar_id); ?>" />
                <button type="button" class="button" id="rsava_select_avatar"><?php esc_html_e('Выберите файл', 'real-simple-avatar'); ?></button>
                <?php if ($avatar_url): ?>
                    <br><label><input type="checkbox" name="rsava_user_avatar_delete" value="1"> <?php esc_html_e( 'Удалить аватар', 'real-simple-avatar' ); ?></label>
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
        $old_avatar_id = intval( get_user_meta( $user_id, 'rsava_user_avatar', true ) );
        if ( $old_avatar_id ) {
            wp_delete_attachment( $old_avatar_id, true );
            delete_user_meta( $user_id, 'rsava_user_avatar' );
        }
        return;
    }
    if ( isset( $_POST['rsava_user_avatar'] ) ) {
        $new_avatar_id = intval( $_POST['rsava_user_avatar'] );
        $old_avatar_id = intval( get_user_meta( $user_id, 'rsava_user_avatar', true ) );
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

// --- Получить URL кастомного дефолтного аватара ---
function rsava_get_default_avatar_url() {
    $avatar_id = (int)get_option('rsava_default_avatar_id', 0);
    if ($avatar_id) {
        $avatar_url = wp_get_attachment_url($avatar_id);
        if ($avatar_url) return $avatar_url;
    }
    return '';
}

// --- Добавляем кастомный дефолтный аватар в radio-кнопки через URL! ---
add_filter('avatar_defaults', function($avatar_defaults) {
    $avatar_url = rsava_get_default_avatar_url();
    if ($avatar_url) {
        $avatar_defaults[$avatar_url] = __('Пользовательский аватар по умолчанию', 'real-simple-avatar');
    }
    return $avatar_defaults;
});

// --- Индивидуальный аватар пользователя всегда в приоритете ---
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
        $post_id = get_user_meta($user->ID, 'rsava_user_avatar', true);
        if ($post_id && get_post_mime_type($post_id) && strpos(get_post_mime_type($post_id), 'image/') === 0) {
            $size = isset($args['size']) ? $args['size'] : 96;
            $img_url = wp_get_attachment_image_url($post_id, [$size, $size]);
            if ($img_url) return $img_url;
        }
    }
    return $url;
}, 10, 3);

// --- Поле загрузки дефолтного аватара (Настройки → Обсуждение) ---
add_action('admin_init', function() {
    register_setting('discussion', 'rsava_default_avatar_id', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 0
    ]);
    add_settings_field(
        'rsava_default_avatar',
        __('Аватар по умолчанию', 'real-simple-avatar'),
        'rsava_default_avatar_field_html',
        'discussion'
    );

    // Новое поле: максимальный размер файла аватара для шорткода (в МБ)
    register_setting('discussion', 'rsava_avatar_max_filesize_mb', [
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 4
    ]);
    add_settings_field(
        'rsava_avatar_max_filesize_mb',
        __('Максимальный размер файла аватара (МБ, только для шорткода)', 'real-simple-avatar'),
        function() {
            $value = (int)get_option('rsava_avatar_max_filesize_mb', 4);
            echo '<input type="number" min="1" max="20" step="1" name="rsava_avatar_max_filesize_mb" value="' . esc_attr($value) . '" style="width:70px;"> ';
            echo '<span class="description">Максимальный размер загружаемого файла через шорткод. По умолчанию — 4 МБ.</span>';
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
            echo '<em style="color:#c00;">Нет выбранного изображения по умолчанию.</em>';
        }
        ?>
    </div>
    <input type="hidden" id="rsava_default_avatar_id" name="rsava_default_avatar_id" value="<?php echo esc_attr($avatar_id); ?>">
    <button type="button" class="button" id="rsava_select_default_avatar"><?php esc_html_e('Загрузить', 'real-simple-avatar'); ?></button>
    <?php
}

// === Фронтовая часть: обработка загрузки/удаления аватара и шорткод ===

// --- Обработка загрузки аватара на фронте ---
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
        $old_avatar_id = get_user_meta($user_id, 'rsava_user_avatar', true);
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

// --- Обработка удаления аватара на фронте ---
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
    $old_avatar_id = get_user_meta($user_id, 'rsava_user_avatar', true);
    if ($old_avatar_id) {
        wp_delete_attachment($old_avatar_id, true);
        delete_user_meta($user_id, 'rsava_user_avatar');
    }
    $redirect_url = wp_get_referer();
    wp_safe_redirect($redirect_url);
    exit;
}

// --- Шорткод формы загрузки аватара ---
add_shortcode('rsava_avatar_form', 'rsava_avatar_shortcode');
function rsava_avatar_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<div class="rsava-server-error" style="color:red;">Только для авторизованных пользователей.</div>';
    }

    $user_id = get_current_user_id();
    $avatar_id = get_user_meta($user_id, 'rsava_user_avatar', true);
    $avatar_url = $avatar_id ? wp_get_attachment_url($avatar_id) : get_avatar_url($user_id, ['size'=>96]);
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
            if ($avatar_id) {
                echo wp_get_attachment_image($avatar_id, [80, 80], false, [
                    'id' => 'rsava-current-avatar',
                    'class' => 'rsava-avatar-img',
                    'style' => 'width:80px;height:80px;border-radius:50%;object-fit:cover;',
                    'alt' => 'avatar'
                ]);
            } else {
                // fallback to Gravatar/WordPress avatar
                echo get_avatar(
                    $user_id,
                    80,
                    '',
                    'avatar',
                    [
                        'id'    => 'rsava-current-avatar',
                        'class' => 'rsava-avatar-img',
                        'style' => 'width:80px;height:80px;border-radius:50%;object-fit:cover;',
                    ]
                );
            }
            ?>
        </div>
        <div class="rsava-avatar-maxsize" style="color: #666; font-size: 13px; margin-bottom: 8px;">
            Максимальный размер файла: <?php echo esc_html($max_mb); ?> МБ
        </div>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" id="rsavaAvatarForm">
            <input type="file" name="rsava_user_avatar" accept=".png,.jpeg,.jpg,.heic" id="rsavaUserAvatarFile" data-maxsize="<?php echo esc_attr($max_bytes); ?>">
            <input type="hidden" name="action" value="rsava_front_upload_avatar">
            <?php wp_nonce_field('rsava_avatar_front_save', 'rsava_avatar_front_nonce'); ?>
            <input type="submit" value="Сохранить" class="rsava-save-button">
            <div id="rsava-avatar-error" style="color:red;margin-top:6px;"></div>
        </form>
        <?php if ($avatar_id): ?>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" onsubmit="return confirm('Удалить изображение?');" style="margin-top:10px;">
            <input type="hidden" name="action" value="rsava_front_delete_avatar">
            <?php wp_nonce_field('rsava_avatar_front_save', 'rsava_avatar_front_nonce'); ?>
            <input type="submit" value="Удалить аватар" class="rsava-default-button">
        </form>
        <?php endif; ?>
        <?php if ($error === 'size'): ?>
           <div class="rsava-server-error" style="color:red;">Файл больше <?php echo esc_html($max_mb); ?> МБ</div>
        <?php elseif ($error === 'nonce'): ?>
           <div class="rsava-server-error" style="color:red;">Ошибка проверки безопасности (nonce). Обновите страницу и попробуйте снова.</div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
