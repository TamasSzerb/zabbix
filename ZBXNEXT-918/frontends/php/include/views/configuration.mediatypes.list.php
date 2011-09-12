<?php
/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
$mediaTypeWidget = new CWidget();

// create new media type button
$createForm = new CForm('get');
$createForm->addItem(new CSubmit('form', _('Create media type')));
$mediaTypeWidget->addPageHeader(_('CONFIGURATION OF MEDIA TYPES'), $createForm);

// header
$numRows = new CDiv();
$numRows->setAttribute('name','numrows');
$mediaTypeWidget->addHeader(_('Media types'));
$mediaTypeWidget->addHeader($numRows);

// create form
$mediaTypeForm = new CForm();
$mediaTypeForm->setName('frm_media_types');

// create table
$mediaTypeTable = new CTableInfo(_('No media types defined'));
$mediaTypeTable->setHeader(array(
	new CCheckBox('all_media_types', null, "checkAll('".$mediaTypeForm->getName()."', 'all_media_types', 'mediatypeids');"),
	make_sorting_header(_('Description'),'description'),
	make_sorting_header(_('Type'),'type'),
	make_sorting_header(_('Used in actions'),'usedInActions'),
	_('Details')
));

// append data to table
foreach ($this->data['mediatypes'] as $mediatype) {
	switch ($mediatype['type']) {
		case MEDIA_TYPE_EMAIL:
			$details =
			_('SMTP server').': "'.$mediatype['smtp_server'].'", '.
			_('SMTP helo').': "'.$mediatype['smtp_helo'].'", '.
			_('SMTP email').': "'.$mediatype['smtp_email'].'"';
			break;
		case MEDIA_TYPE_EXEC:
			$details = _('Script name').': "'.$mediatype['exec_path'].'"';
			break;
		case MEDIA_TYPE_SMS:
			$details = _('GSM modem').': "'.$mediatype['gsm_modem'].'"';
			break;
		case MEDIA_TYPE_JABBER:
			$details = _('Jabber identifier').': "'.$mediatype['username'].'"';
			break;
		case MEDIA_TYPE_EZ_TEXTING:
			$details = _('Username').': "'.$mediatype['username'].'"';
			break;
		default:
			$details = '';
	}

	$actionLinks = array();
	if (!empty($mediatype['listOfActions'])) {
		order_result($mediatype['listOfActions'], 'name');
		foreach ($mediatype['listOfActions'] as $action) {
			$actionLinks[] = new CLink($action['name'], 'actionconf.php?form=edit&actionid='.$action['actionid']);
			$actionLinks[] = ', ';
		}
		array_pop($actionLinks);
	}
	else {
		$actionLinks = '-';
	}

	// append row
	$mediaTypeTable->addRow(array(
		new CCheckBox('mediatypeids['.$mediatype['mediatypeid'].']', null, null, $mediatype['mediatypeid']),
		new CLink($mediatype['description'], '?form=edit&mediatypeid='.$mediatype['mediatypeid']),
		media_type2str($mediatype['type']),
		$actionLinks,
		$details
	));
}

// append table to form
$mediaTypeForm->addItem(array($this->data['paging'], $mediaTypeTable, $this->data['paging'], get_table_header(array(new CButtonQMessage('delete', _('Delete selected'), _('Delete selected media types?'))))));

// append form to widget
$mediaTypeWidget->addItem($mediaTypeForm);

return $mediaTypeWidget;
?>
