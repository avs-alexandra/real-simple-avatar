jQuery(document).ready(function($){
    // --- Админка: выбор аватара по умолчанию через медиатеку ---
    $('#rsava_select_default_avatar').on('click', function(e){
        e.preventDefault();
        let frame = wp.media({
            title: 'Выберите или загрузите аватар по умолчанию',
            button: { text: 'Использовать' },
            library: { type: 'image' },
            multiple: false
        });
        frame.on('select', function(){
            let attachment = frame.state().get('selection').first().toJSON();
            $('#rsava_default_avatar_id').val(attachment.id);
            $('#rsava-default-avatar-preview').html('<img src="'+attachment.url+'" style="max-width:80px;display:block;margin-bottom:10px;">');
        });
        frame.open();
    });

    // --- Админка: индивидуальный аватар пользователя ---
    $('#rsava_select_avatar').on('click', function(e){
        e.preventDefault();
        let frame = wp.media({
            title: 'Выберите или загрузите аватар пользователя',
            button: { text: 'Использовать' },
            library: { type: 'image' },
            multiple: false
        });
        frame.on('select', function(){
            let attachment = frame.state().get('selection').first().toJSON();
            $('#rsava_user_avatar').val(attachment.id);
            $('#rsava-user-avatar-preview').html('<img src="'+attachment.url+'" style="max-width:80px;display:block;margin-bottom:10px;">');
        });
        frame.open();
    });

    // --- Переместить поле вниз после рейтинга аватара (опционально) ---
    var $row = $('#rsava_select_default_avatar').closest('tr');
    var $avatarRatingRow = $('#avatar_rating').closest('tr');
    if($row.length && $avatarRatingRow.length) {
        $row.insertAfter($avatarRatingRow);
    }

    // === Фронтовая форма аватара ===
    // Ожидается структура: 
    // <input type="file" id="rsavaUserAvatarFile" data-maxsize="...">
    // <img id="rsava-current-avatar" src="..." ...>
    // <div id="rsava-avatar-error"></div>
    // <form id="rsavaAvatarForm">...</form>
    var $upload = $('#rsavaUserAvatarFile');
    var $avatarImg = $('#rsava-current-avatar');
    var $form = $('#rsavaAvatarForm');
    var $errorDiv = $('#rsava-avatar-error');
    var originalAvatarSrc = $avatarImg.length ? $avatarImg.attr('src') : '';
    var maxSize = $upload.data('maxsize') ? parseInt($upload.data('maxsize'), 10) : 4*1024*1024;

    function clearAvatarError() {
        if($errorDiv.length) $errorDiv.text('');
        $('.rsava-server-error').hide();
    }

    if($upload.length) {
        $upload.on('input', clearAvatarError);
        $upload.on('focus', clearAvatarError);
        $upload.on('change', function(){
            clearAvatarError();

            if(this.files.length && this.files[0]) {
                var file = this.files[0];
                // Проверка размера
                if(file.size > maxSize) {
                    if($errorDiv.length) $errorDiv.text("Файл больше " + (maxSize/1024/1024).toFixed(1) + " МБ");
                    this.value = "";
                    if($avatarImg.length) $avatarImg.attr('src', originalAvatarSrc); // вернуть исходный
                    return;
                }
                // Показать миниатюру прямо в основном аватаре
                var reader = new FileReader();
                reader.onload = function(e) {
                    if($avatarImg.length)
                        $avatarImg.attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            } else {
                if($avatarImg.length) $avatarImg.attr('src', originalAvatarSrc); // если файл сбросили
            }
        });
    }

    if($form.length && $upload.length) {
        $form.on('submit', function(e){
            if($upload[0].files.length > 0) {
                var file = $upload[0].files[0];
                if(file.size > maxSize) {
                    if($errorDiv.length) $errorDiv.text("Файл больше " + (maxSize/1024/1024).toFixed(1) + " МБ");
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    // Удалить параметр rsava_error из адресной строки, чтобы ошибка не появлялась после обновления
    if(window.location.search.indexOf('rsava_error=') !== -1) {
        var url = new URL(window.location.href);
        url.searchParams.delete('rsava_error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }
});
