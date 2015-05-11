<div class='tools'>
	<h5>Закладки</h5>

	<p>
		Чтобы поменять название или удалить закладку, наведите на неё курсор и нажмите на значок <i class='icon-edit'></i>.
		Чтобы изменить порядок закладок, таскайте их мышкой.
	</p>

	<p id="bookmarks-mass-rm">
		<button class="btn btn-danger" onclick="E.mass_rm()"><i class="icon-remove-sign icon-white"></i> Массовое удаление</button>
	</p>
	<div id="bookmarks-mass-rm-on" class="hide">
		<p>Кликните на те закладки, которые хотите удалить.</p>
		<form method="post" action="/my/bookmark_rm" id="form-mass-rm">
			<button type="submit" class="btn btn-danger" onclick="E.mass_rm_go()" disabled><i class="icon-remove-sign icon-white"></i> Удалить отмеченные!</button>
			<button type="button" class="btn" onclick="E.mass_rm_cancel()"><i class="icon-remove"></i> Отмена</button>
		</form>
	</div>
</div>