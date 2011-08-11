<script type="text/x-jquery-tmpl" id="mapElementFormTpl">
	<div class="floatWindow" style="display: none;">
		<form id="selementForm" name="selementForm">
			<input type="hidden" id="elementid" name="elementid">
			<table id="elementFormTable" class="formtable" style="width:100%" rules="groups" frame="border">
				<thead>
				<tr class="header">
					<td id="formDragHandler" colspan="2" class="form_row_first move"><?php echo _('Edit map element'); ?></td>
				</tr>
				</thead>
				<tbody>
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
						<input id="areaSizeWidth" type="text" class="input text" name="width" value="200">
						<label for="areaSizeHeight"><?php echo _('Height'); ?></label>
						<input id="areaSizeHeight" type="text" class="input text" name="height" value="200">
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
						<span class="link" onclick="PopUp('popup.php?writeonly=1&real_hosts=1&dstfrm=selementForm&dstfld1=elementid&dstfld2=elementNameHost&srctbl=hosts&srcfld1=hostid&srcfld2=name',450,450)"><?php echo _('Select'); ?></span>
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
				</tbody>

				<tbody class="grouped">
				<tr id="useIconMapRow">
					<td><label for="use_iconmap" id=use_iconmapLabel><?php echo _('Automatic icon selection'); ?></label></td>
					<td><input type="checkbox" name="use_iconmap" id="use_iconmap" class="checkbox"></td>
				</tr>
				<tr>
					<td><label for="iconid_off"><?php echo _('Icon (default)'); ?></label></td>
					<td>
						<select class="input" name="iconid_off" id="iconid_off"></select>
					</td>
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
				</tbody>

				<tbody>
				<tr>
					<td><label for="x"><?php echo _('Coordinate X'); ?></label></td>
					<td><input id="x" maxlength="5" value="0" size="5" name="x" class="input"></td>
				</tr>
				<tr>
					<td><label for="y"><?php echo _('Coordinate Y'); ?></label></td>
					<td><input maxlength="5" value="0" size="5" id="y" name="y" class="input"></td>
				</tr>
				<tr>
					<td colspan="2">
						<fieldset>
							<legend><?php echo _('Links'); ?></legend>
							<table class="gridTable">
								<thead>
								<tr>
									<td><?php echo _('Name'); ?></td>
									<td><?php echo _('URL'); ?></td>
									<td></td>
								</tr>
								</thead>
								<tbody id="urlContainer"></tbody>
								<tfoot>
								<tr>
									<td colspan="3"><span id="newSelementUrl" class="link_menu"><?php echo _('Add'); ?></span></td>
								</tr>
								</tfoot>
							</table>
						</fieldset>
					</td>
				</tr>
				<tr class="footer">
					<td colspan="2" class="form_row_last">
						<input id="elementApply" type="button" name="apply" value="<?php echo _('Apply'); ?>">
						<input id="elementRemove" type="button" name="remove" value="<?php echo _('Remove'); ?>">
						<input id="elementClose" type="button" name="close" value="<?php echo _('Close'); ?>">
					</td>
				</tr>
				</tbody>
			</table>
		</form>

		<div id="mapLinksContainer" style="max-height: 128px; overflow-y: scroll; overflow-x: hidden; display: none;">
			<table class="tableinfo">
				<thead>
				<tr class="header">
					<td></td>
					<td><?php echo _('Element type'); ?></td>
					<td><?php echo _('Element name'); ?></td>
					<td><?php echo _('Link status indicator'); ?></td>
				</tr>
				</thead>
				<tbody id=linksList></tbody>
			</table>
		</div>

		<form id="linkForm" name="linkForm" style="display: none;">
			<input type="hidden" name="selementid1">

			<table class="formtable" style="width: 100%;">
				<tbody>
				<tr>
					<td><label for="linklabel"><?php echo _('Label'); ?></label></td>
					<td><textarea cols="48" rows="4" name="label" id="linklabel" class="input"></textarea></td>
				</tr>
				<tr>
					<td><label for="selementid2"><?php echo _('Connect to'); ?></label></td>
					<td><select class="input" name="selementid2" id="selementid2"></select></td>
				</tr>
				<tr>
					<td><label for="drawtype"><?php echo _('Type (OK)'); ?></label></td>
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
					<td><label for="color"><?php echo _('Colour (OK)'); ?></label></td>
					<td>
						<input maxlength="6" size="7" id="color" name="color" class="input colorpicker">
						<div id="lbl_color" class="pointer colorpickerLabel">&nbsp;</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<fieldset>
							<legend><?php echo _('Link indicators'); ?></legend>
							<table class="gridTable">
								<thead>
								<tr>
									<td><?php echo _('Triggers'); ?></td>
									<td><?php echo _('Type'); ?></td>
									<td><?php echo _('Colour'); ?></td>
									<td></td>
								</tr>
								</thead>
								<tbody id="linkTriggerscontainer"></tbody>
								<tfoot>
								<tr>
									<td colspan="4">
										<input type="button" name="Add" value="<?php echo _('Add'); ?>" class="input button link_menu" onclick="PopUp('popup.php?srctbl=triggers&srcfld1=triggerid&real_hosts=1&reference=linktrigger&multiselect=1&writeonly=1');">
									</td>
								</tr>
								</tfoot>
							</table>
						</fieldset>
					</td>
				</tr>
				<tr class="footer">
					<td colspan="2" class="form_row_last">
						<input id="formLinkApply" type="button" value="<?php echo _('Apply'); ?>">
						<input id="formLinkRemove" type="button" value="<?php echo _('Remove'); ?>">
						<input id="formLinkClose" type="button" value="<?php echo _('Close'); ?>">
					</td>
				</tr>
				</tbody>
			</table>
		</form>
	</div>
</script>

<script type="text/x-jquery-tmpl" id="mapMassFormTpl">
	<div class="floatWindow" style="display: none;">
		<form id="massForm">
			<table class="formtable">
				<tbody>
				<tr class="header">
					<td id="massDragHandler" colspan="2" class="form_row_first move">
						<?php echo _('Mass update elements'); ?>&nbsp;
						(<span id="massElementCount"></span>&nbsp;<?php echo _('elements'); ?>)
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php echo _('Selected elements'); ?>:
						<div style="border: 1px dotted black; margin-bottom: 5px; height: 128px; overflow-y: scroll;">
							<table class="tableinfo">
								<tbody id="massList"></tbody>
							</table>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_label" id="chkboxLabel" class="checkbox" style="display: inline; ">
						<label for="chkboxLabel"><?php echo _('Label'); ?></label>
					</td>
					<td><textarea id="massLabel" cols="56" rows="4" name="label" class="input"></textarea></td>
				</tr>
				<tr>
					<td>
						<input type="checkbox" name="chkbox_label_location" id="chkboxLabelLocation" class="checkbox" style="display: inline; ">
						<label for="chkboxLabelLocation"><?php echo _('Label location'); ?></label>
					</td>
					<td><select id="massLabelLocation" class="input" name="label_location">
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
						<input id="massApply" type="button" name="apply" value="<?php echo _('Apply'); ?>">
						<input id="massRemove" type="button" name="remove" value="<?php echo _('Remove'); ?>">
						<input id="massClose" type="button" name="close" value="<?php echo _('Close'); ?>">
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

<script type="text/x-jquery-tmpl" id="mapLinksRow">
	<tr>
		<td><span class="link_menu openlink" data-linkid="#{linkid}"><?php echo _('Edit'); ?></span></td>
		<td>#{elementType}</td>
		<td>#{elementName}</td>
		<td class="pre">#{linktriggers}</td>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="linkTriggerRow">
	<tr id="linktrigger_#{linktriggerid}">
		<td>#{desc_exp}</td>
		<td>
			<input type="hidden" name="linktrigger_#{linktriggerid}_desc_exp" value="#{desc_exp}" />
			<input type="hidden" name="linktrigger_#{linktriggerid}_triggerid" value="#{triggerid}" />
			<input type="hidden" name="linktrigger_#{linktriggerid}_linktriggerid" value="#{linktriggerid}" />
			<select id="linktrigger_#{linktriggerid}_drawtype" name="linktrigger_#{linktriggerid}_drawtype" class="input">
				<option value="0"><?php echo _('Line'); ?></option>
				<option value="2"><?php echo _('Bold line'); ?></option>
				<option value="3"><?php echo _('Dot'); ?></option>
				<option value="4"><?php echo _('Dashed line'); ?></option>
			</select>
		</td>
		<td>
			<input maxlength="6" value="#{color}" size="7" id="linktrigger_#{linktriggerid}_color" name="linktrigger_#{linktriggerid}_color" class="input colorpicker">
			<div id="lbl_linktrigger_#{linktriggerid}_color" class="pointer colorpickerLabel">&nbsp;</div>
		</td>
		<td>
			<span class="link_menu triggerRemove" data-linktriggerid="#{linktriggerid}""><?php echo _('Remove'); ?></span>
		</td>
	</tr>
</script>

<script type="text/x-jquery-tmpl" id="selementFormUrls">
	<tr id="urlrow_#{selementurlid}" class="even_row">
		<td><input class="input" name="url_#{selementurlid}_name" type="text" size="16" value="#{name}"></td>
		<td><input class="input" name="url_#{selementurlid}_url" type="text" size="32" value="#{url}"></td>
		<td><span class="link_menu" onclick="jQuery('#urlrow_#{selementurlid}').remove();"><?php echo _('Remove'); ?></span></td>
	</tr>
</script>
