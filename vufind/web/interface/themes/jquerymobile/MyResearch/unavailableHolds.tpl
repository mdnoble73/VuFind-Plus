{strip}
<div data-role="page" id="MyResearch-holds">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			{assign var=sectionKey value='unavailable'}
			<div class='holdSection'>
				<a name="unavailableHoldsSection" rel="section"></a>
				{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
					<h3 class="myAccountTitle">{translate text='Titles On Hold'}</h3>
					{* Check to see if there is data for the secion *}
					<div class='holdSectionBody'>
						<ul class="results holds" data-role="listview">
						{foreach from=$recordList.$sectionKey item=resource name="recordLoop"}
							<li>
								<a rel="external" href="{if !empty($resource.id)}{$path}/Record/{$resource.id|escape}{else}#{/if}">
								<div class="result">
									{* If $resource.id is set, we have the full Solr record loaded and should display a link... *}
									{if !empty($resource.title)}
										<h3>{$resource.title|trim:'/:'|escape}</h3>
									{* If the record is not available in Solr, perhaps the ILS driver sent us a title we can show... *}
									{elseif !empty($resource.ils_details.title)}
										<h3>{$resource.ils_details.title|trim:'/:'|escape}</h3>
									{* Last resort -- indicate that no title could be found. *}
									{else}
										<h3>{translate text='Title not available'}</h3>
									{/if}
									{if !empty($resource.author)}
										<p>{translate text='by'} {$resource.author}</p>
									{/if}
									{if !empty($resource.format)}
									<p>
									{foreach from=$resource.format item=format}
										<span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
									{/foreach}
									</p>
									{/if} 
									<p><strong>{translate text='Status'}:</strong> {$resource.status} {if $resource.frozen}<span class='frozenHold' title='This hold will not be filled until you thaw the hold.'>(Frozen)</span>{/if}</p>
									<p><strong>{translate text='Pickup Location'}:</strong> {$resource.currentPickupName}</p>
									{if $resource.expiredate}
									<p><strong>{translate text='Expires'}:</strong> {$resource.expiredate|escape}</p>
									{/if}
								</div>
								</a>
								<a href="{$path}/MyResearch/Holds?section=unavailable&amp;multiAction=cancelSelected&amp;waitingholdselected[]={$resource.cancelId|escape:"id"}" rel="external" data-icon="delete" id="cancelHold{$resource.cancelId|escape}">Cancel Hold</a>
							</li>
						{/foreach}
						</ul>
					</div>
					
				{else}
					<p>{translate text='You do not have any holds placed'}.</p>
				{/if}
			</div>
		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
	{include file="footer.tpl"}
</div>
{/strip}