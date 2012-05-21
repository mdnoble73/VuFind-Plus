<div class="ui-grid-a">
	<div class="ui-block-a">
		<img src='{$bookCoverUrl}' />	
	</div>
	<div class="ui-block-b">
			
				<a href="{$path}/EcontentRecord/{$summId|escape:"url"}?searchId={$searchId}&amp;recordIndex={$recordIndex}&amp;page={$page}" class="title">{if !$summTitle|regex_replace:"/(\/|:)$/":""}{translate text='Title not available'}{else}{$summTitle|regex_replace:"/(\/|:)$/":""|truncate:180:"..."|highlight:$lookfor}{/if}</a>
				
				<br/>{translate text='Author/s:'|highlight:"Author/s:"}
				
				{if is_array($summAuthor)}
					{foreach from=$summAuthor item=author}
						{$summAuthor}
					{/foreach}
				{else}
					{$summAuthor}
				{/if}
				<br/>
				{if $summDate}
					{translate text='Published'|highlight:"Published"}: {$summDate.0|escape}
				{/if}
				<br/>
				<div class="resultItemLine3">
					{if !empty($summSnippetCaption)}<b>{translate text=$summSnippetCaption|highlight:"Description"}</b>{/if}
					{if !empty($summSnippet)}<span class="quotestart">&#8220;</span>...{$summSnippet|highlight:"Table of Contents"}...<span class="quoteend">&#8221;</span><br />{/if}
				</div>
				
				{if is_array($summFormats)}
					{strip}
					{translate text="Formats"|highlight:"Formats"}:&nbsp;
					{foreach from=$summFormats item=format name=formatLoop}
						{if $smarty.foreach.formatLoop.index != 0}, {/if}
						<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":" "}">{translate text=$format}</span>
					{/foreach}
					{/strip}
				{else}
					{translate text="Format"|highlight:"Format"}:
					<span class="iconlabel {$summFormats|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$summFormats}</span>
				{/if}
				
				{* Place hold link *}
				<div class='requestThisLink' id="placeEcontentHold{$summId|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Hold" data-role="button" data-mini="true" data-theme='b' data-icon="arrow-r" data-inline="true" data-iconpos="right">
						Place Hold
					</a>
				</div>
				
				{* Checkout link *}
				<div class='checkoutLink' id="checkout{$summId|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Checkout" data-role="button" data-mini="true" data-theme='b' data-icon="arrow-r" data-inline="true" data-iconpos="right">
						Checkout
					</a>
				</div>
				
				{* Access online link *}
				<div class='accessOnlineLink' id="accessOnline{$summId|escape:"url"}" style="display:none">
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/Home" data-role="button" data-mini="true" data-theme='b' data-icon="arrow-r" data-inline="true" data-iconpos="right">
						Access Online
					</a>
				</div>
				{* Add to Wish List *}
				<div class='addToWishListLink' id="addToWishList{$summId|escape:"url"}" style="display:none" >
					<a href="{$path}/EcontentRecord/{$summId|escape:"url"}/AddToWishList" data-role="button" data-mini="true" data-theme='b' data-icon="arrow-r" data-inline="true" data-iconpos="right"> 
						Add to Wish List
					</a>
				</div>
	</div>	   
</div>

<script type="text/javascript">
	addIdToStatusList('{$summId|escape:"javascript"}', {if strcasecmp($source, 'OverDrive') == 0}'OverDrive'{else}'eContent'{/if});
	$(document).ready(function(){literal} { {/literal}
		//resultDescription('{$summId}','{$summId}', 'eContent');
	{literal} }); {/literal}
</script>