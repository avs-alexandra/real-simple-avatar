=== Real Simple Avatar ===
Contributors: avsalexandra
Tags: avatar, user avatar, profile picture, upload avatar, custom avatar
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Upload custom avatars for users from the frontend or user profile. Set a default avatar from the media library. Lightweight, simple, and compatible.

== Description ==

**Real Simple Avatar** - простой лёгкий плагин для аватарок, который ты искал! Главное, когда пользователь загружает свою аватарку, то его прошлая аватарка удаляется из медиатеки Wordpress. 

- Шорткод [rsava_avatar_form] загрузки аватара для пользователя.
- Изменить аватар пользователя в админ-панели.
- Установить аватар по умолчанию (Настройки → Обсуждение).
- Установить максимальный размер изображения для аватаров (Настройки → Обсуждение).
- Совместим с WooCommerce, Elementor и большинством основных плагинов.
- Совместим с Multisite.
- Если установлен Gravatar, то покажет его.

== Installation ==

1. Скачайте плагин.
2. Загрузите папку с плагином в директорию `/wp-content/plugins/`.
3. Активируйте плагин через меню "Плагины" в админке WordPress.

== Frequently Asked Questions ==

= Почему аватарки отображаются как битые ссылки? =
В настройках сайта нужно уставить Адрес сайта (URL) https, а не http

= Как изменить внешний вид шорткода [rsava_avatar_form] =
Добавьте код на страницу (в виджет HTML):

```html
<script>
jQuery(function($){
    var $input = $('#rsavaUserAvatarFile');
    if ($input.length && $('#rsavaCustomFileBtn').length === 0) {
        $input.after('<button type="button" id="rsavaCustomFileBtn" class="rsava-addfile">Загрузить аватар</button>');
    }
    $(document).on('click', '#rsavaCustomFileBtn', function(e){
        e.preventDefault();
        $input.click();
    });
    $input.on('change', function(){
        var fileName = this.files.length ? this.files[0].name : '';
        if ($('#rsavaCustomFileLabel').length === 0) {
            $input.after('<span id="rsavaCustomFileLabel" class="rsava-file-label"></span>');
        }
        $('#rsavaCustomFileLabel').text(fileName ? 'Выбран: ' + fileName : '');
    });
});
</script>

<style>
.rsava-avatar-upload-form {border: 3px dashed #FFB829;padding:20px;text-align:center;border-radius: 20px;}
#rsava-current-avatar{max-width: 90px;max-height: 90px;border:3px solid #FFB829;}
.rsava-avatar-maxsize{font-size: 13px;}
#rsavaUserAvatarFile {display:none;}
.rsava-addfile {padding: 10px 22px;background-color: #FFB829;color:#000;border:none;font-size:16px;font-weight:600;
cursor:pointer;border-radius:100px;display:inline-block;margin-top:10px;}
.rsava-addfile:hover{background-color:#ffc84b;}
.rsava-file-label{display:block;color:#888;font-size:14px;}
.rsava-save-button{padding:10px 22px;background-color:#FFB829;color:#000;
border:none;font-size:16px;font-weight:600;cursor:pointer;border-radius:100px;margin-top:10px;}
.rsava-save-button:hover{background-color:#ffc84b;}
.rsava-default-button {background: transparent !important;color:#a1a1a1 !important;text-decoration:underline;font-size:13px;border:none !important;padding:6px 10px;}
#rsava-avatar-error, .rsava-server-error{font-size: 14px;}
.rsava-avatar-img {width:90px!important;height:90px!important;border-radius: 50% !important;object-fit:cover;border:3px solid #FFB829!important;}
</style>
```

= Работает с Multisite? =
Да. Только установите аватар по умолчанию на каждом сайте.

= Плагин нагрузит сайт? Увеличит вес сайта? =
Нет, плагин очень лёгкий, не влияет на скорость сайта. Ваш сайт не будет много весить из-за аватарок, так как при загрузке новой аватарки, старая удаляется. А также Вы установите макс. размер изображения для аватарок.

= Нужен чекбокс согласия на обработку персональных данных по формой закрузки аватара? =
Нет. По закону 152-ФЗ согласие на обработку ПДн не требуется для каждого конкретного действия в профиле пользователя.
Достаточно наличия политики обработки персональных данных на сайте (ссылка на нее в футере или в меню), а согласие собирается при регистрации/оформлении заказа/первом вводе данных в виде обязательного чекбокса.

== Changelog ==
= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.0 =
* First public release. Safe to upgrade.

== Compatibility ==
Плагин протестирован и поддерживает PHP версии 7.4, 8.0, 8.1, 8.2, 8.3

== License ==
Этот плагин распространяется под лицензией GPLv2 или более поздней версии.
