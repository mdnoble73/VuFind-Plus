{strip}
	{foreach from=$rightsStatements item=rightsStatement}
		<div class="rightsStatement">{$rightsStatement}</div>
	{/foreach}

	{if $rightsHolder}
		<div>
			<em>Rights held by&nbsp;
				{foreach from=$rightsHolder item="rightsHolder" name="rightsHolders"}
					{if $smarty.foreach.rightsHolders.iteration > 1}, {/if}
					<a href="{$rightsHolder.link}">{$rightsHolder.label}</a>
				{/foreach}
			</em>
		</div>
	{/if}
	{if $rightsCreatorTitle}
		<div><em>Rights created by <a href="{$rightsCreatorLink}">{$rightsCreatorTitle}</a></em></div>
	{/if}
	{if $rightsEffectiveDate || $rightsExpirationDate}
		<div><em>{if $rightsEffectiveDate}Rights statement effective {$rightsEffectiveDate}.  {/if}{if $rightsEffectiveDate}Rights statement expires {$rightsExpirationDate}.  {/if}</em></div>
	{/if}
{/strip}