{strip}
	{if $degreeName}
		<div class="row">
			<div class="result-label col-sm-4">Degree Name: </div>
			<div class="result-value col-sm-8">
				{$degreeName}
			</div>
		</div>
	{/if}
	{if $graduationDate}
		<div class="row">
			<div class="result-label col-sm-4">Graduation Date: </div>
			<div class="result-value col-sm-8">
				{$graduationDate}
			</div>
		</div>
	{/if}
	{foreach from=$educationPeople item="educationPerson"}
		<div class="row">
			<div class="result-label col-sm-4">
				{$educationPerson.role}:
			</div>
			<div class="result-value col-sm-8">
				{if $educationPerson.link}
					<a href='{$educationPerson.link}'>
						{$educationPerson.label}
					</a>
				{else}
					{$educationPerson.label}
				{/if}
			</div>
		</div>
	{/foreach}
{/strip}