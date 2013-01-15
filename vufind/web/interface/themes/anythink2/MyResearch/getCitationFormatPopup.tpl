<div align="left">
	{if $message}<div class="error">{$message|translate}</div>{/if}

	<form action="{$path}/MyResearch/CiteList" method="get">
		<input type="hidden" name="listId" value="{$listId|escape}">
		<b>{translate text='Citation Format'}:</b><br />
		<select name="citationFormat">
			{foreach from=$citationFormats item=formatName key=format}
				<option value="{$format}">{$formatName}</option>
			{/foreach}
		</select>
		<br /><br />
		<input type="submit" name="submit" value="{translate text='Generate Citations'}">
	</form>
</div>