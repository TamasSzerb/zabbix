<!-- Discovery Actions-->
<script type="text/x-jquery-tmpl" id="opGroupRowTPL">
<tr id="opGroupRow_#{groupid}">
<td>
	<input name="new_operation[opgroup][#{groupid}][groupid]" type="hidden" value="#{groupid}" />
	<span style="font-size: 1.1em; font-weight: bold;"> #{name} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpGroupRow(#{groupid});" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="opTemplateRowTPL">
<tr id="opTemplateRow_#{templateid}">
<td>
	<input name="new_operation[optemplate][#{templateid}][templateid]" type="hidden" value="#{templateid}" />
	<span style="font-size: 1.1em; font-weight: bold;"> #{host} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpTemplateRow(#{templateid});" />
</td>
</tr>
</script>
<!-- Trigger Actions-->
<script type="text/x-jquery-tmpl" id="opmsgUsrgrpRowTPL">
<tr id="opmsgUsrgrpRow_#{usrgrpid}">
<td>
	<input name="new_operation[opmessage_grp][#{usrgrpid}][usrgrpid]" type="hidden" value="#{usrgrpid}" />
	<span style="font-size: 1.1em; font-weight: bold;"> #{name} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpmsgUsrgrpRow(#{usrgrpid});" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="opmsgUserRowTPL">
<tr id="opmsgUserRow_#{userid}">
<td>
	<input name="new_operation[opmessage_usr][#{userid}][userid]" type="hidden" value="#{userid}" />
	<span style="font-size: 1.1em; font-weight: bold;"> #{alias} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpmsgUserRow(#{userid});" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="opCmdGroupRowTPL">
<tr id="opCmdGroupRow_#{groupid}">
<td>
	<input name="new_operation[opcommand_grp][#{groupid}][groupid]" type="hidden" value="#{groupid}" />
	<input name="new_operation[opcommand_grp][#{groupid}][name]" type="hidden" value="#{name}" />
	#{objectCaption}
	<span class="bold"> #{name} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpCmdRow(#{groupid}, 'groupid');" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="opCmdHostRowTPL">
<tr id="opCmdHostRow_#{hostid}">
<td>
	<input name="new_operation[opcommand_hst][#{hostid}][hostid]" type="hidden" value="#{hostid}" />
	<input name="new_operation[opcommand_hst][#{hostid}][host]" type="hidden" value="#{host}" />
	#{objectCaption}
	<span class="bold"> #{host} </span>
</td>
<td>
	<input type="button" class="input link_menu" name="remove" value="<?php print(_('Remove'));?>" onclick="javascript: removeOpCmdRow(#{hostid}, 'hostid');" />
</td>
</tr>
</script>

<script type="text/x-jquery-tmpl" id="opcmdEditFormTPL">
<div id="opcmdEditForm" class="objectgroup border_dotted ui-corner-all">
<table class="formElementTable"><tbody>
<tr>
	<td> <?php print(_('Target')); ?> </td>
	<td>
		<select name="opCmdTarget" class="input select">
			<option value="0"><?php print(_('Current host')); ?></option>
			<option value="1"><?php print(_('Host')); ?></option>
			<option value="2"><?php print(_('Host group')); ?></option>
		</select>
		<div id="opCmdTargetSelect" class="inlineblock">
			<input name="action" type="hidden" value="#{action}" />
			<input name="opCmdId" type="hidden" value="#{opcmdid}" />
			<input name="opCmdTargetObjectId" id="opCmdTargetObjectId" type="hidden" value="#{objectid}" />
			<input name="opCmdTargetObjectName" id="opCmdTargetObjectName" type="text" class="input text" value="#{name}" readonly="readonly" size="30"/>
			<input type="button" class="input link_menu" name="select" value="<?php print(_('select'));?>" />
		</div>
	</td>
</tr>
<tr>
	<td colspan="2">
		<input type="button" class="input link_menu" name="save" value="#{operationName}" />
		&nbsp;<input type="button" class="input link_menu" name="cancel" value="<?php print(_('Cancel')); ?>" />
	</td>
</tr>
</tbody></table>
</div>
</script>

<script type="text/x-jquery-tmpl" id="operationTypesTPL">
<!-- Script -->
<tr id="operationTypeScriptElements" class="hidden">
<td>
	<?php echo(_('Execute on')); ?>
</td>
<td>
	<div class="objectgroup inlineblock border_dotted ui-corner-all" id="uniqList">
		<div>
			<input type="radio" id="execute_on_agent" name="execute_on" value="0" class="input radio">
			<label for="execute_on_agent"><?php echo(_('Zabbix agent')); ?></label>
		</div>

		<div>
			<input type="radio" id="execute_on_server" name="execute_on" value="1" class="input radio">
			<label for="execute_on_server"><?php echo(_('Zabbix server')); ?></label>
		</div>
	</div>
</td>
</tr>

</script>



<script type="text/javascript">
//<!--<![CDATA[
function addPopupValues(list){
	for(var i=0; i < list.values.length; i++){
		if(empty(list.values[i])) continue;
		var value = list.values[i];

		switch(list.object){
			case 'userid':
				if(jQuery("#opmsgUserRow_"+value.userid).length) continue;

				var tpl = new Template(jQuery('#opmsgUserRowTPL').html());
				jQuery("#opmsgUserListFooter").before(tpl.evaluate(value));
				break;
			case 'usrgrpid':
				if(jQuery("#opmsgUsrgrpRow_"+value.usrgrpid).length) continue;

				var tpl = new Template(jQuery('#opmsgUsrgrpRowTPL').html());
				jQuery("#opmsgUsrgrpListFooter").before(tpl.evaluate(value));
				break;
			case 'dsc_groupid':
				if(jQuery("#opGroupRow_"+value.groupid).length) continue;

				var tpl = new Template(jQuery('#opGroupRowTPL').html());
				jQuery("#opGroupListFooter").before(tpl.evaluate(value));
				break;
			case 'dsc_templateid':
				if(jQuery("#opTemplateRow_"+value.templateid).length) continue;

				var tpl = new Template(jQuery('#opTemplateRowTPL').html());
				jQuery("#opTemplateListFooter").before(tpl.evaluate(value));
				break;
			case 'groupid':
				var tpl = new Template(jQuery('#opCmdGroupRowTPL').html());

				value.objectCaption = "<?php print(_('Host group').': '); ?>";

				if(jQuery("#opCmdGroupRow_"+value.groupid).length == 0){
					jQuery("#opCmdListFooter").before(tpl.evaluate(value));
				}
				break;
			case 'hostid':
				var tpl = new Template(jQuery('#opCmdHostRowTPL').html());

				if(value.hostid.toString() != '0')
					value.objectCaption = "<?php print(_('Host').': '); ?>";
				else
					value.host = "<?php print(_('Current host')); ?>";

				if(jQuery("#opCmdHostRow_"+value.hostid).length == 0){
					jQuery("#opCmdListFooter").before(tpl.evaluate(value));
				}
				break;
		}
	}
}

function removeOpmsgUsrgrpRow(usrgrpid){
	jQuery('#opmsgUsrgrpRow_'+usrgrpid).remove();
}
function removeOpmsgUserRow(userid){
	jQuery('#opmsgUserRow_'+userid).remove();
}
function removeOpGroupRow(groupid){
	jQuery('#opGroupRow_'+groupid).remove();
}
function removeOpTemplateRow(tplid){
	jQuery('#opTemplateRow_'+tplid).remove();
}

function removeOpCmdRow(opCmdRowId, object){
	if(object == 'groupid'){
		jQuery('#opCmdGroupRow_'+opCmdRowId).remove();
	}
	else{
		jQuery('#opCmdHostRow_'+opCmdRowId).remove();
	}
}

function showOpCmdForm(opCmdId){
	if(jQuery("#opcmdEditForm").length > 0){
		if(!closeOpCmdForm()) return true;
	}

	var objectTPL = {};

	objectTPL.action = 'create';
	objectTPL.opcmdid = 'new';
	objectTPL.objectid = 0;
	objectTPL.name = '';
	objectTPL.target = 0;
	objectTPL.operationName = '<?php print(_('Add'));?>';

	var tpl = new Template(jQuery('#opcmdEditFormTPL').html());
	jQuery("#opCmdList").after(tpl.evaluate(objectTPL));

// actions
	jQuery('#opcmdEditForm')
		.find('#opCmdTargetSelect').toggle((objectTPL.target != 0)).end()
		.find('input[name="save"]').click(saveOpCmdForm).end()
		.find('input[name="cancel"]').click(closeOpCmdForm).end()
		.find('input[name="select"]').click(selectOpCmdTarget).end()
		.find('select[name="opCmdTarget"]').val(objectTPL.target).change(changeOpCmdTarget);
}


function saveOpCmdForm(){
	var objectForm = jQuery('#opcmdEditForm');

	var object = {};
	object.action = jQuery(objectForm).find('input[name="action"]').val();
	object.target = jQuery(objectForm).find('select[name="opCmdTarget"]').val();

	if(object.target.toString() == '2'){
		object.object = 'groupid';
		object.opcommand_grpid = jQuery(objectForm).find('input[name="opCmdId"]').val();
		object.groupid = jQuery(objectForm).find('input[name="opCmdTargetObjectId"]').val();
		object.name = jQuery(objectForm).find('input[name="opCmdTargetObjectName"]').val();

		if(empty(object.name)){
			alert("<?php print(_('You did not specify host group for operation.')); ?>");
			return true;
		}

		if(object.opcommand_grpid == 'new') delete(object["opcommand_grpid"]);
	}
	else{
		object.object = 'hostid';
		object.opcommand_hstid = jQuery(objectForm).find('input[name="opCmdId"]').val();
		object.hostid = jQuery(objectForm).find('input[name="opCmdTargetObjectId"]').val();
		object.host = jQuery(objectForm).find('input[name="opCmdTargetObjectName"]').val();

		if((object.target.toString() != '0') && empty(object.host)){
			alert("<?php print(_('You did not specify host for operation.')); ?>");
			return true;
		}

		if(object.opcommand_hstid == 'new') delete(object["opcommand_hstid"]);
	}

	addPopupValues({'object': object.object, 'values': [object]});
	jQuery(objectForm).remove();
}

function selectOpCmdTarget(){
	var target = jQuery('#opcmdEditForm select[name="opCmdTarget"]').val();
	if(target.toString() == '2')
		PopUp("popup.php?dstfrm=action.edit.php&srctbl=host_group&srcfld1=groupid&srcfld2=name&dstfld1=opCmdTargetObjectId&dstfld2=opCmdTargetObjectName&writeonly=1",480,480);
	else
		PopUp("popup.php?dstfrm=action.edit.php&srctbl=hosts&srcfld1=hostid&srcfld2=host&dstfld1=opCmdTargetObjectId&dstfld2=opCmdTargetObjectName&writeonly=1",780,480);
}

function changeOpCmdTarget(){
	jQuery('#opcmdEditForm')
		.find('#opCmdTargetSelect').toggle((jQuery('#opcmdEditForm select[name="opCmdTarget"]').val() > 0)).end()
		.find('input[name="opCmdTargetObjectId"]').val(0).end()
		.find('input[name="opCmdTargetObjectName"]').val('').end();
}

function closeOpCmdForm(){
//	if(Confirm("<?php print(_('Close currently opened remote command details without saving?')); ?>")){
		jQuery('#opCmdDraft').attr('id', jQuery('#opCmdDraft').attr('origid'));
		jQuery("#opcmdEditForm").remove();
		return true;
//	}
	return false;
}

function showOpTypeForm(){
	if(jQuery('#new_operation_opcommand_type').length == 0) return;

	var currentOpType = jQuery('#new_operation_opcommand_type').val();

	var opTypeFields = {
		'class_opcommand_userscript': [ZBX_SCRIPT_TYPES.userscript],
		'class_opcommand_execute_on': [ZBX_SCRIPT_TYPES.script],
		'class_opcommand_port': [ZBX_SCRIPT_TYPES.ssh,ZBX_SCRIPT_TYPES.telnet],
		'class_opcommand_command': [ZBX_SCRIPT_TYPES.script,ZBX_SCRIPT_TYPES.ipmi,ZBX_SCRIPT_TYPES.ssh,ZBX_SCRIPT_TYPES.telnet],
		'class_authentication_method': [ZBX_SCRIPT_TYPES.ssh],
		'class_authentication_username': [ZBX_SCRIPT_TYPES.ssh,ZBX_SCRIPT_TYPES.telnet],
		'class_authentication_publickey': [],
		'class_authentication_privatekey': [],
		'class_authentication_password': [ZBX_SCRIPT_TYPES.ssh,ZBX_SCRIPT_TYPES.telnet]
	}

	var showFields = [];
	for(var fieldClass in opTypeFields){
		jQuery("#operationlist ."+fieldClass).toggleClass("hidden", true).attr("disabled", "disabled");

		for(var f=0; f < opTypeFields[fieldClass].length; f++)
			if(currentOpType == opTypeFields[fieldClass][f]) showFields.push(fieldClass);
	}

	for(var f=0; f < showFields.length; f++){
		if(showFields[f] == 'class_authentication_method') showOpTypeAuth();
		jQuery("#operationlist ."+showFields[f]).toggleClass("hidden", false).removeAttr("disabled");
	}
}

function showOpTypeAuth(){
	var currentOpTypeAuth = parseInt(jQuery('#new_operation_opcommand_authtype').val(), 10);

	if(currentOpTypeAuth === <?php echo(ITEM_AUTHTYPE_PASSWORD); ?>){
		jQuery('#operationlist .class_authentication_publickey').toggleClass("hidden", true).attr("disabled", "disabled");
		jQuery('#operationlist .class_authentication_privatekey').toggleClass("hidden", true).attr("disabled", "disabled");
	}
	else{
		jQuery('#operationlist .class_authentication_publickey').toggleClass("hidden", false).removeAttr("disabled");
		jQuery('#operationlist .class_authentication_privatekey').toggleClass("hidden", false).removeAttr("disabled");
	}
}

var ZBX_SCRIPT_TYPES = {};
ZBX_SCRIPT_TYPES['script'] = <?php echo ZBX_SCRIPT_TYPE_SCRIPT; ?>;
ZBX_SCRIPT_TYPES['ipmi'] = <?php echo ZBX_SCRIPT_TYPE_IPMI; ?>;
ZBX_SCRIPT_TYPES['telnet'] = <?php echo ZBX_SCRIPT_TYPE_TELNET; ?>;
ZBX_SCRIPT_TYPES['ssh'] = <?php echo ZBX_SCRIPT_TYPE_SSH; ?>;
ZBX_SCRIPT_TYPES['userscript'] = <?php echo ZBX_SCRIPT_TYPE_USER_SCRIPT; ?>;

jQuery(document).ready(function(){
	setTimeout(function(){jQuery("#name").focus()}, 10);
//	jQuery("#name").focus();

// Clone button
	jQuery("#clone").click(function(){
		jQuery("#actionid, #delete, #clone").remove();

		jQuery("#cancel").addClass('ui-corner-left');
		jQuery("#name").focus();
	});

// new operation form command type
	showOpTypeForm();

	jQuery('#select_opcommand_script').click(function(){
		PopUp("popup.php?dstfrm=action.edit.php&srctbl=scripts&srcfld1=scriptid&srcfld2=name&dstfld1=new_operation_opcommand_scriptid&dstfld2=new_operation_opcommand_script",480,720);
	})
});

//]]> -->
</script>
