<div class="wrap">
	<h2><?php echo self::$name;?></h2><br/>

	<button type="buttton" class="button button-primary button-large" data-syncstats>Refresh Data</button><br/><br/>

	<div class="row">
		<?php
		if(is_array($this->widgets()) && count($this->widgets()) > 0){

			foreach($this->widgets() as $widget){

				$id = isset($widget['id']) ? 'id="'.$widget['id'].'"' : '';
				$title = isset($widget['title']) ? '<div class="widget__title">'.$widget['title'].'</div>' : '';
				$content = isset($widget['content']) ? $widget['content'] : '';
				?>
				<div class="col-md-6">
					<div class="widget">
						<?php echo $title;?>
						<div <?php echo $id;?> class="chart loading">
							<?php echo $content;?>
						</div>
					</div>
				</div>
			<?php
			}
		}
		?>
	</div>

</div>