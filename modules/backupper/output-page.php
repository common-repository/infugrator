<?php

$schedule = $this->settings->get(self::$slug.'/schedule');
$tables   = $this->settings->get(self::$slug.'/tables');
$labels   = array(
	'Contact' => 'Contacts',
	'ContactGroup' => 'Tags',
);

?>

<div class="wrap">
	<h2><?php echo self::$name;?></h2><br/>

	<div class="row">
		<div class="col-sm-7">
			<div class="widget">

				<?php if($schedule != false && is_array($tables) && count($tables) > 0): ?>

					<form action="" method="post">
						<input type="hidden" name="ifg-nonce" value="<?php echo wp_create_nonce( 'ifg-nonce' );?>">
						<button type="submit" class="button button-primary button-large pull-right" name="ifg-<?php echo self::$slug;?>-backup"><?php _e('Backup Now', 'infugrator');?></button>
					</form><br/><br/>

					<div class="tabs-container">
					    <ul class="tabs-menu">
					    	<?php
				    		$index = 0;
					    	foreach($tables as $table_name):
				    		$current = $index == 0 ? 'class="current"' : '';
				    		?>
				       	 		<li <?php echo $current;?> data-target="<?php echo $table_name;?>"><?php echo $labels[$table_name];?></li>
			    			<?php
			    			$index++;
			    			endforeach;?>
					    </ul>

						<div class="tab">

							<?php foreach($tables as $table_name): ?>
								<div id="<?php echo $table_name;?>" class="tab-content">
									<div class="list">
										<div class="list__head">
											<div class="row">
												<div class="col-xs-8 col-sm-4"><?php _e('Date &amp; Time', 'infugrator');?></div>
												<div class="col-sm-4 hidden-xs"><?php _e('Created by', 'infugrator');?></div>
												<div class="col-xs-4 col-sm-4"><?php _e('Action', 'infugrator');?></div>
											</div>
										</div>
										<?php
										$backups = $this->list_backups(strtolower($table_name));

										if(is_array($backups) && count($backups) > 0):?>

											<?php foreach($backups as $item):
											$created_by = $item->created_by != '0' ? '<a href="'.get_edit_user_link($item->created_by).'">'.get_userdata( $item->created_by )->user_login.'</a>' : 'System';
											?>
												<div class="list__item">
													<div class="row">
														<div class="col-xs-8 col-sm-4">
															<i class="fa fa-calendar" aria-hidden="true"></i> <?php echo date('Y-m-d', $item->date_time);?>&nbsp;
															<i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo date('H:i', $item->date_time);?>
														</div>
														<div class="col-sm-4 hidden-xs"><?php echo $created_by;?></div>
														<div class="col-xs-4 col-sm-4"><a href="<?php echo admin_url('admin.php?page=ifg-backupper').'&backup='.$item->table_name.'/'.$item->filename;?>"><?php _e('Download', 'infugrator');?></a></div>
													</div>
												</div>
											<?php endforeach;?>
										<?php else: ?>
											<div class="list__item"><?php _e('There are no backups yet.', 'infugrator');?></div>
										<?php endif;?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

				<?php else:

					echo sprintf(
						__('Please go to %s to schedule backups.', 'infugrator'),
						'<a href="'.admin_url('admin.php?page=ifg-settings&ref='.rand(1, 666).'#ifg-'.self::$slug).'">'.__('Settings', 'infugrator').'</a>'
					); ?>

				<?php endif;?>

			</div>
		</div>
	</div>

</div>