<script type="text/x-jquery-tmpl" id="hostInterfaceRow">

<tr id="hostInterfaceRow_#{interfaceid}">
<td>
	<input type="hidden" name="interfaces[#{interfaceid}][new]" value="#{newValue}" />
	<input type="hidden" id="interface_id_#{interfaceid}" name="interfaces[#{interfaceid}][interfaceid]" value="#{interfaceid}" />
	<input class="input text" id="interface_ip_#{interfaceid}" name="interfaces[#{interfaceid}][ip]" type="text" size="24" value="#{ip}" />
</td>
<td>
	<input class="input text" id="interface_dns_#{interfaceid}" name="interfaces[#{interfaceid}][dns]" type="text" size="30" value="#{dns}" />
</td>
<td>
	<div class="jqueryinputset">
		<input type="radio" id="radio_ip_#{interfaceid}" name="interfaces[#{interfaceid}][useip]" value="1" #{*checked_ip} />
		<label for="radio_ip_#{interfaceid}"><?php print(S_IP);?></label>

		<input type="radio" id="radio_dns_#{interfaceid}" name="interfaces[#{interfaceid}][useip]" value="0" #{*checked_dns} />
		<label for="radio_dns_#{interfaceid}"><?php print(S_DNS);?></label>
	</div>
</td>
<td>
	<input class="input text" id="port_#{interfaceid}" name="interfaces[#{interfaceid}][port]" type="text" size="15" value="#{port}" />
</td>
<td>
	<div id="interface_type_#{interfaceid}" class="jqueryinputset">
		<input type="radio" id="radio_agent_#{interfaceid}" name="interfaces[#{interfaceid}][type]" value="<?php print(INTERFACE_TYPE_AGENT);?>" #{*checked_agent} />
		<label for="radio_agent_#{interfaceid}"><?php print(S_AGENT);?></label>

		<input type="radio" id="radio_snmp_#{interfaceid}" name="interfaces[#{interfaceid}][type]" value="<?php print(INTERFACE_TYPE_SNMP);?>" #{*checked_snmp} />
		<label for="radio_snmp_#{interfaceid}"><?php print(S_SNMP);?></label>

		<input type="radio" id="radio_jmx_#{interfaceid}" name="interfaces[#{interfaceid}][type]" value="<?php print(INTERFACE_TYPE_JMX);?>" #{*checked_jmx} />
		<label for="radio_jmx_#{interfaceid}"><?php print(S_JMX);?></label>

		<input type="radio" id="radio_ipmi_#{interfaceid}" name="interfaces[#{interfaceid}][type]" value="<?php print(INTERFACE_TYPE_IPMI);?>" #{*checked_ipmi} />
		<label for="radio_ipmi_#{interfaceid}"><?php print(S_IPMI);?></label>
	</div>
</td>
<td>
	<input #{*disabled} type="button" class="link_menu" name="remove" value="<?php print(S_REMOVE);?>" onclick="javascript: removeInterfaceRow(#{interfaceid});" />
</td>
</tr>
</script>

<script type="text/javascript">
function addInterfaceRow(hostInterface){
	var tpl = new Template(jQuery('#hostInterfaceRow').html());

	if(!isset("new", hostInterface)) hostInterface.newValue = "update";
	else hostInterface.newValue = hostInterface["new"];

	if(!isset("interfaceid", hostInterface)){

		hostInterface.interfaceid = jQuery("#hostInterfaces tr[id^=hostInterfaceRow]").length;
		while(jQuery("#interface_id_"+hostInterface.interfaceid).length){
			hostInterface.interfaceid++;
		}

		hostInterface.newValue = "create";
	}

	hostInterface.disabled = '';
	if(isset("items", hostInterface) && (hostInterface.items > 0)){
		hostInterface.disabled = 'disabled="disabled"';
	}

	if(!isset("ip", hostInterface) && !isset("dns", hostInterface)){
		if(jQuery("#hostInterfaces input[type=radio]:checked").first().val() == "0"){
			hostInterface.useip = 0;
			hostInterface.dns = jQuery("#hostInterfaces input[id^=interface_dns]").first().val();
		}
		else{
			hostInterface.useip = 1;
			hostInterface.ip = jQuery("#hostInterfaces input[id^=interface_ip]").first().val();
		}
	}

	if(isset("useip", hostInterface)){
		if(hostInterface.useip == 0)
			hostInterface.checked_dns = 'checked="checked"';
		else
			hostInterface.checked_ip = 'checked="checked"';
	}
//SDJ(hostInterface);
	if(!isset('port', hostInterface)) hostInterface.port = '10050';

	hostInterface.checked_agent = 'checked="checked"';
	if(isset("type", hostInterface)){
		hostInterface.checked_agent = '';
		switch(hostInterface.type.toString()){
			case '<?php print(INTERFACE_TYPE_SNMP);?>': hostInterface.checked_snmp = 'checked="checked"'; break;
			case '<?php print(INTERFACE_TYPE_IPMI);?>': hostInterface.checked_ipmi = 'checked="checked"'; break;
			case '<?php print(INTERFACE_TYPE_JMX);?>': hostInterface.checked_jmx = 'checked="checked"'; break;
			case '<?php print(INTERFACE_TYPE_AGENT);?>':
			default: hostInterface.checked_agent = 'checked="checked"'; break;
		}
	}

	jQuery("#hostIterfacesFooter").before(tpl.evaluate(hostInterface));
	jQuery("#hostInterfaceRow_"+hostInterface.interfaceid)
		.find("div.jqueryinputset").buttonset().end()
		.find("#interface_type_"+hostInterface.interfaceid).find("label")
			.click({"hostInterface": hostInterface}, function(event){
				var portInput = jQuery("#port_"+event.data.hostInterface.interfaceid)[0];

				if(empty(portInput.value) || !(portInput.value == "10050" || portInput.value == "161" || portInput.value == "623" || portInput.value == "12345")) return true;

				var interfaceTypeId = event.currentTarget.htmlFor.toLowerCase();
				switch(true){
					case (interfaceTypeId.indexOf('agent') > -1): portInput.value = "10050"; break;
					case (interfaceTypeId.indexOf('snmp') > -1): portInput.value = "161"; break;
					case (interfaceTypeId.indexOf('ipmi') > -1): portInput.value = "623"; break;
					case (interfaceTypeId.indexOf('jmx') > -1): portInput.value = "12345"; break;
				}
			}).end();
}

function removeInterfaceRow(hostInterfaceId){
	jQuery('#hostInterfaceRow_'+hostInterfaceId).remove();
	jQuery("#hostIterfaces").accordion('resize');
}

jQuery(document).ready(function(){

	// radio button of inventory modes was clicked
	jQuery("div.jqueryinputset input[name=inventory_mode]").click(function(){
		// action depending on which button was clicked
		var inventoryFields = jQuery("#inventorylist :input:gt(2)");
		switch(jQuery(this).val()){
			case '<?php echo HOST_INVENTORY_DISABLED ?>':
				inventoryFields.attr("disabled", "disabled"); // disabling all input fields
				jQuery('.populating_item').hide();
			break;
			case '<?php echo HOST_INVENTORY_MANUAL ?>':
				inventoryFields.removeAttr("disabled"); // enabling all input fields (if they were disabled)
				jQuery('.populating_item').hide();
			break;
			case '<?php echo HOST_INVENTORY_AUTOMATIC ?>':
				// disabling all input fields
				inventoryFields.removeAttr("disabled");
				inventoryFields.filter('.linked_to_item').attr("disabled", "disabled"); // disabling all input fields
				jQuery('.populating_item').show();
			break;
		}

	});

	jQuery('#name').focus();
});

</script>
