{strip}
	{foreach from=$creators item=entity}
		<div class="row">
			<div class="result-label col-sm-4">{if $entity.role}{$entity.role|ucwords}:{/if}</div>
			<div class="result-value col-sm-8">
				<a href='{$entity.link}'>
					{$entity.label}
				</a>
				{if $entity.note}
					&nbsp;- {$entity.note}
				{/if}
			</div>
		</div>
	{/foreach}

	{if count($marriages) > 0}
		{foreach from=$marriages item=marriage}
			<div class="row">
				<div class="result-label col-sm-4">
					Married
				</div>
				<div class="result-value col-sm-8">
					{$marriage.spouseName}{if $marriage.formattedMarriageDate} - {$marriage.formattedMarriageDate}{/if}
					{if $marriage.comments}
						<div class="marriageComments">{$marriage.comments|escape}</div>
					{/if}
				</div>
			</div>
		{/foreach}
	{/if}

	{* Date Created *}
	{if $dateCreated}
		<div class="row">
			<div class="result-label col-sm-4">Date Created: </div>
			<div class="result-value col-sm-8">
				{$dateCreated}
			</div>
		</div>
	{/if}

	{if $dateIssued}
		<div class="row">
			<div class="result-label col-sm-4">Date of Publication: </div>
			<div class="result-value col-sm-8">
				{$dateIssued}
			</div>
		</div>
	{/if}

	{if $language}
		<div class="row">
			<div class="result-label col-sm-4">Language: </div>
			<div class="result-value col-sm-8">
				{$language}
			</div>
		</div>
	{/if}

{/strip}