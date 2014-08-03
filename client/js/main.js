$(document).ready( function() {
	var power_by = $("<p></p>");
	power_by.attr("id", "power_by");
	power_by.html("Powered By <a href=\"http://miskcoo.com\">miskcoo</a> @ 2014");
	$("body").append(power_by);

	var offset = $(window).height() - power_by.position().top;
	var scroll_event = function() {
		power_by.css("top", $(this).scrollTop()
			+ $(this).height() - offset);
	};
	$(window).scroll(scroll_event);
	$(window).resize(scroll_event);
} );
