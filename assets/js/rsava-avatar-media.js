jQuery(document).ready(function($){
    // --- Admin: choose default avatar via media library ---
    $('#rsava_select_default_avatar').on('click', function(e){
        e.preventDefault();
        let frame = wp.media({
            title: rsavaL10n.chooseDefaultAvatar,
            button: { text: rsavaL10n.use },
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

    // --- Admin: choose user avatar via media library ---
    $('#rsava_select_avatar').on('click', function(e){
        e.preventDefault();
        let frame = wp.media({
            title: rsavaL10n.chooseUserAvatar,
            button: { text: rsavaL10n.use },
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

    // --- Move field after avatar rating (optional) ---
    var $row = $('#rsava_select_default_avatar').closest('tr');
    var $avatarRatingRow = $('#avatar_rating').closest('tr');
    if($row.length && $avatarRatingRow.length) {
        $row.insertAfter($avatarRatingRow);
    }

    // === Frontend avatar form ===
    var $upload = $('#rsavaUserAvatarFile');
    var $avatarImg = $('.rsava-avatar-img');
    var $form = $('#rsavaAvatarForm');
    var $errorDiv = $('#rsava-avatar-error');
    var $defaultInput = $('#rsava-user-avatar-url');
    var originalAvatarSrc = $defaultInput.length && $defaultInput.val() ? $defaultInput.val() : ($avatarImg.length ? $avatarImg.attr('src') : '');
    var maxSize = $upload.data('maxsize') ? parseInt($upload.data('maxsize'), 10) : 4*1024*1024;

    // Set current avatar on page load (e.g. after reload)
    if($avatarImg.length && originalAvatarSrc) {
        $avatarImg.attr('src', originalAvatarSrc);
        $avatarImg.attr('data-default', originalAvatarSrc);
    }

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
                // Size check
                if(file.size > maxSize) {
                    if($errorDiv.length) $errorDiv.text(rsavaL10n.fileTooLarge + " " + (maxSize/1024/1024).toFixed(1) + " " + rsavaL10n.mb);
                    this.value = "";
                    if($avatarImg.length) $avatarImg.attr('src', originalAvatarSrc); // revert original
                    return;
                }
                // Type check
                if(!/^image\//.test(file.type)) {
                    if($errorDiv.length) $errorDiv.text(rsavaL10n.onlyImage);
                    this.value = "";
                    if($avatarImg.length) $avatarImg.attr('src', originalAvatarSrc);
                    return;
                }
                // Show thumbnail in main avatar
                var reader = new FileReader();
                reader.onload = function(e) {
                    if($avatarImg.length) {
                        $avatarImg.attr('src', e.target.result);
                        $avatarImg.removeAttr('srcset').removeAttr('sizes');
                    }
                };
                reader.readAsDataURL(file);
            } else {
                if($avatarImg.length) $avatarImg.attr('src', originalAvatarSrc); // if file is reset
            }
        });
    }

    if($form.length && $upload.length) {
        $form.on('submit', function(e){
            if($upload[0].files.length > 0) {
                var file = $upload[0].files[0];
                if(file.size > maxSize) {
                    if($errorDiv.length) $errorDiv.text(rsavaL10n.fileTooLarge + " " + (maxSize/1024/1024).toFixed(1) + " " + rsavaL10n.mb);
                    e.preventDefault();
                    return false;
                }
                if(!/^image\//.test(file.type)) {
                    if($errorDiv.length) $errorDiv.text(rsavaL10n.onlyImage);
                    e.preventDefault();
                    return false;
                }
            }
        });
    }

    // Remove rsava_error param from URL so error does not appear after refresh
    if(window.location.search.indexOf('rsava_error=') !== -1) {
        var url = new URL(window.location.href);
        url.searchParams.delete('rsava_error');
        window.history.replaceState({}, document.title, url.pathname + url.search);
    }

    // =========================
    // Custom "Upload Avatar" button and selected file name
    var $btn = $('#rsavaCustomFileBtn');
    if ($upload.length && $btn.length) {
        $btn.on('click', function(e){
            e.preventDefault();
            $upload.click();
        });
    }
    $upload.on('change', function(){
        var fileName = this.files.length ? this.files[0].name : '';
        $('#rsavaCustomFileLabel').text(fileName ? rsavaL10n.selected + ' ' + fileName : '');
    });
});
