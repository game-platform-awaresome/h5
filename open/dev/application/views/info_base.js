$('#top_nav a:eq(1)').addClass('selected');
$(function(){
	$(".menu .choose").parents('.lists:not(:first)').addClass('selected')
        .find('.i_open').addClass("i_down").end()
        .find('nav').show();

    $(".menu div h3").click(function() {
        var _t = $(this);
        if (!_t.hasClass('selected')) {
        	_t.parent().addClass('selected');
        	_t.parent().siblings().removeClass('selected')
                .find('nav').slideUp();
        	_t.next('nav').slideDown();
        }
    });
});