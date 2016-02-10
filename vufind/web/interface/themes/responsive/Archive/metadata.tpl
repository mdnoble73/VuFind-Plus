{strip}
<div id="archive-metadata">
	{if strlen($marmotExtension->contextNotes) > 0}
		<div class="row">
			<div class="result-label col-sm-4">Context Notes: </div>
			<div class="col-sm-8 result-value">
				{$marmotExtension->contextNotes}
			</div>
		</div>
	{/if}

	{if $mods->subject}
		<div class="row">
			<div class="result-label col-sm-4">Subject: </div>
			<div class="col-sm-8 result-value">
				{foreach from=$subjects item=subject}
					<a href='{$subject.link}'>
						{$subject.label}
					</a><br/>
				{/foreach}
			</div>
		</div>
	{/if}
	{if $mods->originInfo && strlen($mods->originInfo->dateCreated)}
		<div class="row">
			<div class="result-label col-sm-4">Created: </div>
			<div class="col-sm-8 result-value">
				{$mods->originInfo->dateCreated}
			</div>
		</div>
	{/if}

	{if $mods->physicalDescription || $mods->physicalLocation || $mods->shelfLocator}
		<hr/>
		{if $mods->physicalDescription}
			<div class="row">
				<div class="result-label col-sm-4">Physical Description: </div>
				<div class="col-sm-8 result-value">
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
				<div class="col-sm-8 result-value">
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
				<div class="col-sm-8 result-value">
					{foreach from=$mods->shelfLocator item=location}
						{if $location}
							<div>{$location}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
	{/if}

	<hr/>

	{if $mods->identifier}
		<div class="row">
			<div class="result-label col-sm-4">Local Identifier: </div>
			<div class="col-sm-8 result-value">
				{$mods->identifier}
			</div>
		</div>
	{/if}
	{if $mods->recordInfo}
		{if $mods->recordInfo->recordOrigin}
			<div class="row">
				<div class="result-label col-sm-4">Entered By: </div>
				<div class="col-sm-8 result-value">
					{$mods->recordInfo->recordOrigin}
				</div>
			</div>
		{/if}
		{if $mods->recordInfo->recordCreationDate}
			<div class="row">
				<div class="result-label col-sm-4">Entered On: </div>
				<div class="col-sm-8 result-value">
					{$mods->recordInfo->recordCreationDate}
				</div>
			</div>
		{/if}
		{if $mods->recordInfo->recordChangeDate}
			<div class="row">
				<div class="result-label col-sm-4">Last Changed: </div>
				<div class="col-sm-8 result-value">
					{$mods->recordInfo->recordChangeDate}
				</div>
			</div>
		{/if}
	{/if}

	{if $rightsStatements}
		<div class="row">
			<div class="result-label col-sm-4">Rights Statements: </div>
			<div class="col-sm-8 result-value">
				{foreach from=$rightsStatements item=rightsStatement}
					<div>{$rightsStatement}</div>
				{/foreach}
			</div>
		</div>
	{/if}
</div>
<div>
	{if $repositoryLink}
		<hr/>
		<a class="btn btn-small btn-default" href="{$repositoryLink}" target="_blank">
			View in Islandora
		</a>
		<a class="btn btn-small btn-default" href="{$repositoryLink}/datastream/MODS/view" target="_blank">
			View MODS Record
		</a>
	{/if}
</div>
{/strip}