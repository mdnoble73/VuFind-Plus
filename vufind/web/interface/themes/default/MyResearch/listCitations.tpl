<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
	</div>

	<div id="main-content">
		<h3 id='listTitle'><a href="{$path}/MyResearch/MyList/{$favList->id}"><span class="silk list">&nbsp;</span>{$favList->title|escape:"html"}</a></h3>
		{if $favList->description}<div class="listDescription alignleft" id="listDescription">{$favList->description|escape}</div>{/if}
		<p>Citations in {$citationFormat} format.</p>
		{if $citations}
			<div id="searchInfo">
				{foreach from=$citations item=citation}
					<div class="citation">
					{$citation}
					</div>
					<br />
				{/foreach}
				{if $recordCount}
					{translate text="Showing"}
					<b>{$recordStart}</b> - <b>{$recordEnd}</b>
					{translate text='of'} <b>{$recordCount}</b>
				{/if}

			</div>
		{else}
			{translate text='This list does not have any titles to build citations for.'}
		{/if}
		<div class="note">{translate text="Citation formats are based on standards as of July 2010.  Citations contain only title, author, edition, publisher, and year published."}</div>
		<div class="note">{translate text="Citations should be used as a guideline and should be double checked for accuracy."}</div>
	</div>
</div>
