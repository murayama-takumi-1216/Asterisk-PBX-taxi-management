// JavaScript Document
/*************************************************************
 * jConf - jQuery Plugin
 * Simple and Light Confirmation Alternative
 *
 * Examples and documentation at: http://aku.salimag.us/jconf-jquery-plugin.html
 * 
 * Created By Agus Salim
 * 
 * Version: 1.2.0 (06/16/2011)
 * Requires: jQuery v1.3+
 *
/*************************************************************/

(function($){
	$.fn.jConf = function (options) {
		//set default parameter for this plugin
		var defaults = {			
			sText: "Are You Sure ?",
			textInput: false,
			inputVal: '',
			okBtn: "Yes",
			noBtn: false,
			callResult: false
		}
		var param = $.extend({},defaults, options);	//read default options and compare with used defined options
		var wHeight = $(document).height();
		var wWidth = $(document).width();
		var poinx = 0;
		var poiny = 0;
		var elem = {};

		$(this).click(function(e){
			//elem = {'id': $(this).attr('id'), 'class': $(this).attr('class')};
			elem = $(this);
			poinx = e.pageX + 10; 	//Get X coodrinates
			poiny = e.pageY + 10; 	//Get Y coordinates
			render();				//render background and container box
			showBox(poinx, poiny);	//adjust jConf box position depend on mouse position
			setUI();				//read event on button YES and NO
			e.preventDefault();			
		});		
				
		function render(){
			//add render div in body
			$('body').append('<div class="jconfRender"></div>');
			$('.jconfRender').css({'height': wHeight, 'width': wWidth}).fadeIn(100);
			$('body').append('<div id="jconfBox"></div>');
			$('#jconfBox').html(genContent());
		}
		function showBox(posX, posY){
			var mousex = posX + 10; //Get X coodrinates
			var mousey = posY + 10; //Get Y coordinates
			var boxWidth = $('#jconfBox').width(); 	//Find width of box container
			var boxHeight = $('#jconfBox').height(); //Find height of box container
			
			var boxVisX = wWidth - (mousex + boxWidth);	//Distance of element from the right edge of viewport				
			var boxVisY = wHeight - (mousey + boxHeight);	//Distance of element from the bottom of viewport

			//If box container exceeds the X coordinate of viewport
			if ( boxVisX < 10 ) { mousex = posX - boxWidth - 40;} 
			//If box container exceeds the Y coordinate of viewport
			if ( boxVisY < 10 ) { mousey = posY - boxHeight - 40;}
			//set default position
			$('#jconfBox').css({'left': mousex, 'top': mousey});
		}
				
		function genContent(){
			//add text container
			var isi = '<div class="jconfText">' + param.sText + '</div>';
				//cek wether user define input or not, and add it when defined
				if(param.textInput){
					isi = isi + '<input type="text" class="jconfInput" value="' + param.inputVal + '" />';
				}				
				isi = isi + '<div style="clear:both; margin-bottom:10px;"></div>'			//add separator
				isi = isi + '<a class="jconfBtn" id="jconfBtnOK">' + param.okBtn + '</a>&nbsp;';	//add button OK
				//check wether user define only 1 button or not
				if(param.noBtn){
					isi = isi + '<a class="jconfBtn">' + param.noBtn + '</a>';			//add button NO
				}
			return isi;
		}
		
		function setUI(){
			$('.jconfInput').focus();
			//bind click functon on background div
			$('.jconfRender').click(function(){	
				$('.jconfRender').fadeOut(100,function(){	//animate and remove all object created by jConf
					$('#jconfBox').remove();				//remove box container
					$('.jconfRender').remove();				//remove background
				});
			});	
			
			//bind on click event and give callback
			$('.jconfBtn').click(function(){
                                ele = this;
				if ($.isFunction(param.callResult)){ 
					//if there is input on jConf and btn OK is pressed
					if( param.textInput ){
						//Give text value as callback
						//param.callResult($('.jconfInput').val());	
						param.callResult({'btnVal': $(this).html(), 'inputVal': $('.jconfInput').val(), 'oElem': elem});
					}else{
						//Give btn text as callback
						//param.callResult($(this).html()); 			
						param.callResult({'btnVal': $(this).html(), 'inputVal': ele.id, 'oElem': elem});
					}
				}
				$('.jconfRender').trigger('click');				
			});
		}
	}
})(jQuery);
