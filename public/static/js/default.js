(function($) {

    $(document).on('click', 'a[delete="delete"]', function() {
        if (confirm('Are you sure you want to delete this?') === false) {
            return false;
        }
    });

    $(function() {
        $('.file-input').bootstrapFileInput();
    });

})(jQuery);
