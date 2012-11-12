{strip}
<div data-role="page" id="MyResearch-holds">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			
			{* Check to see if there is data for the secion *}
			<div class='holdSection'>
				<h3 class='holdSectionTitle'>{translate text='Holds Ready For Pickup'}</h3>
				{if $userNoticeFile}
					{include file=$userNoticeFile}
				{/if}
				
				{assign var=sectionKey value='available'}
				<div class='holdSectionBody'>
					{if $libraryHoursMessage}
						<div class='libraryHours'>{$libraryHoursMessage}</div>
					{/if}
					{if is_array($recordList.$sectionKey) && count($recordList.$sectionKey) > 0}
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
								<a href="{$path}/MyResearch/Holds?multiAction=cancelSelected&amp;selected[{$resource.xnum}~{$resource.cancelId|escape:"url"}~{$resource.cancelId|escape:"id"}]" rel="external" data-icon="delete">Cancel Hold</a>
							</li>
						{/foreach}
						</ul>
					{else}
						<p>{translate text='You do not have any holds placed'}.</p>
					{/if}
				</div>
			</div>
		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
	{include file="footer.tpl"}
</div>
{/strip}