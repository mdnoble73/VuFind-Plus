{strip}

	{* Date Created *}
	{if $dateCreated}
		<div class="row">
			<div class="result-label col-sm-4">Created: </div>
			<div class="result-value col-sm-8">
				{$dateCreated}
			</div>
		</div>
	{/if}

	{if $dateIssued}
		<div class="row">
			<div class="result-label col-sm-4">Issued: </div>
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

	{* Local Identifier *}
	{if count($identifier) > 0}
		<div class="row">
			<div class="result-label col-sm-4">Local Identifier{if count($identifier) > 1}s{/if}: </div>
			<div class="result-value col-sm-8">
				{implode subject=$identifier glue=', '}
			</div>
		</div>
	{/if}

	{* Date Created *}
	{if $postcardPublisherNumber}
		<div class="row">
			<div class="result-label col-sm-4">Postcard Publisher Number: </div>
			<div class="result-value col-sm-8">
				{$postcardPublisherNumber}
			</div>
		</div>
	{/if}

	{if $physicalExtents || $physicalLocation || $shelfLocator}

		{* Physical Description *}
		{if !empty($physicalExtents)}
			<div class="row">
				<div class="result-label col-sm-4">Physical Description: </div>
				<div class="result-value col-sm-8">
					{foreach from=$physicalExtents item=extent}
						{if $extent}
							<div>{$extent}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}

		{* Physical Location *}
		{if !empty($physicalLocation)}
			<div class="row">
				<div class="result-label col-sm-4">Located at: </div>
				<div class="result-value col-sm-8">
					{foreach from=$physicalLocation item=location}
						{if $location}
							<div>{$location}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}

		{* Shelf Locator *}
		{if !empty($shelfLocator)}
			<div class="row">
				<div class="result-label col-sm-4">Shelf Locator: </div>
				<div class="result-value col-sm-8">
					{foreach from=$shelfLocator item=location}
						{if $location}
							<div>{$location}</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
	{/if}

	{* Record Origin Info *}
	{if $hasRecordInfo}
		{if $recordOrigin}
			<div class="row">
				<div class="result-label col-sm-4">Entered By: </div>
				<div class="result-value col-sm-8">
					{$recordOrigin}
				</div>
			</div>
		{/if}
		{if $recordCreationDate}
			<div class="row">
				<div class="result-label col-sm-4">Entered On: </div>
				<div class="result-value col-sm-8">
					{$recordCreationDate}
				</div>
			</div>
		{/if}
		{if $recordChangeDate}
			<div class="row">
				<div class="result-label col-sm-4">Last Changed: </div>
				<div class="result-value col-sm-8">
					{$recordChangeDate}
				</div>
			</div>
		{/if}
	{/if}

{/strip}