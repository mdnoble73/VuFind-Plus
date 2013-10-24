<form id='{$title}Filter' action='{$fullPath}' class="form-horizontal-narrow">
	<div>
		<div class="control-group">
			<label for="{$title}yearfrom" class='control-label control-label-narrow'>From:</label>
			<div class="controls">
				<input type="text" size="4" maxlength="4" class="yearbox input-mini" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
			</div>
		</div>
		<div class="control-group">
			<label for="{$title}yearto" class='control-label'>To:</label>
			<div class="controls">
				<input type="text" size="4" maxlength="4" class="yearbox input-mini" name="{$title}yearto" id="{$title}yearto" value="" />
			</div>
		</div>
		{* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
		<div class="control-group">
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
			<div class="controls">
				<input type="submit" value="Go" class="goButton btn" />
			</div>
		</div>
		{if $title == 'publishDate'}
			<div id='yearDefaultLinks'>
				<a onclick="$('#{$title}yearfrom').val('2010');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2010</a>
				&bull;<a onclick="$('#{$title}yearfrom').val('2005');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
				&bull;<a onclick="$('#{$title}yearfrom').val('2000');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
			</div>
		{/if}
	</div>
</form>