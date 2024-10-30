(function($){

    var initStats = function(sync){

        return jQuery.ajax({
            url: ifg.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ifg_stats_data',
                sync: sync,
                security: ifg.ajaxNonce
            },
            success: function(RES){

                if(RES.hasOwnProperty('soldProducts')){
                    google.charts.load('current', {packages: ['corechart', 'bar']});
                    google.charts.setOnLoadCallback(function() {
                        var target = jQuery('#'+RES.soldProducts.target);

                        target.removeClass('loading').find('.fa-spinner').remove().promise().done(function(){

                            var data = google.visualization.arrayToDataTable(RES.soldProducts.data);

                            var view = new google.visualization.DataView(data);
                                view.setColumns([0, 1, {
                                    calc: "stringify",
                                    sourceColumn: 1,
                                    type: "string",
                                    role: "annotation"
                                }, 2]);

                            var options = {
                                vAxis: {format: ''},
                                chartArea: {width: '80%'},
                                height: 300,
                                bar: {groupWidth: "80%"},
                                legend: { position: "none" },
                            };
                            var chart = new google.visualization.ColumnChart(document.getElementById(RES.soldProducts.target));
                            chart.draw(view, options);

                        });
                    });
                }


                if(RES.hasOwnProperty('newContacts')){
                    google.charts.load('current', {packages: ['corechart', 'bar']});
                    google.charts.setOnLoadCallback(function() {

                        var target = jQuery('#'+RES.newContacts.target);

                        target.removeClass('loading').find('.fa-spinner').remove().promise().done(function(){

                            var data = google.visualization.arrayToDataTable(RES.newContacts.data);

                            var view = new google.visualization.DataView(data);
                                view.setColumns([0, 1, {
                                    calc: "stringify",
                                    sourceColumn: 1,
                                    type: "string",
                                    role: "annotation"
                                }, 2]);

                            var options = {
                                vAxis: {format: ''},
                                chartArea: {width: '80%'},
                                height: 300,
                                bar: {groupWidth: '80%'},
                                legend: { position: 'none' },
                            };

                            var chart = new google.visualization.ColumnChart(document.getElementById(RES.newContacts.target));
                            chart.draw(view, options);

                        });
                    });
                }


                if(RES.hasOwnProperty('topCountries')){
                    google.charts.load('current', {packages: ['corechart', 'bar']});
                    google.charts.setOnLoadCallback(function() {
                        var target = jQuery('#'+RES.topCountries.target);

                        target.removeClass('loading').find('.fa-spinner').remove().promise().done(function(){

                            var data = google.visualization.arrayToDataTable(RES.topCountries.data);

                            var view = new google.visualization.DataView(data);
                                view.setColumns([0, 1, {
                                    calc: "stringify",
                                    sourceColumn: 1,
                                    type: "string",
                                    role: "annotation"
                                }, 2]);

                            var options = {
                                vAxis: {format: 'decimal'},
                                chartArea: {width: '80%'},
                                height: 300,
                                bar: {groupWidth: "80%"},
                                legend: { position: "none" },
                            };
                            var chart = new google.visualization.ColumnChart(document.getElementById(RES.topCountries.target));
                            chart.draw(view, options);

                        });
                    });
                }


                //allow to add more charts
                PubSub.publish('add_data_charts', RES);
            }
        });
    }


    initStats(0);


    var processing = false;
    $('[data-syncstats]').on('click', function(){

        if(processing){
            return;
        }
        processing = true;

        var btn = $(this);

        btn.attr('disabled', true);

        $('.widget').each(function(){
            var _this = $(this);

            _this.find('.chart').addClass('loading').html('<i class="fa fa-spinner fa-spin"></i>');
        });
        initStats(1).promise().done(function(){
            processing = false;
            btn.attr('disabled', false);
        });
    });


})(jQuery);