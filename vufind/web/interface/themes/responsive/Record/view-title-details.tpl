{strip}
	{* Display more information about the title*}
	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-md-3">Author: </div>
			<div class="col-md-9 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a>
			</div>
		</div>
	{/if}

	{if $recordDriver->getContributors()}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Contributors'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$recordDriver->getContributors() item=contributor name=loop}
					<a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $series}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Series'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$series item=seriesItem name=loop}
					<a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a><br />
				{/foreach}
			</div>
		</div>
	{/if}

	{if $published}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Published'}:</div>
			<div class="col-md-9 result-value">
				{foreach from=$published item=publish name=loop}
					{$publish|escape}
				{/foreach}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Format'}:</div>
		<div class="col-md-9 result-value">
			{implode subject=$recordFormat glue=", "}
		</div>
	</div>

	<div class="row">
		{if $editionsThis}
			<div class="result-label col-md-3">{translate text='Edition'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$editionsThis glue=", "}
			</div>
		{/if}
	</div>

	{if $physicalDescriptions}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Physical Desc'}:</div>
			<div class="col-md-9 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	{if $mpaaRating}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Rating'}:</div>
			<div class="col-md-9 result-value">{$mpaaRating|escape}</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-md-3">{translate text='Location'}:</div>
		<div class="col-md-9 result-value result-value-bold" id="locationValue">Loading...</div>
	</div>

	<div class="row">
		<div class="result-label col-md-3">{translate text='Call Number'}:</div>
		<div class="col-md-9 result-value result-value-bold" id="callNumberValue">Loading...</div>
	</div>

	<div class="row">
		<div class="result-label col-md-3">{translate text='Status'}:</div>
		<div class="col-md-9 result-value result-value-bold" id="statusValue">Loading...</div>
	</div>

	{if $summary}
		<div class="row">
			<div class="result-label col-md-3">{translate text='Description'}</div>
			<div class="result-value col-md-9">
				{if strlen($summary) > 600}
					<span id="shortSummary">
									{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'|truncate:600}{*Leave unescaped because some syndetics reviews have html in them *}
						<a href='#' onclick='$("#shortSummary").slideUp();$("#fullSummary").slideDown()'>More</a>
									</span>
					<span id="fullSummary" style="display:none">
									{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
						<a href='#' onclick='$("#shortSummary").slideDown();$("#fullSummary").slideUp()'>Less</a>
									</span>
				{else}
					{$summary|stripTags:'<b><p><i><em><strong><ul><li><ol>'}{*Leave unescaped because some syndetics reviews have html in them *}
				{/if}
			</div>
		</div>
	{/if}
{/strip}