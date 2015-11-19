{strip}
{if $offline}
	<div class="warning">The circulation system is currently offline.  Holdings information is based on information from before the system went offline.</div>
{/if}
{* ils check & last checkin date *}
{if ($ils == 'Sierra' || $ils == 'Millennium') && $hasLastCheckinData}
	{assign var=showLastCheckIn value=true}
{else}
	{assign var=showLastCheckIn value=false}
{/if}
{assign var=lastSection value=''}
{if isset($sections) && count($sections) > 0}
	{foreach from=$sections item=section}
		{if strlen($section.name) > 0 && count($sections) > 1}
			<div class="accordion-group">
				<div class="accordion-heading" id="holdings-header-{$section.name|replace:' ':'_'}">
					<a class='accordion-toggle' data-toggle="collapse" data-target="#holdings-section-{$section.name|replace:' ':'_'}">{$section.name}</a>
				</div>
		{/if}

		<div id="holdings-section-{$section.name|replace:' ':'_'}" class="accordion-body {if count($sections) > 1}collapse {if $section.sectionId <=5}in{/if}{/if}">
			<div class="accordion-inner">
				<div class="striped">
				{include file="Record/copiesTableHeader.tpl"}
				{foreach from=$section.holdings item=holding}
					{include file="Record/copiesTableRow.tpl"}
				{/foreach}
				</div>
			</div>
		</div>

		{if strlen($section.name) > 0 && count($sections) > 1}
			{* Close the group *}
			</div>
		{/if}
	{/foreach}
{elseif isset($issueSummaries) && count($issueSummaries) > 0}
	{include file="Record/issueSummaries.tpl"}
{else}
	No Copies Found
{/if}
{/strip}