<script type="text/javascript">
	function removeDelayFlex(index) {
		jQuery('#delayFlex_' + index).remove();
		jQuery('#delay_flex_' + index + '_delay').remove();
		jQuery('#delay_flex_' + index + '_period').remove();

		if (jQuery('#delayFlexTable tr').length <= 7) {
			jQuery('#row_new_delay_flex').css('display', 'block');
		}
		else {
			jQuery('#row_new_delay_flex').css('display', 'none');
		}
	}

	function organizeInterfaces() {
		var interfaceType = itemTypeInterface(parseInt(jQuery('#type').val()));
		var selectedInterfaceId = jQuery('#selectedInterfaceId').val();
		var isSelected = false;
		var isInterfaceExist = false;

		if (interfaceType > 0) {
			jQuery('#interface_row option').each(function() {
				if (jQuery(this).data('interfacetype') == interfaceType) {
					isInterfaceExist = true;
				}
			});

			if (isInterfaceExist) {
				jQuery('#interface_row option').each(function() {
					if (jQuery(this).is('[selected]')) {
						jQuery(this).removeAttr('selected');
					}
				});

				jQuery('#interface_row option').each(function() {
					if (jQuery(this).data('interfacetype') == interfaceType) {
						jQuery(this).css('display', 'block');
						if (!isSelected) {
							if (jQuery(this).val() == selectedInterfaceId) {
								jQuery(this).attr('selected', 'selected');
								isSelected = true;
							}
						}
					}
					else {
						jQuery(this).css('display', 'none');
					}
				});

				// select first available option if we previously don't selected it by interfaceid
				if (!isSelected) {
					jQuery('#interface_row option').each(function() {
						if (jQuery(this).data('interfacetype') == interfaceType) {
							if (!isSelected) {
								jQuery(this).attr('selected', 'selected');
								isSelected = true;
							}
						}
					});
				}
			}
		}
	}

	function displayKeyButton() {
		var interfaceType = itemTypeInterface(parseInt(jQuery('#type').val()));
		switch (interfaceType) {
			case 2: // INTERFACE_TYPE_SNMP
			case 3: // INTERFACE_TYPE_IPMI
				jQuery('#keyButton').attr('disabled', 'disabled');
				break;
			default:
				jQuery('#keyButton').removeAttr('disabled');
		}
	}

	function setAuthTypeLabel() {
		if (jQuery('#authtype').val() == 1) {
			jQuery('#row_password label').html('<?php echo _('Key passphrase'); ?>');
		}
		else {
			jQuery('#row_password label').html('<?php echo _('Password'); ?>');
		}
	}

	jQuery(document).ready(function() {
		jQuery('#type').bind('change', function() {
			organizeInterfaces();
			displayKeyButton();
		});
		organizeInterfaces();
		displayKeyButton();

		jQuery('#authtype').bind('change', function() {
			setAuthTypeLabel();
		});
		setAuthTypeLabel();
	});
</script>
