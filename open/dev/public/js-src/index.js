	var pause = false;
	var fader = function(){
		var crt = $('#fade_wrap a:visible');
		var idx = crt.index();
		var num = $('#fade_wrap a').length - 1;
		var nxt = idx + 1;
		if( idx == num ) {
			nxt = 0;
		}
		crt.animate({opacity:0.3}, 300, '', function(){
			crt.hide();
			$('#fade_wrap a').eq(nxt).css({opacity: 0.3, display:"block"}).animate({opacity:1}, 300);
		});
		$('#fade_bar li').eq(nxt).addClass('selected').siblings().removeClass('selected');
	};
	setInterval(function(){
		if( pause ) return false;
		fader();
	}, 4000);
	$('#fade_bar li').mouseover(function(){
		pause = true;
		var _t = $(this);
		if( _t.hasClass('selected') ) return false;
		$('#fade_wrap a').stop();
		var crt = $('#fade_wrap a:visible');
		var nxt = _t.index();
		crt.animate({opacity:0.3}, 300, '', function(){
			crt.hide();
			$('#fade_wrap a').eq(nxt).css({opacity: 0.3, display:"block"}).animate({opacity:1}, 300);
		});
		_t.addClass('selected').siblings().removeClass('selected');
	}).mouseout(function(){
		pause = false;
	});
