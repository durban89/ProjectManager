$(function () {
	//给外站连接加上新窗口打开
	$("a").each(function (id, elem) {
		var parse = elem.href.match(/^(([a-z]+):\/\/)?([^\/\?#]+)\/*([^\?#]*)\??([^#]*)#?(\w*)$/i);
		if ((parse != null && parse.length > 3 && parse[3] != location.hostname)
			|| ($(elem).attr("rel") + "").toLowerCase() == "external") {
			$(elem).attr("target", "_blank");
		}
	});
});