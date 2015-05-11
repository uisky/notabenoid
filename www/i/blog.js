var Blog = {
	init: function() {
		if($("#Lenta").length) $(document).bind("keyup", Blog.key_document);
	},
	my: function(id) {
		$.get("/my/comments/", {add: id, ajax: 1}, function(data) {
			if(data == "ok")		
				$("#post_" + id + " .info .tools .talks").replaceWith("<a href='/my/comments/#post_" + id + "' title='пост в ваших обсуждениях'>&rarr;</a>")
		})
		return false;
	},
	
	scroll_post: -1,
	key_document: function(e) {
		console.log("Blog.key_document");
		if(e.ctrlKey || e.altKey) {
			if(e.keyCode == 40) {
				$post = $("#Lenta .post").eq(Blog.scroll_post + 1);
				if($post.length) {
					Blog.scroll_post++;
					$.scrollTo($post, 100, {offset: -10});
				}
			} else if(e.keyCode == 38) {
				if(Blog.scroll_post > 0) {
					Blog.scroll_post--;
					$.scrollTo($("#Lenta .post").eq(Blog.scroll_post), 100, {offset: -10});
				}
			}
		}
	},
}

$(Blog.init);