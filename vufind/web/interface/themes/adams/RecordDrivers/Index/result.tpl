<div id="record{$summId|escape}">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[{$summShortId|escape:"url"}]" id="selected{$summShortId|escape:"url"}" class="titleSelect" {if $enableBookCart}onclick="toggleInBag('{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:)$/":""|regex_replace:"/\"/":"&quot;"|escape:'javascript'}', this);"{/if} />&nbsp;
	</div>
					
	<div class="resultsList">
		<div id='descriptionPlaceholder{$summShortId|escape}'	style='display:none'></div>
		<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" id="pretty{$summShortId|escape:"url"}">
			<img src="{$path}/bookcover.php?isn={$summISBN|@formatISBN}&amp;size=small&amp;upc={$summUPC}&amp;category={$summFormatCategory.0|escape:"url"}&amp;format={$summFormats.0|escape:"url"}" class="alignleft listResultImage" alt="{translate text='Cover Image'}"/>
		</a>
		
	 
		<div class="resultitem">
			<div class="resultItemLine1">
				{if $summScore}({$summScore}) {/if}
				<a href="{$path}/Record/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}&amp;searchSource={$searchSource}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
					{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
				{if $showRatings == 1}
					{* Let the user rate this title *}
					{include file="Record/title-rating.tpl" ratingClass="searchStars" recordId=$summId shortId=$summShortId}
				{/if}
			</div>
			{if $summEditions}
			<div class="resultInformation"><span class="resultLabel">{translate text='Edition'}:</span><span class="resultValue">{$summEditions.0|escape}</span></div>
			{/if}
			
			{if $summAuthor}
				<div class="resultInformation"><span class="resultLabel">{translate text='Author'}:</span>
					<span class="resultValue">
						{if is_array($summAuthor)}
							{foreach from=$summAuthor item=author}
								<a href="{$path}/Author/Home?author={$author|escape:"url"}">{$author|highlight:$lookfor}</a>
							{/foreach}
						{else}
							<a href="{$path}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
						{/if}
					</span>
				</div>
			{/if}
			{if $summPublicationDates || $summPublishers || $summPublicationPlaces}
			<div class="resultInformation"><span class="resultLabel">{translate text='Published'}:</span><span class="resultValue">{$summPublicationPlaces.0|escape}{$summPublishers.0|escape}{$summPublicationDates.0|escape}</span></div>
			{/if}
			
			<div class="resultInformation"><span class="resultLabel">{translate text='Format'}:</span><span class="resultValue">
			{if is_array($summFormats)}
				{foreach from=$summFormats item=format}
					<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
				{/foreach}
			{else}
				<span class="iconlabel {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
			{/if}
			</span></div>
			{if false && $summPhysical}
			<div class="resultInformation"><span class="resultLabel">{translate text='Physical Desc'}:</span><span class="resultValue">{$summPhysical.0|escape}</span></div>
			{/if}
			<div class="resultInformation" ><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue boldedResultValue" id="locationValue{$summShortId|escape}">Loading...</span></div>
			<div class="resultInformation" ><span class="resultLabel">{translate text='Call Number'}:</span><span class="resultValue callNumber" id="callNumberValue{$summShortId|escape}">Loading...</span></div>
			<div class="resultInformation" id="downloadLink{$summShortId|escape}" style="display:none"><span class="resultLabel">{translate text='Download From'}:</span><span class="resultValue" id="downloadLinkValue{$summShortId|escape}">Loading...</span></div>
			<div class="resultInformation" ><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue{$summShortId|escape}">Loading...</span></div>
					
			<div class="resultItemLine3">
				<!-- 
				<b>{translate text='Call Number'}:</b> <span id="callnumber{$summId|escape}">{translate text='Loading'}</span><br />
				<b>{translate text='Located'}:</b> <span id="location{$summId|escape}">{translate text='Loading'}</span>
				 -->
				
				{* If we have an ISSN and an OpenURL resolver, use those to provide full
					text.	Otherwise, check to see if there was a URL stored in the Solr
					record and assume that is full text instead. *}
				{if $record.issn && $openUrlLink}
					{if is_array($record.issn)}
						{assign var='currentIssn' value=$record.issn.0|escape:"url"}
					{else}
						{assign var='currentIssn' value=$record.issn|escape:"url"}
					{/if}
					{assign var='extraParams' value="issn=`$currentIssn`&genre=journal"}
					<br /><a href="{$openUrlLink|addURLParams:"`$extraParams`"|escape}" class="fulltext"
					 onclick="window.open('{$openUrlLink|addURLParams:"`$extraParams`"|escape}', 'openurl', 'toolbar=no,location=no,directories=no,buttons=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=550,height=600'); return false;">{translate text='Get full text'}</a>
				{elseif $record.url}
					{* Remove download links for now since we are pulling them from item records 
					{if is_array($record.url)}
						{foreach from=$record.url item=recordurl}
							<br /><a href="{$recordurl|escape}" class="fulltext" target="_new"><img height="" width="" src={$path}/interface/themes/marmot/images/download.jpg></a>
						{/foreach}
					{else}
						<br /><a href="{$recordurl|escape}" class="fulltext" target="_new"><img src={$path}/interface/themes/marmot/images/download.jpg></a>
					{/if}
					*}
				{else}
					 
				{/if}
			</div>
		</div>
			 
	</div>
	
	<span class="Z3988"
		style="display:none"
		{if $summFormats=="Book"}
			title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&amp;rfr_id=info%3Asid%2F{$coinsID}%3Agenerator&amp;rft.genre=book&amp;rft.btitle={$summTitle|regex_replace:"/(\/|:)$/":""|escape:"url"}&amp;rft.title={$summTitle|escape:"url"}&amp;rft.series={$record.series}&amp;rft.au={$record.author|escape:"url"}&amp;rft.date={$record.publishDate}&amp;rft.pub={$record.publisher|escape:"url"}&amp;rft.edition={$record.edition|escape:"url"}&amp;rft.isbn={$summISBN}">
		{elseif $summFormats=="Journal"}
			title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rfr_id=info%3Asid%2F{$coinsID}%3Agenerator&amp;rft.genre=article&amp;rft.title={$summTitle|regex_replace:"/(\/|:)$/":""|escape:"url"}&amp;rft.date={$record.publishDate}&amp;rft.issn={$record.issn}">
		{else}
			title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rfr_id=info%3Asid%2F{$coinsID}%3Agenerator&amp;rft.title={$summTitle|regex_replace:"/(\/|:)$/":""|escape:"url"}&amp;rft.creator={$record.author|escape:"url"}&amp;rft.date={$record.publishDate}&amp;rft.pub={$record.publisher|escape:"url"}&amp;rft.format={$summFormats}">
		{/if}
	&nbsp;</span>
	
	<script type="text/javascript">
		$(document).ready(function(){literal} { {/literal}
			addIdToStatusList('{$summId|escape:"javascript"}');
			resultDescription('{$summShortId}','{$summId}');
		{literal} }); {/literal}
	</script>
	
	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>