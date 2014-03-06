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
<form id='objectEditor' method="post" {if $contentType}enctype="{$contentType}"{/if} action="{$submitUrl}" role="form">
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

		<br/>
		
		{foreach from=$structure item=property}
			{include file="DataObjectUtil/property.tpl"}
			
		{/foreach}
		<input type="submit" name="submitReturnToList" value="Save Changes and Return" class="btn btn-primary"/>
		{if $id}
			<input type="submit" name="submitStay" value="Save Changes and Stay Here" class="btn"/>
		{else}
			<input type="submit" name="submitAddAnother" value="Save Changes and Add Another" class="btn"/>
		{/if}
	</div>
</form>