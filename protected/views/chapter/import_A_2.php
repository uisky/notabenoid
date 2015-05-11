<?php
	/**
	 * @var Array $text
	 * @var Chapter $chap
	 */

	$this->pageTitle = "Импортировать текст в перевод {$chap->book->fullTitle}";
?>
<style type="text/css">
	#TextSource_text {height:200px;}

	#Chopper {
		border:1px solid #777;
		padding:10px;
	}

	#Chopper p {
		border-bottom:1px dashed #777;
		margin:0px 0;
		padding:10px 0;
	}
	#Chopper p:hover {
		background:#eee;
	}
	#Chopper p.toobig {
		border-left:20px solid red;
		padding-left:20px;
	}
	#Chopper p a.glue, #Chopper p a.rm{ font-size:10px; display:none;}
	#Chopper p:hover a.glue, #Chopper p:hover a.rm{ display:inline;}

	#Chopper span:hover {
		background:#adff2f;
		border-left:2px solid black;
		padding-left:5px;
	}
</style>
<script type="text/javascript">
var C = {
	init: function() {
		$("#Chopper")
			.delegate("span", "click", C.chop)
			.delegate("a.glue", "click", C.glue)
			.delegate("a.rm", "click", C.rm);
	},
	chop: function() {
		var p = $(this).parent("p");

		var p1 = $($(this).prevAll().get().reverse());
		var p2 = $(this).nextAll().andSelf();
		if(p1.length == 0) return;
		var html1 = "", html2 = "";
		p1.each(function() {
			html1 += $(this).outerHTML();
		});
		p2.each(function() {
			html2 += $(this).outerHTML();
		});

		p.replaceWith("<p>" + html1 + " <a href='#' class='glue'>(склеить)</a> <a href='#' class='rm'>(удалить)</a></p><p>" + html2 + "</p>");
		C.check_lengths();
	},
	glue: function(e) {
		e.preventDefault();

		var p1 = $(this).parent("p");
		var p2 = p1.next("p");
		if(p2.length == 0) return false;
		p1.append(p2.html());
		$(this).remove();
		p2.remove();

		console.log("new length: %d", p1.text().length);
		if(p1.text().length > 1024) p1.addClass("toobig");

		return false;
	},
	rm: function(e) {
		e.preventDefault();
		$(this).parent("p").remove();
	},
	save: function() {
		var i = 0, $form = $("#form-chop-text");
		$form.find("button.save").attr("disabled", true);
		$("#Chopper p").each(function() {
			var $this = $(this);
			$this.children("a").remove();
			$("<input type='hidden' />").val($this.text()).attr("name", "t[txt][" + i + "]").appendTo($form);
			i++;
		});
		$("#form-chop-text").submit();
//		$("<input type='submit'>").appendTo("#form-chop-text");
	},
	check_lengths: function() {
		$("#Chopper p").each(function() {
			var $p = $(this);
			if($p.text().length > 1024) $p.addClass("toobig");
			else $p.removeClass("toobig");
		})
	}
}
$(C.init);
</script>
<h1>Импортировать текст</h1>
<p>
	Перевод: <?=$chap->book->ahref; ?>, <?=$chap->ahref; ?>
</p>

<p class="help-block">
	Кликами мыши разбейте текст на удобные для перевода фрагменты. Имейте в виду, что одна глава не может содержать более 4000 фрагментов.
</p>

<form method='post' id='form-chop-text' action="<?=$chap->getUrl("import_text_save"); ?>">
<div id="Chopper">
<?php
	foreach($text as $p) {
		if(trim($p) == "") continue;
		$class = "";
		if(mb_strlen($p) > 1024) $class = " class='toobig'";
		$p = htmlspecialchars($p);

		$p = "<span>" . preg_replace('/(\s+)/', '\1</span><span>', $p . "\n") . "</span>";

		echo "<p{$class}>";
		echo nl2br($p);
		echo "<a href='#' class='glue'>(склеить)</a> ";
		echo "<a href='#' class='rm'>(удалить)</a> ";
		echo "</p>";
	}
?>
</div>

<div class="form-actions">
	<button type="button" class="btn btn-success cancel" onclick="location.href='<?=$chap->getUrl("import"); ?>'">
		<i class="icon-arrow-left icon-white"></i>
		Назад
	</button>
	<button type="button" class="btn btn-primary pull-right save" onclick="C.save()">
		Сохранить
		<i class="icon-arrow-right icon-white"></i>
	</button>
</div>
</form>
