{strip}
	{if count($marriages) > 0}
		{foreach from=$marriages item=marriage}
			<div class="marriageTitle">
				Married: {$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
			</div>
			{if $marriage.comments}
				<div class="marriageComments">{$marriage.comments|escape}</div>
			{/if}
		{/foreach}
	{/if}
	{include file="Archive/accordion-items.tpl" relatedItems=$relatedPeople}
{/strip}