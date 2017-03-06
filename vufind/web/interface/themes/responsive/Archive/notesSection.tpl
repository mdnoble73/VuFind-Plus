{strip}
	{foreach from=$notes item=note}
		<div class="row">
			<div class="result-label col-sm-4">{$note.label}</div>
			<div class="result-value col-sm-8">
				{$note.body}
			</div>
		</div>
	{/foreach}
{/strip}