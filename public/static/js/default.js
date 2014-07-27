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

        $('a[data-btn]').each(function() {
            var btn_status = $(this).data('btn');
            var btn_param  = $(this).data('btn-param');
            var url_status = $.parseParams(window.location.href.split('?')[1])[btn_param];

            // Default active first btn when btn not specified
            if (url_status === undefined) {
                $(this).addClass('active');
                return false;
            }

            if (btn_status == url_status) {
                $(this).addClass('active');
                return true;
            }
        });

        $('a[data-tab]').each(function() {
            var tab_name = $(this).data('tab');
            var url_name = $.parseParams(window.location.href.split('?')[1]).tab;

            // Default active first tab when tab not specified
            if (url_name === undefined) {
                $(this).parent().addClass('active');
                return false;
            }

            if (tab_name == url_name) {
                $(this).parent().addClass('active');
                return true;
            }
        });
    });

})(jQuery);
