var Blog = {
	init: function() {
		if($("#Lenta").length) $(document).bind("keyup", Blog.key_document);
	},

	my: function(id, button) {
		$.ajax({
			type: "POST",
			url: "/my/comments/add",
			data: {ajax: 1, post_id: id},
			dataType: "json",
			success: function(data) {
				if(data.error) {
					alert(data.error);
					return false;
				}
				$(button).replaceWith("<a href='/my/comments/?mode=p#post_" + data.id + "' title='Пост в ваших обсуждениях'>&rarr;</a>");
			}
		});
		return false;
	},

	scroll_post: -1,
	key_document: function(e) {
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
	}
};

$(Blog.init);