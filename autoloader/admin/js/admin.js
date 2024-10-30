(function( $ ) {
    var hash = window.location.hash.substr(1);
    if (hash && hash != '' && $('[data-scrolltarget=' + hash + ']').length > 0) {
        $('html, body').animate({
            backgroundColor: 'red',
            scrollTop: parseInt($('[data-scrolltarget=' + hash + ']').offset().top) - parseInt(100)
        }, 1000);

        $('[data-scrolltarget=' + hash + ']').closest('.widget').css("border", "2px solid #ff7f7f");
    }

    
    $(".ifg-shortcode span").on('mouseup', function() {
        var sel, range;
        var el = $(this)[0];
        if (window.getSelection && document.createRange) { //Browser compatibility
        sel = window.getSelection();
        if(sel.toString() == ''){ //no text selection
            window.setTimeout(function(){
                range = document.createRange(); //range object
                range.selectNodeContents(el); //sets Range
                sel.removeAllRanges(); //remove all ranges from selection
                sel.addRange(range);//add Range to a Selection.
            },1);
        }
        }else if (document.selection) { //older ie
            sel = document.selection.createRange();
            if(sel.text == ''){ //no text selection
                range = document.body.createTextRange();//Creates TextRange object
                range.moveToElementText(el);//sets Range
                range.select(); //make selection.
            }
        }
    });


    //init colorpicker
    $('.ifg-colorpicker').wpColorPicker({
        change: function(event, ui){
            var _this = $(this);
            $(document).trigger("ifg-setColorPickerVal", {
                el: _this,
                val: _this.val()
            });
        }
    });


	//init tablist
	$(document).on('click', '.tabs-menu li', function(event) {
        event.preventDefault();

        var _this = $(this),
        	tabContent = _this.closest('.tabs-container').find('> .tab > .tab-content'),
        	tab = $('#'+_this.attr('data-target'));

        _this.addClass("current");
        _this.siblings().removeClass("current");
        tabContent.not(tab).hide();
        tab.fadeIn();
    });

})( jQuery );
