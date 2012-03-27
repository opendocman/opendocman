/**
 * noticeMsg 1.0 - Plugin for jQuery
 *
 * Notice Message Plugin is jQuery Plugin inspired by Oleg Slobodskoi's humanized messages plugin.
 * http://jsui.de/projects/humanizedmessages/
 *
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Depends:
 *   jquery.js
 *
 *  Author: Hiromitz ( http://hiromitz.jimdo.com/ )
 */
(function($){
var PROP_NAME = 'NoticeMsg';
$.fn.noticeMsg = function(message, options) {
	var op = $.extend({}, {
			message: 'Notification',
			addClass: '',
			dur: 3000,
			fade: 300
		}, options),
		m = message || op.message;

	return this.each(function(){
		var e = this == window || this == document ? document.body : this;
		var $el = $(e).children('.notice-message').length ? $(e).children('.notice-message') : $('<div class="notice-message ' + op.addClass + '" />').appendTo(e);

		$el.html(m)
			.click(remove)
			.css({
				display: 'none',
				top: ($(this).height() - $el.innerHeight())/2,
				left: ($(this).width() - $el.innerWidth())/2
			})
			.stop().fadeIn(op.fade);

		clearTimeout($.data(e, PROP_NAME));
		op.dur && $.data(e, PROP_NAME, setTimeout(remove, op.dur));

		function remove() {
			$.removeData(e, PROP_NAME);
			$el.stop().fadeOut(op.fade, function(){
				$el.remove();
			});
		}
	});
};
$.noticeMsg = function(message, options) {
	$(document.body).noticeMsg(message, options);
}
})(jQuery);
