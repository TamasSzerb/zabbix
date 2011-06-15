<?php
$schema = DB::getSchema('config');
?>
<div id="dialog" style="display:none; white-space: normal;"></div>

<script type="text/javascript">

	jQuery(document).ready(function(){

		jQuery("#resetDefaults").click(function(){

			jQuery('#dialog').text("<?php echo _('Reset all fields to default values?'); ?>");
			var w = jQuery('#dialog').outerWidth()+20;

			jQuery('#dialog').dialog({
				buttons: [
					{text: '<?php echo _('Reset defaults');?>', click: function(){
						// Unacknowledged problem events
						jQuery('#problem_unack_color').val("<?php echo $schema['fields']['problem_unack_color']['default']; ?>");
						jQuery('#problem_unack_color').change();
						<?php if($schema['fields']['problem_unack_style']['default'] == 0){ ?>
							jQuery('#problem_unack_style').removeAttr('checked');
						<?php }else{ ?>
							jQuery('#problem_unack_style').attr('checked', 'checked');
						<?php } ?>

						// Acknowledged problem events
						jQuery('#problem_ack_color').val("<?php echo $schema['fields']['problem_ack_color']['default']; ?>");
						jQuery('#problem_ack_color').change();
						<?php if($schema['fields']['problem_ack_style']['default'] == 0){ ?>
							jQuery('#problem_ack_style').removeAttr('checked');
						<?php }else{ ?>
							jQuery('#problem_ack_style').attr('checked', 'checked');
						<?php } ?>

						// Unacknowledged ok events
						jQuery('#ok_unack_color').val("<?php echo $schema['fields']['ok_unack_color']['default']; ?>");
						jQuery('#ok_unack_color').change();
						<?php if($schema['fields']['ok_unack_style']['default'] == 0){ ?>
							jQuery('#ok_unack_style').removeAttr('checked');
						<?php }else{ ?>
							jQuery('#ok_unack_style').attr('checked', 'checked');
						<?php } ?>

						// Acknowledged ok events
						jQuery('#ok_ack_color').val("<?php echo $schema['fields']['ok_ack_color']['default']; ?>");
						jQuery('#ok_ack_color').change();
						<?php if($schema['fields']['ok_ack_style']['default'] == 0){ ?>
							jQuery('#ok_ack_style').removeAttr('checked');
						<?php }else{ ?>
							jQuery('#ok_ack_style').attr('checked', 'checked');
						<?php } ?>

						jQuery('#ok_period').val("<?php echo $schema['fields']['ok_period']['default']; ?>");
						jQuery('#blink_period').val("<?php echo $schema['fields']['blink_period']['default']; ?>");

						jQuery(this).dialog("destroy");
					} },
					{text: '<?php echo _('Cancel');?>', click: function(){
						jQuery(this).dialog("destroy");
					}}
				],
				draggable: false,
				modal: true,
				width: (w > 600 ? 600 : 'inherit'),
				resizable: false,
				minWidth: 200,
				minHeight: 100,
				title: '<?php echo _('Reset confirmation');?>',
				close: function(){ jQuery(this).dialog('destroy'); }
			});

			jQuery('#dialog').dialog('widget').find('button:first').addClass('main');
		});
	});

</script>
