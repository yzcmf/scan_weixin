$(document).ready( function() {
	var power_by = $("<p></p>");
	power_by.attr("id", "power_by");
	power_by.html("Powered By <a href=\"http://miskcoo.com\">miskcoo</a> @ 2014");
	$("body").append(power_by);

	var offset = 30;
	var scroll_event = function() {
		power_by.css("top", $(this).scrollTop()
			+ $(this).height() - offset);
	};
	$(window).scroll(scroll_event);
	$(window).resize(scroll_event);
} );

$(document).ready( function() {
	// 设置 jsbtn 样式
	$(".jsbtn").addClass("jsbtn_leave");
	$(".jsbtn").mouseenter( function() {
		$(this).removeClass("jsbtn_leave");
		$(this).addClass("jsbtn_stay");
	} );

	$(".jsbtn").mouseleave( function() {
		$(this).addClass("jsbtn_leave");
		$(this).removeClass("jsbtn_stay");
	} );
} );
