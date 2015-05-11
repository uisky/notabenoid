(function() {
	var $massForm = $("#form-mass"), $checkAll = $("#cb-check-all"), $checkboxes = $massForm.find("[name=id\\[\\]]"),
		$massActions = $("#mass-actions");

	function massNeeded() {
		if($checkboxes.filter(":checked").length) {
			$massActions.show();
		} else {
			$massActions.hide();
			$checkAll.prop("checked", false);
		}
	}

	$checkAll.click(function(e) {
		$checkboxes.prop("checked", $checkAll.prop("checked"));
		massNeeded();
	});

	$checkboxes.click(massNeeded);

	$massForm.submit(function(e) {
		if($massForm.find("[name=act]").val() == "rm") {
			var n = $checkboxes.filter(":checked").length;
			return confirm("Вы уверены, что хотите удалить выбранные сообщения (" + n + "шт.) ?");
		}
		return true;
	});
})();
