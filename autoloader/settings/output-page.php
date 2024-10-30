<div class="wrap ifg-settings">
	<h2><?php _e('Settings', 'infugrator')?></h2><br/>

	<?php settings_errors(); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields('ifg-settings-group');
        do_settings_sections(__FILE__);

		?>

		<div class="row">
			<?php
			if(is_array($this->widgets()) && count($this->widgets()) > 0){

				foreach($this->widgets() as $widget){

					$id = isset($widget['id']) ? $widget['id'] : '';
					$title = isset($widget['title']) ? '<div class="widget__title">'.$widget['title'].'</div>' : '';
					$content = isset($widget['content']) ? $widget['content'] : '';
					?>
					<div class="col-sm-7">
						<div class="widget">
							<?php echo $title;?>
							<div id="<?php echo $id;?>" data-scrolltarget="<?php echo $id;?>">
								<?php echo $content;?>
							</div>
						</div>
					</div>
					<?php
				}
			}
			?>
		</div>


		<?php submit_button('Save Settings');?>
	</form>

</div>