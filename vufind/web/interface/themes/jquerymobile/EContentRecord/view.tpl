<div data-role="page" id="Record-view">
	{include file="header.tpl"}
	<div class="record" data-role="content" data-record-id="{$id}">
		<h3>{$eContentRecord->title|escape}
			{if $eContentRecord->subTitle}{$eContentRecord->subTitle|escape}{/if}
		</h3>

		{if $eContentRecord->description}<p>{$eContentRecord->description|truncate:200:"..."|escape}</p>{/if}
		
		<dl class="biblio" title="{translate text='Bibliographic Details'}">
		{if !empty($coreMainAuthor)}
			<dt>{translate text='Main Author'}:</dt>
			<dd><a rel="external" href="{$path}/Author/Home?author={$eContentRecord->author|escape:"url"}">{$eContentRecord->author|escape}</a></dd>
		{/if}
		
		<dt>{translate text='Format'}:</dt>
		<dd>
			{if is_array($eContentRecord->format())}
				{foreach from=$eContentRecord->format() item=displayFormat name=loop}
					<span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span>
				{/foreach}
			{else}
				<span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span>
				{/if}  
		</dd>
		
		<dt>{translate text='Language'}:</dt>
		<dd>{$eContentRecord->language|escape}</dd>
		
		{if !empty($eContentRecord->publisher)}
		  <dt>{translate text='Published'}:</dt>
		  <dd>{$eContentRecord->publisher}</dd>
		{/if}
		
		{if !empty($eContentRecord->edition)}
		  <dt>{translate text='Edition'}:</dt>
		  <dd>{$eContentRecord->edition|escape}</dd>
		{/if}
		
		{if !empty($subjectList)}
		  <dt>{translate text='Subjects'}:</dt>
		  <dd>
		  {foreach from=$subjectList item=subjectListItem name=loop}
		  <p>
		    <a href="{$path}/Search/Results?lookfor=%22{$subjectListItem|escape:'url'}%22&amp;type=Subject">{$subjectListItem|escape}</a>
		  </p>
		  {/foreach}
		  </dd>
		{/if}
		
		{assign var="otherAuthors" value=$eContentRecord->getPropertyArray('author2')}
		{if !empty($otherAuthors)}
		  <dt>{translate text='Other Authors'}:</dt>
		  <dd>
		  
		  {foreach from=$otherAuthors item=field name=loop}
		    <p><a rel="external" href="{$path}/Author/Home?author={$field|escape:"url"}">{$field|escape}</a></p>
		  {/foreach}
		  </dd>
		{/if}
		
		{* Display series section if at least one series exists. *}
		{assign var="seriesList" value=$eContentRecord->getPropertyArray('series')}
		{if !empty($coreSeries)}
		  <dt>{translate text='Series'}:</dt>
		  <dd>
		  {foreach from=$seriesList item=field name=loop}
		    {* Depending on the record driver, $field may either be an array with
		       "name" and "number" keys or a flat string containing only the series
		       name.  We should account for both cases to maximize compatibility. *}
		    {if is_array($field)}
		      {if !empty($field.name)}
		        <p>
		        <a rel="external" href="{$path}/Search/Results?lookfor=%22{$field.name|escape:"url"}%22&amp;type=Series">{$field.name|escape}</a>
		        {if !empty($field.number)}
		          {$field.number|escape}
		        {/if}
		        </p>
		      {/if}
		    {else}
		      <p><a rel="external" href="{$path}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Series">{$field|escape}</a></p>
		    {/if}
		  {/foreach}
		  </dd>
		{/if}
		
		{if $tagList}
		  <dt>{translate text='Tags'}:</dt>
		  <dd>
		    {foreach from=$tagList item=tag name=tagLoop}
		      <a rel="external" href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt}){if !$smarty.foreach.tagLoop.last}, {/if}
		    {/foreach}
		  </dd>
		{/if}
		</dl>
    
    <div data-role="controlgroup">
    {* Place hold link *}
	  <div class='requestThisLink' id="placeHold{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Hold"><img src="{$path}/interface/themes/default/images/place_hold.png" alt="Place Hold"/></a>
	  </div>
	  
	  {* Checkout link *}
	  <div class='checkoutLink' id="checkout{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Checkout" data-role="button" rel="external"><img src="{$path}/interface/themes/default/images/checkout.png" alt="Checkout"/></a>
	  </div>
	  
	  {* Access online link *}
	  <div class='accessOnlineLink' id="accessOnline{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/Home?detail=holdingstab#detailsTab" data-role="button" rel="external"><img src="{$path}/interface/themes/default/images/access_online.png" alt="Access Online"/></a>
	  </div>
	  
	  {* Add to Wish List *}
	  <div class='addToWishListLink' id="addToWishList{$id|escape:"url"}" style="display:none">
	    <a href="{$path}/EcontentRecord/{$id|escape:"url"}/AddToWishList" data-role="button" rel="external"><img src="{$path}/interface/themes/default/images/add_to_wishlist.png" alt="Add To Wish List"/></a>
	  </div>
	  	<a href="{$path}/EcontentRecord/{$id}/Save" data-role="button" rel="external">{translate text="Add to favorites"}</a>
    	<a href="{$path}/EcontentRecord/{$id}/AddTag" data-role="button" rel="external">{translate text="Add Tag"}</a>
    </div>
    
    {if $subTemplate}
    {include file="Record/$subTemplate"}
    {else}
    {include file="Record/view-holdings.tpl"}
    {/if}
            
  </div>
	{include file="footer.tpl"}
</div>

<script type="text/javascript" src="{$path}/services/EcontentRecord/ajax.js"></script>
<script type="text/javascript">
	GetEContentHoldingsInfo('{$id|escape:"url"}');
</script>   
{if $strandsAPID}
	{* Strands Tracking *}{literal}
	<!-- Event definition to be included in the body before the Strands js library -->
	<script type="text/javascript">
	if (typeof StrandsTrack=="undefined"){StrandsTrack=[];}
	StrandsTrack.push({
	   event:"visited",
	   item: "{/literal}econtentRecord{$id|escape}{literal}"
	});
	</script>
	{/literal}
{/if}
