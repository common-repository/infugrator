<?php

$schedule = $this->settings->get(self::$slug.'/schedule');
$tables   = $this->settings->get(self::$slug.'/tables');
$contacts = $this->utility->rgar($tables, array_search('Contact', (array) $tables));
$tags     = $this->utility->rgar($tables, array_search('ContactGroup', (array) $tables));

?>

<div class="item-row">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<label><b><?php _e('Schedule a Backup', 'infugrator');?></b></label>
		</div>
		<div class="col-sm-8 col-md-9">
			<select name="ifg-settings[<?php echo self::$slug;?>][schedule]">
				<option value="0">-</option>
				<option <?php selected($schedule, 'ifg_daily');?> value="ifg_daily"><?php _e('Daily', 'infugrator');?></option>
				<option <?php selected($schedule, 'ifg_weekly');?> value="ifg_weekly"><?php _e('Weekly', 'infugrator');?></option>
			</select>
		</div>
	</div>
</div>

<div class="item-row">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<label for="app-name"><b><?php _e('Choose what to backup', 'infugrator');?></b></label>
		</div>
		<div class="col-sm-8 col-md-9">
			<p><label><input type="checkbox" <?php checked($contacts, 'Contact');?> name="ifg-settings[<?php echo self::$slug;?>][tables][]" value="Contact" /> <?php _e('Contacts', 'infugrator');?></label></p>
			<p><label><input type="checkbox" <?php checked($tags, 'ContactGroup');?> name="ifg-settings[<?php echo self::$slug;?>][tables][]" value="ContactGroup" /> <?php _e('Tags', 'infugrator');?></label></p>
		</div>
	</div>
</div>
