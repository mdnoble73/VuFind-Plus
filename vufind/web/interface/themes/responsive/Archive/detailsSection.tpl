{strip}
	{include file="Archive/accordion-items.tpl" relatedItems=$creators}

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