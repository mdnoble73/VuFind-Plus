{strip}
	{if $includesStamp}
		<div class="row">
			<div class="result-label col-sm-4">Includes Stamp: </div>
			<div class="result-value col-sm-8">
				Yes
			</div>
		</div>
	{/if}
	{if $datePostmarked}
		<div class="row">
			<div class="result-label col-sm-4">Date Postmarked: </div>
			<div class="result-value col-sm-8">
				{$datePostmarked}
			</div>
		</div>
	{/if}
	{if $postmarkLocation}
		<div class="relatedPlace row">
			<div class="result-label col-sm-4">
				Postmark Location:
			</div>
			<div class="result-value col-sm-8">
				{if $postMarkLocation.link}
					<a href='{$postMarkLocation.link}'>
						{$postMarkLocation.label}
					</a>
				{else}
					{$postMarkLocation.label}
				{/if}
				{if $postMarkLocation.role}
					&nbsp;({$postMarkLocation.role})
				{/if}
			</div>
		</div>
	{/if}
	{if $postmarkLocation}
		<div class="relatedPlace row">
			<div class="result-label col-sm-4">
				Postmark Location:
			</div>
			<div class="result-value col-sm-8">
				{if $postMarkLocation.link}
					<a href='{$postMarkLocation.link}'>
						{$postMarkLocation.label}
					</a>
				{else}
					{$postMarkLocation.label}
				{/if}
			</div>
		</div>
	{/if}
	{if $correspondenceRecipient}
		<div class="relatedPlace row">
			<div class="result-label col-sm-4">
				Correspondence Recipient:
			</div>
			<div class="result-value col-sm-8">
				{if $correspondenceRecipient.link}
					<a href='{$correspondenceRecipient.link}'>
						{$correspondenceRecipient.label}
					</a>
				{else}
					{$correspondenceRecipient.label}
				{/if}
			</div>
		</div>
	{/if}
{/strip}