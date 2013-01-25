<script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js" ></script>
{* Errors *}
{if isset($errors) && count($errors) > 0}
	<div id='errors'>
	{foreach from=$errors item=error}
		<div id='error'>{$error}</div>
	{/foreach}
	</div>
{/if}

{* Create the base form *}
<form id='objectEditor' method="post" {if $contentType}enctype="{$contentType}"{/if} action="{$submitUrl}">
	{literal}
	<script type="text/javascript">
	$(document).ready(function(){
		$("#objectEditor").validate();
	});
	</script>
	{/literal}
	
	<div class='editor'>
		<input type='hidden' name='objectAction' value='save' />
		<input type='hidden' name='id' value='{$id}' />
		
		{foreach from=$structure item=property}
			{include file="DataObjectUtil/property.tpl"}
			
		{/foreach}
		<input type="submit" name="submit" value="Save Changes"/>
	</div>
</form>