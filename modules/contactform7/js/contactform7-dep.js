(function($){

    var selectElem = $('#infusionsoft-field'),
        form = selectElem.closest('form'),
        tagGenerator = form.find('.insert-box > input:not(.hidden)');

    form.change(function() {

        var tag = tagGenerator.val(),
            selectedField = selectElem.val();

        // Update the generated tag
        if ( selectedField != '0' ) {

            // Form Tag
            if ( tag.match(/(\s+infusionsoft[a-z-]+)/) ) {
                tagGenerator.val(tag.replace(/(\s+infusionsoft[a-z-]+)/, selectedField));
            } else {
                tagGenerator.val(tag.replace(/(^\[\w+\*?)/, '$1 infusionsoft-' + selectedField));
            }
            // Mail tag
            form.find('#tag-generator-panel-infusionsoft-mailtag').val('[infusionsoft-' + selectedField + ']');
            form.find('span.mail-tag').text('[infusionsoft-' + selectedField + ']');

        } else {

            tagGenerator.val(tag.replace(/(\s+infusionsoft[a-z-]+)/, ''));
            form.find('#tag-generator-panel-infusionsoft-mailtag').val('[]');
            form.find('span.mail-tag').text('[]');
        }

    });




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
                    action: 'ifg_contactform7_search_data',
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


})(jQuery);