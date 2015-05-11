<?php $this->beginContent('//layouts/' . $this->layout_layout); ?>
<div class="row">
	<div class="span8">
		<?php echo $content; ?>

		<div style='margin-top:30px'>
		<?php echo $this->ad(Controller::AD_PLACE_BOTTOM); ?>
		</div>
	</div>

	<div class="span4 sr">
		<?php
			if(is_array($this->side_view)) {
				foreach($this->side_view as $view => $params) {
					if(is_string($params)) {
						echo "<div class='tools'><h5>{$view}</h5><p>{$params}</p></div>";
					}
					if(is_array($params)) echo $this->renderPartial($view, $params);
				}
			} elseif($this->side_view != "") {
				echo $this->renderPartial($this->side_view, $this->side_params);
			}

			echo $this->ad(Controller::AD_PLACE_SIDE);
		?>
	</div>
</div>
<?php $this->endContent(); ?>