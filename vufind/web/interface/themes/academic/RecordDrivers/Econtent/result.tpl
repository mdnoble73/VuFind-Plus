<div id="record{$summId|escape}">
	<div class="resultIndex">{$resultIndex}</div>
	<div class="selectTitle">
		<input type="checkbox" name="selected[econtentRecord{$summId|escape:"url"}]" id="selectedEcontentRecord{$summId|escape:"url"}" {if $enableBookCart}onclick="toggleInBag('econtentRecord{$summId|escape:"url"}', '{$summTitle|regex_replace:"/(\/|:'\")$/":""|escape:"javascript"}', this);"{/if} />&nbsp;
	</div>
	
	<div class="resultsList">
		{if !isset($user->disableCoverArt) ||$user->disableCoverArt != 1}	
			<div id='descriptionPlaceholder{$summId|escape}' style='display:none'></div>
			<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" id="descriptionTrigger{$summId|escape:"url"}">
			<img src="{$bookCoverUrl}" class="listResultImage" alt="{translate text='Cover Image'}"/>
			</a>
		{/if}
	
		<div class="resultitem">
			<div class="resultItemLine1">
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				{if $summTitleStatement}
					<div class="searchResultSectionInfo">
						{$summTitleStatement|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}
					</div>
				{/if}
				
				{if $showRatings == 1}
					<div id ="searchStars{$summId|escape}" class="resultActions">
						<div class="rateEContent{$summId|escape} stat">
							<div class="statVal">
								<span class="ui-rater">
									<span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px">&nbsp;</span></span>
									(<span class="ui-rater-rateCount-{$summId|escape} ui-rater-rateCount">0</span>)
								</span>
							</div>
							<div id="saveLink{$summId|escape}">
								{if $user}
									<div id="lists{$summId|escape}"></div>
									<script type="text/javascript">
										getSaveStatuses('{$summId|escape:"javascript"}');
									</script>
								{/if}
								{if $showFavorites == 1} 
									<a href="{$path}/Resource/Save?id={$summId|escape:"url"}&amp;source=eContent" style="padding-left:8px;" onclick="getSaveToListForm('{$summId|escape}', 'eContent'); return false;">{translate text='Add to'} <span class='myListLabel'>MyLIST</span></a>
								{/if}
							</div>
							{assign var=id value=$summId scope="global"}
							{include file="EcontentRecord/title-review.tpl" id=$summId}
						</div>
						<script type="text/javascript">
							$(
								 function() {literal} { {/literal}
										 $('.rateEContent{$summId|escape}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: {$summId},	rating:0.0, postHref: '{$path}/EcontentRecord/{$summId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
								 {literal} } {/literal}
							);
						</script>
					</div>
				{/if}
			</div>
			
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
			
			{if $summFormats}
				<div class="resultInformation"><span class="resultLabel">{translate text='Format'}:</span><span class="resultValue">
					{if is_array($summFormats)}
						{foreach from=$summFormats item=format}
							<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
						{/foreach}
					{else}
						<span class="iconlabel {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
					{/if}
					</span>
				</div>
			{/if}
			
			<div class="resultInformation" ><span class="resultLabel">{translate text='Location'}:</span><span class="resultValue" id="locationValue{$summId|escape}">Online</span></div>
			<div class="resultInformation" ><span class="resultLabel">{translate text='Status'}:</span><span class="resultValue" id="statusValue{$summId|escape}">Loading...</span></div>
		</div>
	</div>
	
	<script type="text/javascript">
		{if $showRatings == 1}
		addRatingId('{$summId|escape:"javascript"}', 'eContent');
		{/if}
		addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if});
		$(document).ready(function(){literal} { {/literal}
			resultDescription('{$summId}','{$summId}', 'eContent');
		{literal} }); {/literal}
		
	</script>


	{* Clear floats so the record displays as a block*}
	<div class='clearer'></div>
</div>