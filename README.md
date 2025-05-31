# real-simple-avatar
## Real Simple Avatar

## <a href="https://wordpress.org/plugins/real-simple-avatar/">Скачать этот плагин на Wordpress</a>

---

Real Simple Avatar — простой лёгкий плагин для аватарок, который ты искал! 
<br>Главное, когда пользователь загружает свою аватарку, то его прошлая аватарка удаляется из медиатеки Wordpress

---

## Основные возможности

- Шорткод [rsava_avatar_form] загрузки аватара для пользователя.
- Изменить аватар пользователя в админ-панели.
- Установить аватар по умолчанию (Настройки → Обсуждение).
- Установить максимальный размер изображения для аватаров (Настройки → Обсуждение).
- Совместим с WooCommerce, Elementor и большинством основных плагинов.
- Совместим с Multisite.
- Если установлен Gravatar, то покажет его.

---
## Скриншоты

<img width="560" alt="screenshot-1" src="https://github.com/user-attachments/assets/5e1ad00f-8a4c-4f04-95b1-89aa20062367" />
<img width="560" alt="screenshot-2" src="https://github.com/user-attachments/assets/468576df-79c5-4bc3-9253-4cb0823a4d4b" />
<img width="560" alt="screenshot-3" src="https://github.com/user-attachments/assets/56a78ab7-1740-447a-aaa1-edf703104131" />

---

## FAQ

**Почему аватарки отображаются как битые ссылки?**
<br>- В настройках сайта нужно уставить Адрес сайта (URL) https, а не http

**Как изменить внешний вид шорткода [rsava_avatar_form]**
<br>- Добавьте код на страницу (в виджет HTML):

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

**Работает с Multisite?**
<br>- Да. Только установите аватар по умолчанию на каждом сайте.

**Плагин нагрузит сайт? Увеличит вес сайта?**
<br>- Нет, плагин очень лёгкий, не влияет на скорость сайта. Ваш сайт не будет много весить из-за аватарок, так как при загрузке новой аватарки, старая удаляется. А также Вы установите макс. размер изображения для аватарок.
