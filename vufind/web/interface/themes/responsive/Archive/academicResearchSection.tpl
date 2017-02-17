{strip}
	{if $researchType}
		<div class="row">
			<div class="result-label col-sm-4">Research Type: </div>
			<div class="result-value col-sm-8">
				{$researchType}
			</div>
		</div>
	{/if}
	{if $degreeName}
		<div class="row">
			<div class="result-label col-sm-4">Degree Name: </div>
			<div class="result-value col-sm-8">
				{$degreeName}
			</div>
		</div>
	{/if}
	{if $degreeDiscipline}
		<div class="row">
			<div class="result-label col-sm-4">Degree Discipline: </div>
			<div class="result-value col-sm-8">
				{$degreeDiscipline}
			</div>
		</div>
	{/if}
	{if $researchLevel}
		<div class="row">
			<div class="result-label col-sm-4">Research Level: </div>
			<div class="result-value col-sm-8">
				{$researchLevel}
			</div>
		</div>
	{/if}
	{if $peerReview}
		<div class="row">
			<div class="result-label col-sm-4">Peer Reviewed? </div>
			<div class="result-value col-sm-8">
				{$peerReview}
			</div>
		</div>
	{/if}
	{if $defenceDate}
		<div class="row">
			<div class="result-label col-sm-4">Defence Date:  </div>
			<div class="result-value col-sm-8">
				{$defenceDate}
			</div>
		</div>
	{/if}
	{if $acceptedDate}
		<div class="row">
			<div class="result-label col-sm-4">Accepted Date: </div>
			<div class="result-value col-sm-8">
				{$acceptedDate}
			</div>
		</div>
	{/if}
	{foreach from=$academicPeople item="academicPerson"}
		<div class="row">
			<div class="result-label col-sm-4">
				{$academicPerson.role}:
			</div>
			<div class="result-value col-sm-8">
				{if $academicPerson.link}
					<a href='{$academicPerson.link}'>
						{$academicPerson.label}
					</a>
				{else}
					{$academicPerson.label}
				{/if}
			</div>
		</div>
	{/foreach}
{/strip}