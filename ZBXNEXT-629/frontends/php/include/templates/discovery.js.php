<!-- Discovery -->
<script type="text/x-jquery-tmpl" id="dcheckRowTPL">
<tr id="dcheckRow_#{dcheckid}">
<td>
	<input name="dchecks[#{dcheckid}][dcheckid]" type="hidden" value="#{dcheckid}" />
	<input name="dchecks[#{dcheckid}][name]" type="hidden" value="#{name}" />
	<input name="dchecks[#{dcheckid}][type]" type="hidden" value="#{type}" />
	<input name="dchecks[#{dcheckid}][key_]" type="hidden" value="#{key_}" />
	<input name="dchecks[#{dcheckid}][ports]" type="hidden" value="#{ports}" />
	<input name="dchecks[#{dcheckid}][snmp_community]" type="hidden" value="#{snmp_community}" />
	<input name="dchecks[#{dcheckid}][snmpv3_securityname]" type="hidden" value="#{snmpv3_securityname}" />
	<input name="dchecks[#{dcheckid}][snmpv3_securitylevel]" type="hidden" value="#{snmpv3_securitylevel}" />
	<input name="dchecks[#{dcheckid}][snmpv3_authpassphrase]" type="hidden" value="#{snmpv3_authpassphrase}" />
	<input name="dchecks[#{dcheckid}][snmpv3_privpassphrase]" type="hidden" value="#{snmpv3_privpassphrase}" />
	<input name="dchecks[#{dcheckid}][uniq]" type="hidden" value="#{uniq}" />
	<span class="bold"> #{name} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeDCheckRow(#{dcheckid});" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="uniqRowTPL">
	<div id="uniqueness_criteria_row_#{dcheckid}">
	<input type="radio" id="uniqueness_criteria_#{dcheckid}" name="uniqueness_criteria" value="#{dcheckid}" class="input radio">
<label for="uniqueness_criteria_#{dcheckid}">#{name}</label>
</div>
</script>

<script type="text/x-jquery-tmpl" id="newDCheckTPL">
<div id="new_check_form">
<div class="objectgroup inlineblock border_dotted ui-corner-all">
<table class="formElementTable"><tbody>
<tr>
	<td><label for="new_check_type"><?php print(_('Check type')); ?></label></td>
	<td>
	<select id="new_check_type" name="new_check_type" class="input select"></select>
	</td>
</tr>
<tr id="newCheckPortsRow" class="hidden">
	<td><label for="new_check_ports"><?php print(_('Ports')); ?></label></td>
	<td><input type="text" id="new_check_ports" name="new_check_ports" value="" class="input text" size="16" maxlength="255"></td>
</tr>
<tr id="newCheckComunityRow" class="hidden">
	<td><label for="new_check_comunity"><?php print(_('SNMP Comunity')); ?></label></td>
	<td><input type="text" id="new_check_comunity" name="new_check_comunity" value="" class="input text" size="20" maxlength="255"></td>
</tr>
<tr id="newCheckKeyRow" class="hidden">
	<td><label for="new_check_key_"><?php print(_('SNMP Key')); ?></label></td>
	<td><input type="text" id="new_check_key_" name="new_check_key_" value="" class="input text" size="20" maxlength="255"></td>
</tr>
<tr id="newCheckSecNameRow" class="hidden">
	<td><label for="new_check_snmpv3_securityname"><?php print(_('SNMPv3 Security name')); ?></label></td>
	<td><input type="text" id="new_check_snmpv3_securityname" name="new_check_snmpv3_securityname" value="" class="input text" size="20" maxlength="64"></td>
</tr>
<tr id="newCheckSecLevRow" class="hidden">
	<td><label for="new_check_snmpv3_securitylevel"><?php print(_('SNMPv3 Security level')); ?></label></td>
	<td><select id="new_check_snmpv3_securitylevel" name="new_check_snmpv3_securitylevel" class="input select" size="1">
		<option value="0"><?php print('noAuthNoPriv'); ?> </option>
		<option value="1"><?php print('authNoPriv'); ?> </option>
		<option value="2"><?php print('authPriv'); ?> </option>
	</select></td>
</tr>
<tr id="newCheckAuthPassRow" class="hidden">
	<td><label for="new_check_snmpv3_authpassphrase"><?php print(_('SNMPv3 auth passphrase')); ?></label></td>
	<td><input type="text" id="new_check_snmpv3_authpassphrase" name="new_check_snmpv3_authpassphrase" value="" class="input text" size="20" maxlength="64"></td>
</tr>
<tr id="newCheckPrivPassRow" class="hidden">
	<td><label for="new_check_snmpv3_authpassphrase"><?php print(_('SNMPv3 priv passphrase')); ?></label></td>
	<td><input type="text" id="new_check_snmpv3_privpassphrase" name="new_check_snmpv3_privpassphrase" value="" class="input text" size="20" maxlength="64"></td>
</tr>
</tbody></table>
<input type="button" id="add_new_dcheck" name="add_new_dcheck" value="<?php print(_('Add'));?>" class="input button link_menu">
&nbsp;&nbsp;<input type="button" id="cancel_new_dcheck" name="cancel_new_dcheck" value="<?php print(_('Cancel'));?>" class="input button link_menu">
</div>
</div>
</script>

<script type="text/javascript">
//<!--<![CDATA[
var ZBX_SVC_PORT = {
	'ssh': <?php print(SVC_SSH);?>,
	'ldap': <?php print(SVC_LDAP);?>,
	'smtp': <?php print(SVC_SMTP);?>,
	'ftp': <?php print(SVC_FTP);?>,
	'http': <?php print(SVC_HTTP);?>,
	'pop': <?php print(SVC_POP);?>,
	'nntp': <?php print(SVC_NNTP);?>,
	'imap': <?php print(SVC_IMAP);?>,
	'tcp': <?php print(SVC_TCP);?>,
	'agent': <?php print(SVC_AGENT);?>,
	'snmpv1': <?php print(SVC_SNMPv1);?>,
	'snmpv2': <?php print(SVC_SNMPv2);?>,
	'snmpv3': <?php print(SVC_SNMPv3);?>,
	'icmp': <?php print(SVC_ICMPPING);?>,
	'https': <?php print(SVC_HTTPS);?>,
	'telnet': <?php print(SVC_TELNET);?>
};

function discoveryCheckDefaultPort(service){
	service = service.toString();
	var defPorts = {};
	defPorts[ZBX_SVC_PORT.ssh] = '22';
	defPorts[ZBX_SVC_PORT.ldap] = '389';
	defPorts[ZBX_SVC_PORT.smtp] = '25';
	defPorts[ZBX_SVC_PORT.ftp] = '21';
	defPorts[ZBX_SVC_PORT.http] = '80';
	defPorts[ZBX_SVC_PORT.pop] = '110';
	defPorts[ZBX_SVC_PORT.nntp] = '119';
	defPorts[ZBX_SVC_PORT.imap] = '143';
	defPorts[ZBX_SVC_PORT.tcp] = '0';
	defPorts[ZBX_SVC_PORT.agent] = '10050';
	defPorts[ZBX_SVC_PORT.snmpv1] = '161';
	defPorts[ZBX_SVC_PORT.snmpv2] = '161';
	defPorts[ZBX_SVC_PORT.snmpv3] = '161';
	defPorts[ZBX_SVC_PORT.https] = '443';
	defPorts[ZBX_SVC_PORT.telnet] = '23';


	return isset(service, defPorts) ? defPorts[service] : 0;
}

function discoveryCheckTypeToString(svcPort){
	var defPorts = {};
	defPorts[ZBX_SVC_PORT.ftp] = "<?php echo _('FTP');?>";
	defPorts[ZBX_SVC_PORT.http] = "<?php echo _('HTTP');?>";
	defPorts[ZBX_SVC_PORT.https] = "<?php echo _('HTTPS');?>";
	defPorts[ZBX_SVC_PORT.imap] = "<?php echo _('IMAP');?>";
	defPorts[ZBX_SVC_PORT.tcp] = "<?php echo _('TCP');?>";
	defPorts[ZBX_SVC_PORT.ldap] = "<?php echo _('LDAP');?>";
	defPorts[ZBX_SVC_PORT.nntp] = "<?php echo _('NNTP');?>";
	defPorts[ZBX_SVC_PORT.pop] = "<?php echo _('POP');?>";
	defPorts[ZBX_SVC_PORT.snmpv1] = "<?php echo _('SNMPv1 agent');?>";
	defPorts[ZBX_SVC_PORT.snmpv2] = "<?php echo _('SNMPv2 agent');?>";
	defPorts[ZBX_SVC_PORT.snmpv3] = "<?php echo _('SNMPv3 agent');?>";
	defPorts[ZBX_SVC_PORT.smtp] = "<?php echo _('SMTP');?>";
	defPorts[ZBX_SVC_PORT.ssh] = "<?php echo _('SSH');?>";
	defPorts[ZBX_SVC_PORT.telnet] = "<?php echo _('Telnet');?>";
	defPorts[ZBX_SVC_PORT.agent] = "<?php echo _('Zabbix agent');?>";


	if(typeof(svcPort) == 'undefined')
		return defPorts;

	svcPort = parseInt(svcPort, 10);
	return isset(svcPort, defPorts) ? defPorts[svcPort] : _('Unknown');
}

function addPopupValues(list){
	var uniqTypeList = {};
	uniqTypeList[ZBX_SVC_PORT.agent] = true;
	uniqTypeList[ZBX_SVC_PORT.snmpv1] = true;
	uniqTypeList[ZBX_SVC_PORT.snmpv2] = true;
	uniqTypeList[ZBX_SVC_PORT.snmpv3] = true;

	for(var i=0; i < list.values.length; i++){
		if(empty(list.values[i])) continue;
		var value = list.values[i];

		switch(list.object){
			case 'dcheckid':
				if(jQuery("#dcheckRow_"+value.dcheckid).length) continue;

				var tpl = new Template(jQuery('#dcheckRowTPL').html());
				jQuery("#dcheckListFooter").before(tpl.evaluate(value));


				if(isset(parseInt(value.type, 10), uniqTypeList)){
					var tpl = new Template(jQuery('#uniqRowTPL').html());
					jQuery("#uniqList").append(tpl.evaluate(value));
				}

				break;
//			if(in_array($data['type'], array(SVC_AGENT, SVC_SNMPv1, SVC_SNMPv2, SVC_SNMPv3)))
//				$cmbUniquenessCriteria->addItem($id, $data['name']);
		}
	}
}

function removeDCheckRow(dcheckid){
	jQuery('#dcheckRow_'+dcheckid).remove();
	if(jQuery('#uniqueness_criteria_'+dcheckid).is(':checked')){
		console.log('sdf');
		jQuery('#uniqueness_criteria_1').attr('checked', 'checked');
	}
	jQuery('#uniqueness_criteria_row_'+dcheckid).remove();
}

function showNewCheckForm(e, dcheckType){
	if(jQuery('#new_check_form').length == 0){
		var tpl = new Template(jQuery('#newDCheckTPL').html());
		jQuery("#dcheckList").after(tpl.evaluate());

		jQuery("#new_check_type").change(updateNewDCheckType);
		jQuery("#new_check_snmpv3_securitylevel").change(updateNewDCheckSNMPType);
		jQuery("#add_new_dcheck").click(saveNewDCheckForm);
		jQuery("#cancel_new_dcheck").click(function(){ jQuery('#new_check_form').remove(); });

// Port name sorting
		var svcPorts = discoveryCheckTypeToString();
		var portNameSvcValue = {};
		var portNameOrder = new Array();
		for(var key in svcPorts){
			portNameOrder.push(svcPorts[key]);
			portNameSvcValue[svcPorts[key]] = key;
		}

		portNameOrder.sort();
// ---
		for(var i=0; i < portNameOrder.length; i++){
			var portName = portNameOrder[i];
			jQuery('#new_check_type').append(jQuery('<option>').attr({'value': portNameSvcValue[portName]}).text(portName));
		}

	}

	updateNewDCheckType(e);
}

function updateNewDCheckType(e){
	var dcheckType = parseInt(jQuery("#new_check_type").val(), 10);

	var keyRowTypes = {}
	keyRowTypes[ZBX_SVC_PORT.agent] = true;
	keyRowTypes[ZBX_SVC_PORT.snmpv1] = true;
	keyRowTypes[ZBX_SVC_PORT.snmpv2] = true;
	keyRowTypes[ZBX_SVC_PORT.snmpv3] = true;

	var ComRowTypes = {}
	ComRowTypes[ZBX_SVC_PORT.snmpv1] = true;
	ComRowTypes[ZBX_SVC_PORT.snmpv2] = true;

	var SecNameRowTypes = {};
	SecNameRowTypes[ZBX_SVC_PORT.snmpv3] = true;

	jQuery('#newCheckPortsRow').toggle((ZBX_SVC_PORT.icmp != dcheckType));
	jQuery('#newCheckKeyRow').toggle(isset(dcheckType, keyRowTypes));
	if(isset(dcheckType, keyRowTypes)){
		var caption = (dcheckType == ZBX_SVC_PORT.agent)? "<?php print(_('Key')); ?>" : "<?php print(_('SNMP OID')); ?>";
		jQuery('#newCheckKeyRow label').text(caption);
	}

	jQuery('#newCheckComunityRow').toggle(isset(dcheckType, ComRowTypes));
	jQuery('#newCheckSecNameRow').toggle(isset(dcheckType, SecNameRowTypes));
	jQuery('#newCheckSecLevRow').toggle(isset(dcheckType, SecNameRowTypes));

	if(ZBX_SVC_PORT.icmp != dcheckType)
		jQuery('#new_check_ports').val(discoveryCheckDefaultPort(dcheckType));

	updateNewDCheckSNMPType(e);
}

function updateNewDCheckSNMPType(e){
	var dcheckType = parseInt(jQuery("#new_check_type").val(), 10);
	var dcheckSecLevType = parseInt(jQuery("#new_check_snmpv3_securitylevel").val(), 10);

	var SecNameRowTypes = {};
	SecNameRowTypes[ZBX_SVC_PORT.snmpv3] = true;

	var showAuthPass = (isset(dcheckType, SecNameRowTypes) && ((dcheckSecLevType == <?php print(ITEM_SNMPV3_SECURITYLEVEL_AUTHNOPRIV); ?>) || (dcheckSecLevType == <?php print(ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV); ?>)));
	var showPrivPass = (isset(dcheckType, SecNameRowTypes) && (dcheckSecLevType == <?php print(ITEM_SNMPV3_SECURITYLEVEL_AUTHPRIV); ?>));

	jQuery('#newCheckAuthPassRow').toggle(showAuthPass);
	jQuery('#newCheckPrivPassRow').toggle(showPrivPass);

}

function saveNewDCheckForm(e){
	var formData = jQuery('#new_check_form').find('input,select').serializeJSON();

	var dCheck = {
		'dcheckid': jQuery("#dcheckList tr[id^=dcheckRow_]").length,
		'name': jQuery('#new_check_type :selected').text()
	};

	while(jQuery("#uniqueness_criteria_"+dCheck.dcheckid).length)
		dCheck.dcheckid++;

	for(var key in formData){
		var name = key.split('new_check_');
		if(name.length != 2) continue;

		dCheck[name[1]] = formData[key];
	}

	if(dCheck.ports != discoveryCheckDefaultPort(dCheck.type))
		dCheck.name += ' ('+dCheck.ports+')';

	if(!empty(dCheck.key_)) dCheck.name += ' "'+dCheck.key_+'"';


	addPopupValues({'object': 'dcheckid', 'values': [dCheck]});
	jQuery('#new_check_form').remove();
}

jQuery(document).ready(function(){
	setTimeout(function(){jQuery("#name").focus()}, 10);

	jQuery("#newCheck").click(showNewCheckForm);
// Clone button
	jQuery("#clone").click(function(){
		jQuery("#druleid, #delete, #clone").remove();

		jQuery("#cancel").addClass('ui-corner-left');
		jQuery("#name").focus();
	});
});

(function($){
	$.fn.serializeJSON = function(){
		var json = {};
		jQuery.map($(this).serializeArray(), function(n, i){
			json[n['name']] = n['value'];
		});
		return json;
	};
})(jQuery);

//]]> -->
</script>
