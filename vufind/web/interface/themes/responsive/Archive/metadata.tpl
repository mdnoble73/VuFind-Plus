{strip}
<div id="archive-metadata">

	{*{include file="Archive/moredetails-accordion.tpl"}*}

	{if strlen($marmotExtension->contextNotes) > 0}
		<div class="row">
			<div class="result-label col-sm-4">Context Notes: </div>
			<div class="result-value col-sm-8">
				{$marmotExtension->contextNotes}
			</div>
		</div>
	{/if}

{*//Moved to accordion
	{if $mods->subject}
		<div class="row">
			<div class="result-label col-sm-4">Subject: </div>
			<div class="result-value col-sm-8">
				{foreach from=$subjects item=subject}
					<a href='{$subject.link}'>
						{$subject.label}
					</a><br>
				{/foreach}
			</div>
		</div>
	{/if}
*}

	{if $relatedPeople}
		<div class="row">
			<div class="result-label col-sm-4">Related People: </div>
			<div class="result-value col-sm-8">
				{foreach from=$relatedPeople item=entity}
					<a href='{$entity.link}'>
						{$entity.label}
					</a>
					{if $entity.role}
						&nbsp;({$entity.role})
					{/if}
					<br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $relatedPlaces}
		<div class="row">
			<div class="result-label col-sm-4">Related Places: </div>
			<div class="result-value col-sm-8">
				{foreach from=$relatedPlaces item=entity}
					<a href='{$entity.link}'>
						{$entity.label}
					</a><br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $relatedEvents}
		<div class="row">
			<div class="result-label col-sm-4">Related Events: </div>
			<div class="result-value col-sm-8">
				{foreach from=$relatedEvents item=entity}
					<a href='{$entity.link}'>
						{$entity.label}
					</a><br>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $mods->originInfo && strlen($mods->originInfo->dateCreated)}
		<div class="row">
			<div class="result-label col-sm-4">Created: </div>
			<div class="result-value col-sm-8">
				{$mods->originInfo->dateCreated}
			</div>
		</div>
	{/if}

	{if $mods->physicalDescription || $mods->physicalLocation || $mods->shelfLocator}
		<hr>
		{if $mods->physicalDescription}
			<div class="row">
				<div class="result-label col-sm-4">Physical Description: </div>
				<div class="result-value col-sm-8">
					{foreach from=$mods->physicalDescription->extent item=extent}
						{if $extent}
							<div>{$extent}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
		{if $mods->physicalLocation}
			<div class="row">
				<div class="result-label col-sm-4">Located at: </div>
				<div class="result-value col-sm-8">
					{foreach from=$mods->physicalLocation item=location}
						{if $location}
							<div>{$location}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
		{if $mods->shelfLocator}
			<div class="row">
				<div class="result-label col-sm-4">Shelf Locator: </div>
				<div class="result-value col-sm-8">
					{foreach from=$mods->shelfLocator item=location}
						{if $location}
							<div>{$location}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
	{/if}

	{if $hasMilitaryService}
		<hr>
		<h3>Military Service</h3>
		<div class="row">
			<div class="result-label col-sm-4">Military Branch: </div>
			<div class="result-value col-sm-8">
				{$militaryRecord.branch}
			</div>
		</div>
		<div class="row">
			<div class="result-label col-sm-4">Conflict: </div>
			<div class="result-value col-sm-8">
				{$militaryRecord.conflict}
			</div>
		</div>
	{/if}

	{if $mods->identifier}
		<hr>
		<div class="row">
			<div class="result-label col-sm-4">Local Identifier: </div>
			<div class="result-value col-sm-8">
				{$mods->identifier}
			</div>
		</div>
	{/if}
	{if $mods->recordInfo}
		<hr>
		{if $mods->recordInfo->recordOrigin}
			<div class="row">
				<div class="result-label col-sm-4">Entered By: </div>
				<div class="result-value col-sm-8">
					{$mods->recordInfo->recordOrigin}
				</div>
			</div>
		{/if}
		{if $mods->recordInfo->recordCreationDate}
			<div class="row">
				<div class="result-label col-sm-4">Entered On: </div>
				<div class="result-value col-sm-8">
					{$mods->recordInfo->recordCreationDate}
				</div>
			</div>
		{/if}
		{if $mods->recordInfo->recordChangeDate}
			<div class="row">
				<div class="result-label col-sm-4">Last Changed: </div>
				<div class="result-value col-sm-8">
					{$mods->recordInfo->recordChangeDate}
				</div>
			</div>
		{/if}
	{/if}

{*
	{if $rightsStatements}
		<div class="row">
			<div class="result-label col-sm-4">Rights Statements: </div>
			<div class="result-value col-sm-8">
				{foreach from=$rightsStatements item=rightsStatement}
					<div>{$rightsStatement}</div>
				{/foreach}
			</div>
		</div>
	{/if}
*}

	{include file="Archive/moredetails-accordion.tpl"}

</div>

<div>
	{if $repositoryLink}
		<hr>
		<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
			View in Islandora
		</a>
		<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
			View MODS Record
		</a>
	{/if}
</div>
{/strip}