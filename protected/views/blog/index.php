<?php
	Yii::app()->clientScript->registerScriptFile("/js/jquery.scrollTo.js")->registerScriptFile("/js/blog.js");

	$this->pageTitle = "Блог";
?>
<style type="text/css">
.moder-topic { cursor: pointer; }
#moder-topic-menu { position:absolute; margin-left: 220px; z-index: 100; background: #fff; padding: 15px; border-radius: 10px; border: 1px solid #777; box-shadow: 10px 10px 20px rgba(0,0,0,.3); }
</style>

<h1>Коллективный блог</h1>

<div id="Lenta">
<?php
	$has = array(
		"mytalks" => true,
	);
	$user = Yii::app()->user;
	if($user->can("blog_topic_moderate")) $has["extra"] = "<i class='moder-topic icon-chevron-down i'></i></a>";

	foreach($lenta->getData() as $post) {
		$has["edit"] = $post->user_id == $user->id || $user->can("blog_moderate");
		$this->renderPartial("_post", array("post" => $post, "placement" => "index", "has" => $has));
	}
?>
</div>

<?php
	$this->widget('bootstrap.widgets.TbPager', array('pages' => $lenta->pagination));

	if($user->can("blog_topic_moderate")):
?>
<div id="moder-topic-menu">
	<ul>
		<?php
			foreach(Yii::app()->params["blog_topics"]["common"] as $k => $v) {
				echo "<li><a href='#' rel='{$k}'>{$v}</a></li>";
			}
		?>
	</ul>
</div>
<script type="text/javascript">
(function() {
	var post_id = 0, $topicMenu = $("#moder-topic-menu");

	$("#Lenta").find(".post .info .moder-topic").attr("title", "Изменить раздел").click(function(e) {
		e.preventDefault();
		var $this = $(this), $info = $this.parents(".info"), id = parseInt($this.parents(".post").attr("id").substr(5));
		if(id == post_id) {
			post_id = 0;
			$topicMenu.hide();
		} else {
			post_id = id;
            $topicMenu.show().appendTo($info);
		}
	});

	$topicMenu.delegate("a[rel]", "click", function(e) {
		e.preventDefault();
		var topic = parseInt($(this).attr("rel"));
		console.log("Set topic %d to post %d", topic, post_id);

		$.ajax({
			url: "/moderator/blogTopic",
			type: "post",
			dataType: "json",
			data: {post_id: post_id, topic: topic},
			success: function(data) {
				console.log(data);
                $topicMenu.hide();
				if(data.error) return !!alert(data.error);
				$("#post_" + data.id).find(".info span.topic").html(data.topicHtml);
			}
		});
	})
})();
</script>
<?php endif; ?>