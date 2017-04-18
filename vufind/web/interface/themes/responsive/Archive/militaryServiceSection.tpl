{strip}
	{foreach from=$militaryRecords item=militaryRecord}
		<div class="row">
			<div class="result-label col-sm-4"><a href="{$militaryRecord.branchLink}">{$militaryRecord.branch}</a>
				{if $militaryRecord.serviceDateStart || $militaryRecord.serviceDateEnd}
					&nbsp;({if $militaryRecord.serviceDateStart}{$militaryRecord.serviceDateStart}{/if}{if $militaryRecord.serviceDateEnd} to {$militaryRecord.serviceDateEnd}{/if})
				{/if}
			</div>
			<div class="col-sm-8">
			{if $militaryRecord.conflict}
				<div class="row">
					<div class="result-value col-sm-12">
						<span class="result-label">Served in: </span><a href="{$militaryRecord.conflictLink}">{$militaryRecord.conflict}</a>
					</div>
				</div>
			{/if}
			{if $militaryRecord.highestRank}
				<div class="row">
					<div class="result-value col-sm-12">
						<span class="result-label">Highest rank attained: </span>{$militaryRecord.highestRank}
					</div>
				</div>
			{/if}
			{if $militaryRecord.prisonerOfWar == 'yes'}
				<div class="row">
					<div class="result-value col-sm-12">
						<span class="result-label">Prisoner of War</span>
					</div>
				</div>
			{/if}
			{if count($militaryRecord.locationsServed)}
				{foreach from=$militaryRecord.locationsServed item=locationServed}

					<div class="row">
						<div class="result-label col-sm-3">Served at: </div>
						<div class="result-value col-sm-9">
							{if $locationServed.link}<a href='{$locationServed.link}'>{/if}
								{$locationServed.label}
							{if $locationServed.link}</a>{/if}
						</div>
					</div>
				{/foreach}
			{/if}
			</div>
		</div>
	{/foreach}
{/strip}