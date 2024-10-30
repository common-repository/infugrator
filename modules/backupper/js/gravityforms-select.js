(function($){

	/**
     * Searching remote/local data
     */
    $('select[data-remote]').select2({
        placeholder: 'Search',
        ajax: {
            url: ifg.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            delay: 250,
            minimumInputLength: 1,
            data: function (params) {
                return {
                    action: 'ifg_gravityforms_search_data',
                    q: params.term,
                    page: params.page,
                    remote: JSON.parse($(this).attr('data-remote')),
                    security: ifg.ajaxNonce
                };
            },
            processResults: function (data, params) {
                // parse the results into the format expected by Select2
                // since we are using custom formatting functions we do not need to
                // alter the remote JSON data, except to indicate that infinite
                // scrolling can be used
                params.page = params.page || 1;

                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
        },
        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
        templateResult: function(res){
            if (res.loading) return res.text;

            var markup = res.label;

            return markup;
        },
        templateSelection: function(res) {
            return res.label || res.text;
        }

    });

})(jQuery)