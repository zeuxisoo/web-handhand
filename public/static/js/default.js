(function($) {

    // Confirm delete
    $(document).on('click', 'a[delete="delete"]', function() {
        if (confirm('Are you sure you want to delete this?') === false) {
            return false;
        }
    });

    // Preivew image in item detail
    $(document).on('mouseenter', 'a.thumbnail-blur', function() {
        var old_source = $('img.preview').attr('src');
        var new_source = $(this).find('img').attr('src').replace('200x200', '525x525');

        // Preivew selected image
        $('img.preview').fadeOut('slow', function() {
            $(this).attr('src', new_source).fadeIn('slow');
        });

        // Toggle blur effect when hover
        $(this).removeClass('thumbnail-blur');
        $(this).on('mouseleave', function() {
            $(this).addClass('thumbnail-blur');
        });
    });

    $(function() {
        $('.file-input').bootstrapFileInput();

        $('#user-item-manage a[data-status]').each(function() {
            var btn_status = $(this).data('status');
            var url_status = $.parseParams(window.location.href.split('?')[1]).status;

            if (btn_status == url_status) {
                $(this).addClass('active');
            }
        });
    });

})(jQuery);
