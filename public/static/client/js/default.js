(function($) {

    // Extend
    $.fn.activeStyle = function(name, param, callback) {
        $(this).each(function() {
            var current_value   = $(this).data(name);
            var parameter_name  = $(this).data(param);
            var parameter_value = $.parseParams(window.location.href.split('?')[1])[parameter_name];

            return callback.call(this, current_value, parameter_value);
        });
    };

    // Confirm delete, trade, done
    $(document).on('click', 'a[delete="delete"]', function() {
        if (confirm('Are you sure you want to delete this?') === false) {
            return false;
        }
    });

    $(document).on('click', 'a[trade="trade"]', function() {
        if (confirm('Are you sure trade for this item?') === false) {
            return false;
        }
    });

    $(document).on('click', 'a[done="done"]', function() {
        if (confirm('Are you sure set it to done?') === false) {
            return false;
        }
    });

    // Preivew image in item detail
    $(document).on('click', 'a.thumbnail-blur', function() {
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

        $('a[data-btn]').activeStyle('btn', 'btn-param', function(current_value, parameter_value) {
            if (parameter_value === undefined || current_value == parameter_value) {
                $(this).addClass('active');
                return false;
            }
        });

        $('a[data-tab]').activeStyle('tab', 'tab-param', function(current_value, parameter_value) {
            if (parameter_value === undefined || current_value == parameter_value) {
                $(this).parent().addClass('active');
                return false;
            }
        });

        $('a[data-label]').activeStyle('label', 'label-param', function(current_value, parameter_value) {
            if (parameter_value === undefined || current_value == parameter_value) {
                $(this).addClass('label-info');
                return false;
            }
        });

        $('a[data-badge]').each(function() {
            var self = this;
            $.getJSON($(this).data('badge'), function(data) {
                if (data['number'] !== undefined && data['number'] != 0) {
                    $(self).append('&nbsp;<span class="badge">' + data['number'] + '</span>');
                }
            });
        });
    });

})(jQuery);
