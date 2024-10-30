<?php

$form_id     = $_GET['id'];
$form_fields = $this->get_form_fields($form_id);
$fields = isset($this->mapped_fields[$form_id]['fields']) ? $this->mapped_fields[$form_id]['fields'] : array();
$tags   = isset($this->mapped_fields[$form_id]['tags']) ? $this->mapped_fields[$form_id]['tags'] : array();

GFFormSettings::page_header();

do_action('ifg_save_gf_form_settings');
?>

<h3><?php _e('Map form fields to Infusionsoft fields', 'infugrator');?></h3>
<p><?php _e('<b>Note:</b> You need to map at least <b>FirstName</b> and <b>Email</b> in order to send the form\'s entry to Infusionsoft!', 'infugrator');?></p>

<form action="" method="post">

    <div style="max-width: 600px;">

		<div class="item-row">
			<div class="item-row">
	    		<div class="col-title"><?php _e('Apply Tags', 'infugrator');?> <?php gform_tooltip("ifg_infusionsoft_apply_tags");?></div>

	    		<select style="display: none;min-width: 300px;" multiple name="ifg-<?php echo self::$slug;?>[tags][]" data-remote='{"type": "tags"}'>
			    	<?php if(is_array($tags) && count($tags) > 0): ?>
						<?php foreach($tags as $tag): ?>
							<option <?php selected($tag, $this->utility->get_tag_data($tag, 'id'));?> value="<?php echo $this->utility->get_tag_data($tag, 'id');?>"><?php echo $this->utility->get_tag_data($tag, 'label');?></option>
						<?php endforeach; ?>
					<?php endif; ?>
			    </select>
	    	</div>
    	</div>

    	<div class="item-row">
    		<div class="row">
				<div class="col-sm-6 col-title"><?php _e('Infusionsoft Fields', 'infugrator');?></div>
				<div class="col-sm-6 col-title"><?php _e('Form Fields', 'infugrator');?></div>
			</div>

			<?php if( is_array($this->get_infusionsoft_fields()) && count($this->get_infusionsoft_fields()) > 0 ): ?>

				<?php foreach($this->get_infusionsoft_fields() as $field):
					$selected_field = isset($fields[$field]) ? $fields[$field] : '';
					?>

					<div class="item-row">
						<div class="row">
							<div class="col-sm-5"><?php echo $field;?></div>
							<div class="col-sm-7"><?php echo $this->output_mapped_fields($field, $selected_field, $form_fields);?></div>
						</div>
					</div>
			    <?php endforeach; ?>

	    	<?php endif;?>
    	</div>

    	<div class="item-row">
    		<div class="col-title"><?php _e('Trigger Campaign Goal on Success', 'infugrator');?></div>
	    	<?php $this->utility->output_trigger_campaign($form_id, 'ifg-'.self::$slug.'[apicallname]', self::$slug);?>
    	</div>

    </div><br/>

    <button type="submit" class="button button-primary" name="ifg-<?php echo self::$slug;?>-save">Save fields</button><br/><br/>

</form>

<?php

GFFormSettings::page_footer();