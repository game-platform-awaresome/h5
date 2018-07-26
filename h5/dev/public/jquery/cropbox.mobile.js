/**
 * Created by ezgoing on 14/9/2014.
 * Added auto zoom and mobile suitable by dxj on 11/20/2015.
 */

"use strict";
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {
    var cropbox = function(options, el){
        var el = el || $(options.imageBox),
            obj =
            {
                state : {},
                ratio : 1,
                options : options,
                imageBox : el,
                thumbBox : el.find(options.thumbBox),
                spinner : el.find(options.spinner),
                image : new Image(),
                getDataURL: function ()
                {
                    var width = this.thumbBox.width(),
                        height = this.thumbBox.height(),
                        canvas = document.createElement("canvas"),
                        dim = el.css('background-position').split(' '),
                        size = el.css('background-size').split(' '),
                        dx = parseInt(dim[0]) - el.width()/2 + width/2,
                        dy = parseInt(dim[1]) - el.height()/2 + height/2,
                        dw = parseInt(size[0]),
                        dh = parseInt(size[1]),
                        sh = parseInt(this.image.height),
                        sw = parseInt(this.image.width);

                    canvas.width = width;
                    canvas.height = height;
                    var context = canvas.getContext("2d");
                    context.drawImage(this.image, 0, 0, sw, sh, dx, dy, dw, dh);
                    var imageData = canvas.toDataURL('image/png');
                    return imageData;
                },
                getBlob: function()
                {
                    var imageData = this.getDataURL();
                    var b64 = imageData.replace('data:image/png;base64,','');
                    var binary = atob(b64);
                    var array = [];
                    for (var i = 0; i < binary.length; i++) {
                        array.push(binary.charCodeAt(i));
                    }
                    return  new Blob([new Uint8Array(array)], {type: 'image/png'});
                },
                zoomIn: function ()
                {
                    this.ratio*=1.1;
                    setBackground();
                },
                zoomOut: function ()
                {
                    this.ratio*=0.9;
                    setBackground();
                }
            },
            setBackground = function()
            {
                var w =  parseInt(obj.image.width)*obj.ratio;
                var h =  parseInt(obj.image.height)*obj.ratio;

                var pw = (el.width() - w) / 2;
                var ph = (el.height() - h) / 2;

                el.css({
                    'background-image': 'url(' + obj.image.src + ')',
                    'background-size': w +'px ' + h + 'px',
                    'background-position': pw + 'px ' + ph + 'px',
                    'background-repeat': 'no-repeat'});
            },
            imgMouseDown = function(e)
            {
                e.stopImmediatePropagation();

                obj.state.dragable = true;
                obj.state.mouseX = e.clientX;
                obj.state.mouseY = e.clientY;
            },
            imgMouseMove = function(e)
            {
                e.stopImmediatePropagation();

                if (obj.state.dragable)
                {
                    var x = e.clientX - obj.state.mouseX;
                    var y = e.clientY - obj.state.mouseY;

                    var bg = el.css('background-position').split(' ');

                    var bgX = x + parseInt(bg[0]);
                    var bgY = y + parseInt(bg[1]);

                    el.css('background-position', bgX +'px ' + bgY + 'px');

                    obj.state.mouseX = e.clientX;
                    obj.state.mouseY = e.clientY;
                }
            },
            imgMouseUp = function(e)
            {
                e.stopImmediatePropagation();
                obj.state.dragable = false;
            },
            zoomImage = function(e)
            {
                e.originalEvent.wheelDelta > 0 || e.originalEvent.detail < 0 ? obj.ratio*=1.1 : obj.ratio*=0.9;
                setBackground();
            };

        var tmp_i = new Image(),
        	tmp_w = 0,
        	tmp_h = 0;
        tmp_i.onload = function(){
        	if( tmp_i.width > options.maxWidth ) {
            	tmp_w = options.maxWidth;
            	tmp_h = parseInt(tmp_i.height * (tmp_w / tmp_i.width));
            }
            if( tmp_i.height > options.maxHeight ) {
            	if( tmp_h == 0 ) {
            		tmp_h = options.maxHeight;
            		tmp_w = parseInt(tmp_i.width * (tmp_h / tmp_i.height));
            	} else if( tmp_h > options.maxHeight ) {
            		tmp_w = parseInt(tmp_w * (options.maxHeight / tmp_h));
            		tmp_h = options.maxHeight;
            	}
            }
            if( tmp_w > 0 ) {
            	var cvs = document.createElement("canvas");
            	cvs.width = tmp_w;
            	cvs.height = tmp_h;
            	var ctt = cvs.getContext("2d");
                ctt.drawImage(tmp_i, 0, 0, tmp_i.width, tmp_i.height, 0, 0, tmp_w, tmp_h);
                options.imgSrc = cvs.toDataURL('image/png');
            }
            
            obj.spinner.show();
            obj.image.onload = function() {
                obj.spinner.hide();
                setBackground();

                el.bind('vmousedown', imgMouseDown);
                el.bind('vmousemove', imgMouseMove);
                $(window).bind('vmouseup', imgMouseUp);
                el.bind('mousewheel DOMMouseScroll', zoomImage);
            };
            obj.image.src = options.imgSrc;
            el.on('remove', function(){$(window).unbind('vmouseup', imgMouseUp)});
        };
        tmp_i.src = options.imgSrc;

        return obj;
    };

    jQuery.fn.cropbox = function(options){
        return new cropbox(options, this);
    };
}));
