<script type="text/x-jquery-tmpl" id="mapElementFormTpl">
	<div class="floatWindow" style="display: none;">
		<form name="selementForm">
			<input type="hidden" id="selementid" name="selementid">
			<input type="hidden" id="elementid" name="elementid">
			<table id="elementFormTable" class="formtable" style="width:100%">
				<caption>asd</caption>
				<thead>
				<tr class="header">
					<td id="formDragHandler" colspan="2" class="form_row_first move"><?php echo _('Edit map element'); ?></td>
				</tr>
				</thead>
				</tbody>
				<tr>
					<td><label for="elementType"><?php echo _('Type'); ?></label></td>
					<td>
						<select size="1" class="input" name="elementtype" id="elementType">
							<option value="0"><?php echo _('Host'); ?></option>
							<option value="1"><?php echo _('Map'); ?></option>
							<option value="2"><?php echo _('Trigger'); ?></option>
							<option value="3"><?php echo _('Host group'); ?></option>
							<option value="4"><?php echo _('Image'); ?></option>
						</select>
					</td>
				</tr>
				<tr id="subtypeRow">
					<td><?php echo _('Show'); ?></td>
					<td>
						<input id="subtypeHostGroup" type="radio" class="input radio" name="elementsubtype" value="0" checked="checked">
						<label for="subtypeHostGroup"><?php echo _('Host group'); ?></label>
						<br />
						<input id="subtypeHostGroupElements" type="radio" class="input radio" name="elementsubtype" value="1">
						<label for="subtypeHostGroupElements"><?php echo _('Host group elements'); ?></label>
					</td>
				</tr>
				<tr id="areaTypeRow">
					<td><?php echo _('Area type'); ?></td>
					<td>
						<input id="areaTypeAuto" type="radio" class="input radio" name="areatype" value="0" checked="checked">
						<label for="areaTypeAuto"><?php echo _('Fit to map'); ?></label>
						<br />
						<input id="areaTypeCustom" type="radio" class="input radio" name="areatype" value="1">
						<label for="areaTypeCustom"><?php echo _('Custom size'); ?></label>
					</td>
				</tr>
				<tr id="areaSizeRow">
					<td><?php echo _('Area size'); ?></td>
					<td>
						<label for="areaSizeWidth"><?php echo _('Width'); ?></label>
						<input id="areaSizeWidth" type="text" class="input text" name="areasizewidth" value="200">
						<label for="areaSizeHeight"><?php echo _('Height'); ?></label>
						<input id="areaSizeHeight" type="text" class="input text" name="areasizeheight" value="200">
					</td>
				</tr>
				<tr id="areaPlacingRow">
					<td><label for="areaPlacing"><?php echo _('Placing algorithm'); ?></label></td>
					<td>
						<select id="areaPlacing" class="input">
							<option value="0"><?php echo _('Grid'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><label for="elementLabel"><?php echo _('Label'); ?></label></td>
					<td><textarea id="elementLabel" cols="56" rows="4" name="label" class="input"></textarea></td>
				</tr>
				<tr>
					<td><label for="label_location"><?php echo _('Label location'); ?></label></td>
					<td><select id="label_location" class="input" name="label_location">
						<option value="-1">-</option>
						<option value="0"><?php echo _('Bottom'); ?></option>
						<option value="1"><?php echo _('Left'); ?></option>
						<option value="2"><?php echo _('Right'); ?></option>
						<option value="3"><?php echo _('Top'); ?></option>
					</select></td>
				</tr>
				<tr id="hostGroupSelectRow">
					<td><?php echo _('Host group'); ?></td>
					<td>
						<input readonly="readonly" size="56" id="elementNameHostGroup" name="elementName" class="input">
						<span class="link" onclick="PopUp('popup.php?writeonly=1&dstfrm=selementForm&dstfld1=elementid&dstfld2=elementNameHostGroup&srctbl=host_group&srcfld1=groupid&srcfld2=name',450,450)"><?php echo _('Select'); ?></span>
					</td>
				</tr>
				<tr id="hostSelectRow">
					<td><?php echo _('Host'); ?></td>
					<td>
						<input readonly="readonly" size="56" id="elementNameHost" name="elementName" class="input">
						<span class="link" onclick="PopUp('popup.php?writeonly=1&real_hosts=1&dstfrm=selementForm&dstfld1=elementid&dstfld2=elementNameHost&srctbl=hosts&srcfld1=hostid&srcfld2=host',450,450)"><?php echo _('Select'); ?></span>
					</td>
				</tr>
				<tr id="triggerSelectRow">
					<td><?php echo _('Trigger'); ?></td>
					<td>
						<input readonly="readonly" size="56" id="elementNameTrigger" name="elementName" class="input">
						<span class="link" onclick="PopUp('popup.php?writeonly=1&dstfrm=selementForm&dstfld1=elementid&dstfld2=elementNameTrigger&srctbl=triggers&srcfld1=triggerid&srcfld2=description',450,450)"><?php echo _('Select'); ?></span>
					</td>
				</tr>
				<tr id="mapSelectRow">
					<td><?php echo _('Map'); ?></td>
					<td>
						<input readonly="readonly" size="56" id="elementNameMap" name="elementName" class="input">
						<span class="link" onclick="PopUp('popup.php?writeonly=1&dstfrm=selementForm&dstfld1=elementid&dstfld2=elementNameMap&srctbl=sysmaps&srcfld1=sysmapid&srcfld2=name&excludeids[]=#{sysmapid}',450,450)"><?php echo _('Select'); ?></span>
					</td>
				</tr>
				<tr>
					<td><label for="iconid_off"><?php echo _('Icon (default)'); ?></label></td>
					<td>
						<select class="input" name="iconid_off" id="iconid_off"></select>
					</td>
				</tr>
				<tr id="advancedIconsRow">
					<td><label for="advanced_icons"><?php echo _('Use advanced icons'); ?></label></td>
					<td><input type="checkbox" name="advanced_icons" id="advanced_icons" class="checkbox"></td>
				</tr>
				<tr id="iconProblemRow">
					<td><label for="iconid_on"><?php echo _('Icon (problem)'); ?></label></td>
					<td>
						<select class="input" name="iconid_on" id="iconid_on"></select>
					</td>
				</tr>
				<tr id="iconMainetnanceRow">
					<td><label for="iconid_maintenance"><?php echo _('Icon (maintenance)'); ?></label></td>
					<td>
						<select class="input" name="iconid_maintenance" id="iconid_maintenance"></select>
					</td>
				</tr>
				<tr id="iconDisabledRow">
					<td><label for="iconid_disabled"><?php echo _('Icon (disabled)'); ?></label></td>
					<td>
						<select class="input" name="iconid_disabled" id="iconid_disabled">
						</select>
					</td>
				</tr>
				<tr>
					<td><label for="x"><?php echo _('Coordinate X'); ?></label></td>
					<td><input id="x" onchange="if(isNaN(parseInt(this.value,10))) this.value = 0;" style="text-align: right;" maxlength="5" value="0" size="5" name="x" class="input"></td>
				</tr>
				<tr>
					<td><label for="y"><?php echo _('Coordinate Y'); ?></label></td>
					<td><input onchange="if(isNaN(parseInt(this.value,10))) this.value = 0;" style="text-align: right;" maxlength="5" value="0" size="5" id="y" name="y" class="input"></td>
				</tr>
				<tr class="edit">
					<td><?php echo _('Links'); ?></td>
					<td>
						<table>
							<tbody id="urlContainer">
							<tr class="header">
								<td><?php echo _('Name'); ?></td>
								<td><?php echo _('URL'); ?></td>
								<td></td>
							</tr>
							<tr id="urlfooter">
								<td colspan="3"><span id="newSelementUrl" class="link_menu" title="Add"><?php echo _('Add'); ?></span></td>
							</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr class="footer">
					<td colspan="2" class="form_row_last">
						<input id="elementApply" type="button" name="apply" value="Apply">
						<input id="elementRemove" type="button" name="remove" value="Remove">
						<input id="elementClose" type="button" name="close" value="Close">
					</td>
				</tr>
				</tbody>
			</table>
		</form>

		<div style="max-height: 128px; overflow-y: auto;">
			<table class="tableinfo">
				<thead>
				<tr class="header">
					<td><?php echo _('Link'); ?></td>
					<td><?php echo _('Element type'); ?></td>
					<td><?php echo _('Element name'); ?></td>
					<td><?php echo _('Link status indicator'); ?></td>
				</tr>
				</thead>
				<tbody id=formList></tbody>
			</table>
		</div>
	</div>
</script>

<script type="text/x-jquery-tmpl" id="mapMassFormTpl">
	<div class="floatWindow" style="display: none;">
		<form>
			<table class="formtable">
				<tbody>
				<tr class="header">
					<td id="massDragHandler" colspan="2" class="form_row_first move">
						<?php echo _('Mass update elements'); ?>&nbsp;
						(<span id="massElementCount"></span>&nbsp;<?php echo _('elements'); ?>)
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_label" id="chkboxLabel" class="checkbox" style="display: inline; ">
						<label for="chkboxLabel"><?php echo _('Label'); ?></label>
					</td>
					<td><textarea cols="56" rows="4" name="label" class="input"></textarea></td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_label_location" id="chkboxLabelLocation" class="checkbox" style="display: inline; ">
						<label for="chkboxLabelLocation"><?php echo _('Label location'); ?></label>
					</td>
					<td><select class="input" name="label_location">
							<option value="-1">-</option>
							<option value="0"><?php echo _('Bottom'); ?></option>
							<option value="1"><?php echo _('Left'); ?></option>
							<option value="2"><?php echo _('Right'); ?></option>
							<option value="3"><?php echo _('Top'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_iconid_off" id="chkboxMassIconidOff" class="checkbox" style="display: inline; ">
						<label for="chkboxMassIconidOff"><?php echo _('Icon (default)'); ?></label>
					</td>
					<td><select class="input" name="iconid_off" id="massIconidOff"></select></td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_iconid_on" id="chkboxMassIconidOn" class="checkbox" style="display: inline; ">
						<label for="chkboxMassIconidOn"><?php echo _('Icon (problem)'); ?></label>
					</td>
					<td><select class="input" name="iconid_on" id="massIconidOn"></select></td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_iconid_maintenance" id="chkboxMassIconidMaintenance" class="checkbox" style="display: inline; ">
						<label for="chkboxMassIconidMaintenance"><?php echo _('Icon (maintenance)'); ?></label>
					</td>
					<td><select class="input" name="iconid_maintenance" id="massIconidMaintenance"></select></td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_iconid_disabled" id="chkboxMassIconidDisabled" class="checkbox" style="display: inline; ">
						<label for="chkboxMassIconidDisabled"><?php echo _('Icon (disabled)'); ?></label>
					</td>
					<td><select class="input" name="iconid_disabled" id="massIconidDisabled"></select>
					</td>
				</tr>
				<tr class="footer">
					<td colspan="2" class="form_row_last">
						<input id="massApply" type="button" name="apply" value="Apply">
						<input id="massRemove" type="button" name="remove" value="Remove">
						<input id="massClose" type="button" name="close" value="Close">
					</td>
				</tr>
				</tbody>
			</table>
		</form>
		<div style="max-height: 128px; overflow-y: auto;">
			<table class="tableinfo">
			<tbody id="massList"></tbody>
			</table>
		</div>
	</div>
</script>

<script type="text/x-jquery-tmpl" id="mapLinkForm">
	<div id="linkForm" class="floatWindow" style="display: none;">
		<form name="linkForm">
			<input type="hidden" value="" id="linkid" name="linkid">

			<input name="link_triggers[12795][linktriggerid]" type="hidden" value="1" id="link_triggers[12795][linktriggerid]">
			<input name="link_triggers[12795][triggerid]" id="link_triggers[12795][triggerid]" type="hidden" value="12795">
			<input name="link_triggers[12795][desc_exp]" id="link_triggers[12795][desc_exp]" type="hidden" value="Zabbix server:/etc/passwd has been changed on server Zabbix server">

			<table class="formtable" style="width: 100%; ">
				<tbody>
				<tr class="header">
					<td colspan="2" class="form_row_first">
						<?php echo _('Edit connector'); ?>
					</td>
				</tr>
				<tr>
					<td><?php echo _('Label'); ?></td>
					<td><textarea cols="48" rows="4" name="linklabel" id="linklabel" class="input"></textarea></td>
				</tr>
				<tr>
					<td><?php echo _('Element 1'); ?></td>
					<td>
						<select class="input" name="selementid1" id="selementid1"></select>
					</td>
				</tr>
				<tr>
					<td><?php echo _('Element 2'); ?></td>
					<td>
						<select class="input" name="selementid2" id="selementid2"></select>
					</td>
				</tr>
				<tr class="edit">
					<td><?php echo _('Link indicators'); ?></td>
					<td>
						<table id="linktriggers" class="tableinfo">
							<tbody>
							<tr class="header">
								<td><?php echo _('Triggers'); ?></td>
								<td><?php echo _('Type'); ?></td>
								<td><?php echo _('Colour'); ?></td>
								<td></td>
							</tr>
							<tr>
								<td>
									<span>Zabbix server:/etc/passwd has been changed on server Zabbix server</span>
								</td>
								<td>
									<select id="link_triggers[12795][drawtype]" name="link_triggers[12795][drawtype]" class="input">
										<option value="0"><?php echo _('Line'); ?></option>
										<option value="2"><?php echo _('Bold line'); ?></option>
										<option value="3"><?php echo _('Dot'); ?></option>
										<option value="4"><?php echo _('Dashed line'); ?></option>
									</select>
								</td>
								<td>
									<input style="margin-top: 0px; margin-bottom: 0px;" onchange="set_color_by_name('link_triggers[12795][color]',this.value)" maxlength="6" value="DD0000" size="7" id="link_triggers[12795][color]" name="link_triggers[12795][color]" class="input">
									<div title="#DD0000" id="lbl_link_triggers[12795][color]" name="lbl_link_triggers[12795][color]" class="pointer" style="margin-left: 2px; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: black; border-right-color: black; border-bottom-color: black; border-left-color: black; display: inline; width: 10px; height: 10px; text-decoration: none; background-color: rgb(221, 0, 0); ">&nbsp;&nbsp;&nbsp;</div>
								</td>
								<td>
									<input type="button" name="Remove" value="Remove" class="input button link_menu">
								</td>
							</tr>
							</tbody>
						</table>
						<input type="button" name="Add" value="Add" class="input button link_menu">
					</td>
				</tr>
				<tr>
					<td><?php echo _('Type (OK)'); ?></td>
					<td >
						<select size="1" class="input" name="drawtype" id="drawtype">
							<option value="0"><?php echo _('Line'); ?></option>
							<option value="2"><?php echo _('Bold line'); ?></option>
							<option value="3"><?php echo _('Dot'); ?></option>
							<option value="4"><?php echo _('Dashed line'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td><?php echo _('Colour (OK)'); ?></td>
					<td>
						<input style="margin-top: 0px; margin-bottom: 0px;" onchange="set_color_by_name('color',this.value)" maxlength="6" value="000055" size="7" id="color" name="color" class="input">
						<div title="#000055" id="lbl_color" name="lbl_color" class="pointer" style="margin-left: 2px; border-top-width: 1px; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 1px; border-top-style: solid; border-right-style: solid; border-bottom-style: solid; border-left-style: solid; border-top-color: black; border-right-color: black; border-bottom-color: black; border-left-color: black; display: inline; width: 10px; height: 10px; text-decoration: none; background-color: rgb(0, 204, 0); ">&nbsp;&nbsp;&nbsp;</div>
					</td>
				</tr>
				<tr class="footer">
					<td colspan="2" class="form_row_last">
						<input type="button" name="apply" class="input button shadow" value="Apply">
						<input type="button" name="remove" class="input button shadow" value="Remove">
						<input type="button" name="close" class="input button shadow" value="Close">
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>
</script>

<script type="text/x-jquery-tmpl" id="mapMassFormListRow">
	<tr>
		<td>#{elementType}</td>
		<td>#{elementName}</td>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="mapElementsFormListRow">
	<tr class="even_row">
		<td><span class="link" onclick="jQuery('#linkForm').toggle();">#{linkName}</span></td>
		<td>#{elementType}</td>
		<td>#{elementName}</td>
		<td>#{linkIndicators}<br>
		</td>
	</tr>
</script>


<script type="text/x-jquery-tmpl" id="selementFormUrls">
	<tr id="urlrow_#{selementurlid}">
	<td><input class="input" name="urls[#{selementurlid}][name]" type="text" size="16" value="#{name}"></td>
	<td><input class="input" name="urls[#{selementurlid}][url]" type="text" size="32" value="#{url}"></td>
	<td><span class="link_menu" onclick="jQuery('#urlrow_#{selementurlid}').remove();"><?php echo _('Remove'); ?></span></td>
	</tr>
</script>
