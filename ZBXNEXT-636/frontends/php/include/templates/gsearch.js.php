<script type="text/x-jquery-tmpl" id="globalSearch">
	<input type="text" id="search" name="search" class="input text ui-corner-left" autocomplete="off" />
	<input type="submit" id="searchbttn" name="searchbttn" value="<?php print(_('Search')); ?>" class="input button" style="margin: 0; padding: 0 8px; height: 20px; position: relative; left: -3px;" />
</script>

<script type="text/javascript">
jQuery(document).ready(function(){
	var tpl = new Template(jQuery('#globalSearch').html());
	jQuery("#zbx_search").html(tpl.evaluate([]));
	jQuery('#searchbttn').button().removeClass('ui-corner-all').addClass('ui-corner-right');

	createSuggest('search');
});
</script>
