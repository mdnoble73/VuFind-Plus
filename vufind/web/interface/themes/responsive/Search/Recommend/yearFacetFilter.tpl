<form id='{$title}Filter' action='{$fullPath}' class="form-horizontal">
	<label for="{$title}yearfrom" class='yearboxlabel'>From:</label>
	<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
	<label for="{$title}yearto" class='yearboxlabel'>To:</label>
	<input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearto" id="{$title}yearto" value="" />

	{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
	{foreach from=$smarty.get item=parmValue key=paramName}
		{if is_array($smarty.get.$paramName)}
			{foreach from=$smarty.get.$paramName item=parmValue2}
			{* Do not include the filter that this form is for. *}
				{if strpos($parmValue2, $title) === FALSE}
					<input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
				{/if}
			{/foreach}
		{else}
			<input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
		{/if}
	{/foreach}
	<input type="submit" value="Go" class="goButton btn btn-sm btn-default" />
	
	{if $title == 'publishDate'}
		<div id='yearDefaultLinks'>
			<a onclick="$('#{$title}yearfrom').val('2010');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2010</a>
			&bull;<a onclick="$('#{$title}yearfrom').val('2005');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
			&bull;<a onclick="$('#{$title}yearfrom').val('2000');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
		</div>
	{/if}
</form>