function preloadImages(images)
{
	var img = new Image();
	for(var i in images)
	{
		img.src = images[i];
	}
}

function showAjaxLoader(cnf)
{
	var cfg = {
		cover: 0
	};
	if( typeof cnf == 'object' ) {
		for(var i in cnf)
		{
			cfg[i] = cnf[i];
		}
	}
	var id = 'ajax_loading';
	var _c = $('#'+id);
	if( _c.length > 0 ) {
		_c.show().children('img').show();
		cfg.cover == 0 ? _c.children('div').hide() : _c.children('div').show();
		return ;
	}
	
	var img = '<div id="'+ id +'" class="ajax_loading">';
	img += '<div class="ajax_cover"></div>';
	img += '<img src="/admin/images/loading.gif" />';
	img += '<p style="display:none;"></p>';
	img += '</div>';
	
	_c = $(img);
	cfg.cover == 0 ? _c.children('div').hide() : _c.children('div').show();
	$(document.body).append(_c);
}
function hideAjaxLoader()
{
	$('#ajax_loading').hide();
}
function hideLoaderImg()
{
	$('#ajax_loading img').hide();
}
function setLoaderMsg(msg)
{
	if( typeof msg == 'undefined' || msg == '' ) {
		$('#ajax_loading p').html('').hide();
	} else {
		$('#ajax_loading p').html(msg).show();
	}
}
function getLoaderMsg()
{
	return $('#ajax_loading p').html();
}

$(document.body).ajaxStart(showAjaxLoader);
$(document.body).ajaxStop(hideAjaxLoader);

function activeAjaxDelete(fn, cls, succ)
{
	var _pp;
	if( typeof cls == 'undefined' ) {
		cls = 'op_delete';
	}
	if( typeof succ == 'undefined' ) {
		succ = '1';
	}
	$('a.'+cls).click(function(){
		if( ! confirm('删除操作不可挽回，确定要执行吗？') ) {
			return false;
		}
		
		var _t = $(this);
		_pp = _t.parent().parent();
		$.get(_t.attr('href'), 'ajax=1', function(msg){
			if( typeof fn != 'undefined' ) {
				fn(msg, _t);
			} else if( msg == succ ) {
				var pid = _pp.find('input').attr('parent_id');
				if( pid === '0' ) {
					location.reload();
				} else {
					_pp.slideUp();
					_pp.nextAll().toggleClass('alt-row');
					_pp.remove();
				}
			} else {
				alert(msg);
			}
		});
		return false;
	});
	$('a.delete-all').click(function(){
		var ids = '';
		var cmm = '';
		$('.check-all').parent().parent().parent().parent().find("input[type='checkbox']:checked").each(function(){
			ids += cmm+this.value;
			cmm = ',';
		});
		$.get($(this).attr('href'), 'ids='+ids+'&ajax=1', function(msg){
			if( msg == succ ) {
				location.reload();
			} else {
				alert(msg);
			}
		});
		return false;
	});
}

function Timer(func, interval) {
	this.timer = null;
	this.dida = func;
	this.beat = interval;
	var _t = this;
	
	this.setDida = function(func){
		_t.dida = func;
	};
	this.setBeat = function(interval){
		_t.beat = interval;
	};
	this.start = function(){
		_t.timer = setInterval(_t.dida, _t.beat);
	};
	this.stop = function(){
		clearInterval(_t.timer);
		_t.timer = null;
	};
}

$(function(){
	$('a.lightbox').magnificPopup({
        type: 'image',
        closeOnContentClick: true,
        mainClass: 'mfp-img-mobile',
        image: {
            verticalFit: true
        }
    });
	
	$('#search_form input[type=button]').click(function(){
		var ok = false;
		$('#search_form select,#search_form input[type=text]').each(function(){
			var v = $.trim($(this).val());
			if( v != '' ) {
				ok = true;
			}
		});
		if( ! ok ) {
			alert('请选择或输入搜索条件！');
			return false;
		}
		$('#search_form').submit();
	});
	
	var fn = typeof window.ajax_delete_callback == 'function' ? window.ajax_del_func_cb : window.undefined;
	activeAjaxDelete(fn);
});
