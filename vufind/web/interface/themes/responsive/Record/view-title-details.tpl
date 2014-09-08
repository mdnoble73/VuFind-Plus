{strip}
	{* Display more information about the title*}

	{if $recordDriver->getUniformTitle()}
		<div class="row">
			<div class="result-label col-xs-4">Uniform Title: </div>
			<div class="col-xs-8 result-value">
				{foreach from=$recordDriver->getUniformTitle() item=uniformTitle}
					<a href="{$path}/Search/Results?lookfor={$uniformTitle|escape:"url"}">{$uniformTitle|highlight:$lookfor}</a><br/>
				{/foreach}
			</div>
		</div>
	{/if}

	{if $recordDriver->getAuthor()}
		<div class="row">
			<div class="result-label col-xs-4">Author: </div>
			<div class="col-xs-8 result-value">
				<a href="{$path}/Author/Home?author={$recordDriver->getAuthor()|escape:"url"}">{$recordDriver->getAuthor()|highlight:$lookfor}</a><br/>
			</div>
		</div>
	{/if}

	{if $recordDriver->getDetailedContributors()}
		<div class="row">
			<div class="result-label col-xs-4">{translate text='Contributors'}:</div>
			<div class="col-xs-8 result-value">
				{foreach from=$recordDriver->getDetailedContributors() item=contributor name=loop}
					{if $smarty.foreach.loop.index == 5}
						<div id="showAdditionalContributorsLink">
							<a onclick="VuFind.Record.moreContributors(); return false;" href="#">{translate text='more'} ...</a>
						</div>
						{*create hidden div*}
						<div id="additionalContributors" style="display:none">
					{/if}
					<a href="{$path}/Author/Home?author={$contributor.name|trim|escape:"url"}">{$contributor.name|escape}</a>
					{if $contributor.role}
						&nbsp;{$contributor.role}
					{/if}
					{if $contributor.title}
						&nbsp;<a href="{$path}/Search/Results?lookfor={$contributor.title}&amp;basicType=Title">{$contributor.title}</a>
					{/if}
				<br/>
				{/foreach}
				{if $smarty.foreach.loop.index >= 5}
					<div>
						<a href="#" onclick="VuFind.Record.lessContributors(); return false;">{translate text='less'} ...</a>
					</div>
					</div>{* closes hidden div *}
				{/if}
			</div>
		</div>
	{/if}

	{if $recordDriver->getSeries()}
		<div class="series row">
			<div class="result-label col-xs-4">Series: </div>
			<div class="col-xs-8 result-value">
				{assign var=summSeries value=$recordDriver->getSeries()}
				<a href="{$path}/GroupedWork/{$recordDriver->getPermanentId()}/Series">{$summSeries.seriesTitle}</a>{if $summSeries.volume} volume {$summSeries.volume}{/if}
			</div>
		</div>
	{/if}

	{if $recordDriver->getPublicationDetails()}
		<div class="row">
			<div class="result-label col-xs-4">{translate text='Published'}:</div>
			<div class="col-xs-8 result-value">
				{implode subject=$recordDriver->getPublicationDetails() glue=", "}
			</div>
		</div>
	{/if}

	<div class="row">
		<div class="result-label col-xs-4">{translate text='Format'}:</div>
		<div class="col-xs-8 result-value">
			{implode subject=$recordFormat glue=", "}
		</div>
	</div>

	{if $recordDriver->getEdition()}
		<div class="row">
			<div class="result-label col-xs-4">{translate text='Edition'}:</div>
			<div class="col-xs-8 result-value">
				{implode subject=$recordDriver->getEdition() glue=", "}
			</div>
		</div>
	{/if}

	{if $physicalDescriptions}
		<div class="row">
			<div class="result-label col-xs-4">{translate text='Physical Desc'}:</div>
			<div class="col-xs-8 result-value">
				{implode subject=$physicalDescriptions glue="<br/>"}
			</div>
		</div>
	{/if}

	{if $mpaaRating}
		<div class="row">
			<div class="result-label col-xs-4">{translate text='Rating'}:</div>
			<div class="col-xs-8 result-value">{$mpaaRating|escape}</div>
		</div>
	{/if}

	<div class="row" id="locationRow">
		<div class="result-label col-xs-4">{translate text='Location'}:</div>
		<div class="col-xs-8 result-value result-value-bold" id="locationValue">Loading...</div>
	</div>

	<div class="row" id="callNumberRow">
		<div class="result-label col-xs-4">{translate text='Call Number'}:</div>
		<div class="col-xs-8 result-value result-value-bold" id="callNumberValue">Loading...</div>
	</div>

	<div class="row">
		<div class="result-label col-xs-4">{translate text='Status'}:</div>
		<div class="col-xs-8 result-value result-value-bold statusValue" id="statusValue">Loading...</div>
	</div>
{/strip}