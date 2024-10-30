<?php

$app_name    = $this->get('application/name');
$app_key     = $this->get('application/key');
$merchant_id = $this->get('application/merchant_id');

?>

<div class="item-row">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<label for="app-name"><b><?php _e('Application Name', 'infugrator');?></b></label>
		</div>
		<div class="col-sm-8 col-md-9">
			<input type="text" id="app-name" name="ifg-settings[application][name]" value="<?php echo $app_name ;?>" />
			<p class="field-desc"><a href="http://help.infusionsoft.com/taxonomy/term/4/0" target="_blank"><?php _e('How do I find this?', 'infugrator');?></a></p>
		</div>
	</div>
</div>

<div class="item-row">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<label for="app-key"><b><?php _e('Encrypted Key', 'infugrator');?></b></label>
		</div>
		<div class="col-sm-8 col-md-9">
			<input type="password" id="app-key" name="ifg-settings[application][key]" value="<?php echo $app_key ;?>"/>
			<p class="field-desc"><a href="http://help.infusionsoft.com/userguides/get-started/tips-and-tricks/api-key" target="_blank"><?php _e('How do I find this?', 'infugrator');?></a></p>
		</div>
	</div>
</div>

<div class="item-row">
	<div class="row">
		<div class="col-sm-4 col-md-3">
			<label for="merchant-id"><b><?php _e('Merchant Account ID', 'infugrator');?></b></label>
		</div>
		<div class="col-sm-8 col-md-9">
			<input type="text" id="merchant-id" name="ifg-settings[application][merchant_id]" value="<?php echo $merchant_id ;?>"/>
			<p class="field-desc"><a href="http://instachargeit.com/155/how-do-i-find-my-merchant-account-id-in-infusionsoft/" target="_blank"><?php _e('How do I find this?', 'infugrator');?></a></p>
		</div>
	</div>
</div>