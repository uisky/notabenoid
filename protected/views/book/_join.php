<style type="text/css">
#join_btn {display:block;}
#join_msg {display:none;}
</style>
<?php
	if(Yii::app()->user->isGuest) {
		echo "Вам нужно зарегистрироваться или войти на сайт и подать заявку на вступление в эту группу.";
	} else {
?>
	<form method="post" action="<?=$book->getUrl("members_join"); ?>" class="well form-horizontal" style="margin-top:10px;">
		<div id="join_btn">
			<button type="button" onclick="$('#join_btn').hide(); $('#join_msg').show(); $('#join [name=message]').focus();" class="btn btn-success" />
			<i class="icon-plus icon-white"></i> Вступить в группу
			</button>
		</div>
		<div id="join_msg">
			<label>
				Ваша заявка сначала будет рассмотрена <?php echo $book->ac_membership == "m" ? "модераторами" : "создателем перевода"; ?>.
				Вы можете написать им короткое сообщение:
			</label>
			<input type="text" name="message" maxlength="200" class="span4" />
			<button type="submit" class="btn" /><i class="icon-ok"></i> Отправить заявку</button>
			<button type="button" class="btn" onclick="$('#join_btn').show(); $('#join_msg').hide(); $('#join_msg [name=message]').focus();"><i class="icon-remove"></i> Отмена</button>
		</div>
	</form>
<?php
	}
?>
