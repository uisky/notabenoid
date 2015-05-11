<?php
/**
 * @var UsersController $this
 * @var User $user
 * @var RegInvite $invite
 * @var RegInvite[] $sent
 */
$this->pageTitle = "Приглашения";
$this->renderPartial("profile_head", array("user" => $user, "h1" => "приглашения"));
?>
<style type="text/css">
	 .invite-who { display: none; }
</style>

<?php if($user->n_invites == 0): ?>
	<p>У вас нет приглашений.</p>
<?php else: ?>
<p>
	Вы можете пригласить в наш клуб ещё <?=Yii::t("app", "{n} человека|{n} человек|{n} человек", $user->n_invites); ?>.
</p>
<form method="post" class="form-horizontal" id="invite-send">
	<h4>Кого вы хотите пригласить?</h4>

	<?=CHtml::errorSummary($invite, '<div class="alert alert-box alert-danger">', '</div>'); ?>

	<label class="radio">
		<input type="radio" name="invite[type]" value="user" <?=$invite->type == "user" ? "checked" : ""; ?>>
		Уже зарегистрированного на Нотабеноиде, но пока неактивного пользователя
	</label>
	<label class="radio">
		<input type="radio" name="invite[type]" value="new" <?=$invite->type == "new" ? "checked" : ""; ?>>
		Нового переводчика
	</label>

	<div id="invite-send-more" <?=$invite->type == "" ? "style='display:none;'" : ""; ?>>
		<p>
			<span class="invite-who invite-who-user">Логин:</span>
			<span class="invite-who invite-who-new">E-mail:</span>
			<input type="text" name="invite[clue]" value="<?=CHtml::encode($invite->clue); ?>">

			<?php if(Yii::app()->user->can("admin")): ?>
			<span class="invite-who invite-who-user">
				Отсыпать инвайтов:
				<input type="text" class="span1" name="invite[giveInvites]" value="<?=CHtml::encode($invite->giveInvites); ?>">
			</span>
			<?php endif ?>
		</p>

		<p>
			<label>Если хотите, можете дописать что-нибудь от себя к письму с приглашением:</label>
			<textarea name="invite[message]" rows="4" style="width:100%"><?=CHtml::encode($invite->message); ?></textarea>
		</p>
		<p>
			<button type="submit" class="btn btn-success">Пригласить</button>
		</p>

	</div>
</form>
<script type="text/javascript">
	(function() {
		function typeclick(e) {
			$("#invite-send-more").show();
			$(".invite-who").hide();
			$(".invite-who-" + $(this).val()).show();
			$("#invite-send input[name=invite\\[clue\\]").focus();
		}
		$("#invite-send [name=invite\\[type\\]]").click(typeclick);
		$("#invite-send input[type=radio]:checked").click();
	})();
</script>
<?php endif ?>

<?php if(count($sent) > 0): ?>
<h4>Отправленные приглашения</h4>
<table class="table table-bordered table-striped" id="sent">
<?php
foreach($sent as $inv) {
	echo "<tr data-id='{$inv->id}'>";
	echo "<td>" . Yii::app()->dateFormatter->format("dd.MM.yyyy HH:mm", $inv->cdate) . "</td>";
	echo "<td>";
	if($inv->to_id) echo $inv->buddy->ahref;
	else echo $inv->to_email;
	echo "</td>";
	echo "<td>";
	echo "<a href='#' class='btn btn-small revoke'><i class='icon icon-remove'></i> отозвать</a> ";
	echo "<a href='#' class='btn btn-small resend'><i class='icon icon-envelope'></i> ещё раз</a> ";
	echo "<a href='#' class='btn btn-small code'><i class='icon icon-leaf'></i> получить код</a> ";
	echo "</td>";
}
?>
</table>
<form id="form-revoke" method="post"><input type="hidden" name="revoke"></form>
<form id="form-resend" method="post"><input type="hidden" name="resend"></form>

<div id="modal-code" class="modal hide fade">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Отправить инвайт</h3>
	</div>
	<div class="modal-body">
		<p>
			Вы можете отправить вашему коллеге ссылку на регистрацию сами, например, в социальной сети
			или продиктовать её по телефону.
		</p>
		<p id="code-code">

		</p>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn" data-dismiss="modal">Ok</a>
	</div>
</div>

<script type="text/javascript">
	(function() {
		$("#sent").on("click", "a.revoke", function(e) {
			e.preventDefault();
			if(!confirm("Вы уверены, что хотите отозвать это приглашение?")) return;
			$("#form-revoke [name=revoke]").val($(this).parents("tr").data("id"));
			$("#form-revoke").submit();
		}).on("click", "a.resend", function(e) {
			e.preventDefault();
			if(!confirm("Отправить приглашение ещё раз?")) return;
			$("#form-resend [name=resend]").val($(this).parents("tr").data("id"));
			$("#form-resend").submit();
		}).on("click", "a.code", function(e) {
			e.preventDefault();
			var $modal = $("#modal-code"), $codeP = $("#code-code");

			$.get(
				'/users/' + User.id + '/inviteCode',
				{iid: $(this).parents("tr").data("id")},
				function(data) {
					var $input = $('<textarea id="code-code" class="span5" rows="3">')
						.val(data)
						.on("focus", function() { $(this).select(); });
					$codeP.html('').append($input);

					$modal.modal('show').on('shown', function() { $input.focus(); });
				}
			);
		});
	})();
</script>


<?php endif ?>
