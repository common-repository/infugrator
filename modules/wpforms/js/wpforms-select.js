(function($){

    $(document).on('click', '#wpforms-builder', function(){
        $('#ifg-wpforms-save').attr('id', 'wpforms-save');
    });

    $(document).on('click', '[data-section="infusionsoft"]', function(e){
        e.stopPropagation();
        $('#wpforms-save').attr('id', 'ifg-wpforms-save');
    });

    $(document).on('click', '[data-panel="settings"]', function(e){
        e.stopPropagation();
        if($('[data-section="infusionsoft"]').hasClass('active')){
            $('#wpforms-save').attr('id', 'ifg-wpforms-save');
        }else{
            $('#ifg-wpforms-save').attr('id', 'wpforms-save');
        }
    });



    $(document).on('click', '#ifg-wpforms-save', function(e){
        e.preventDefault();

        var $saveBtn = $('#ifg-wpforms-save'),
            $icon    = $saveBtn.find('i'),
            $label   = $saveBtn.find('span'),
            text     = $label.text();

        $.ajax({
            url: ifg.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ifg_wpforms_save_mapped_fields',
                data: $('#wpforms-builder-form').serialize(),
                id: $('#wpforms-builder-form').data('id'),
                security: ifg.ajaxNonce
            },
            beforeSend: function(){
                $label.text(wpforms_builder.saving);
                $icon.toggleClass('fa-check fa-cog fa-spin');
            },
            success: function(res){
                console.log(res);
            },
            complete: function(){
                $label.text(text);
                $icon.toggleClass('fa-check fa-cog fa-spin');
            }
        });

        // if (typeof tinyMCE !== 'undefined') {
        //     tinyMCE.triggerSave();
        // }

        // $label.text(wpforms_builder.saving);
        // $icon.toggleClass('fa-check fa-cog fa-spin');

        // var data = {
        //     action: 'wpforms_save_form',
        //     data  : JSON.stringify($('#wpforms-builder-form').serializeArray()),
        //     id    : $('#wpforms-builder-form').data('id'),
        //     nonce : wpforms_builder.nonce
        // }
        // $.post(wpforms_builder.ajax_url, data, function(res) {
        //     if (res.success) {
        //         $label.text(text);
        //         $icon.toggleClass('fa-check fa-cog fa-spin');
        //         wpf.savedState = wpf.getFormState( '#wpforms-builder-form');
        //         $(document).trigger('wpformsSaved');
        //         if (true === redirect ) {
        //             window.location.href = wpforms_builder.exit_url;
        //         }
        //     } else {
        //         console.log(res);
        //     }
        // }).fail(function(xhr, textStatus, e) {
        //     console.log(xhr.responseText);
        // });
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
                    action: 'ifg_wpforms_search_data',
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