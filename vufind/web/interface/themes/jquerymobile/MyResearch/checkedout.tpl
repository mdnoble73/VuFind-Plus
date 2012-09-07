
<div data-role="page" id="MyResearch-checkedout">
	{include file="header.tpl"}
	<div data-role="content">
		{if $user->cat_username}
			<h3>{translate text='Your Checked Out Items'}</h3>
			{if $renew_message}
				{$renew_message}
			{/if}
			{if $transList}
				<ul class="results checkedout-list" data-role="listview" data-split-icon="forward">
				{foreach from=$transList item=resource name="recordLoop"}
					<li>
						<a rel="external" href="{if !empty($resource.id)}{$path}/Record/{$resource.id|escape}{else}#{/if}">
						<div class="result">
							{* If $resource.id is set, we have the full Solr record loaded and should display a link... *}
							{if !empty($resource.id)}
								<h3>{$resource.title|trim:'/:'|escape}</h3>
							{* If the record is not available in Solr, perhaps the ILS driver sent us a title we can show... *}
							{elseif !empty($resource.title)}
								<h3>{$resource.title|trim:'/:'|escape}</h3>
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
							<p><strong>{translate text='Due'}</strong>: {$resource.duedate|date_format}
								{if $resource.overdue}
									<span class='overdueLabel'>OVERDUE</span>
								{elseif $resource.daysUntilDue == 0}
									<span class='dueSoonLabel'>(Due today)</span>
								{elseif $resource.daysUntilDue == 1}
									<span class='dueSoonLabel'>(Due tomorrow)</span>
								{elseif $resource.daysUntilDue <= 7}
									<span class='dueSoonLabel'>(Due in {$resource.daysUntilDue} days)</span>
								{/if}
							</p>
							{if $resource.renewMessage}
								<p class='{if $resource.renewResult == true}renewPassed{else}renewFailed{/if}'>
									{$resource.renewMessage|escape}
								</p>
							{/if}
						</div>
						</a>
						<a href="{$path}/MyResearch/Renew?barcode={$resource.barcode}&amp;itemId={$resource.itemid}&itemIndex={$resource.itemindex}" rel="external" >Renew Item</a>
					</li>
				{/foreach}
				</ul>
				<a href="{$path}/MyResearch/RenewAll" data-role="button" rel="external" data-icon="forward">Renew All</a>
			{else}
				<p>{translate text='You do not have any items checked out'}.</p>
			{/if}
		{else}
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
	</div>
	{include file="footer.tpl"}
</div>
