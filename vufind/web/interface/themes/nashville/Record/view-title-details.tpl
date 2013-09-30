<div id="titleDetailsSidegroup">
		{* <h4>{translate text="Title Details"}</h4> *}
		{if $mainAuthor}
			<div class="sidebarLabel">{translate text='Main Author'}:</div>
			<div class="sidebarValue"><a href="{$path}/Author/Home?author={$mainAuthor|trim|escape:"url"}">{$mainAuthor|escape}</a></div>
		{/if}
		
		{if $corporateAuthor}
			<div class="sidebarLabel">{translate text='Corporate Author'}:</div>
			<div class="sidebarValue"><a href="{$path}/Author/Home?author={$corporateAuthor|trim|escape:"url"}">{$corporateAuthor|escape}</a></div>
		{/if}
		
		{if $contributors}
			<div class="sidebarLabel">{translate text='Contributors'}:</div>
			{foreach from=$contributors item=contributor name=loop}
				{if $smarty.foreach.loop.index == 5}
					<div class="sidebarValue" id="contributorsMoreLink"><a href="#" onclick="$('#contributorsMoreSection').toggle();$('#contributorsMoreLink').toggle();">{translate text="more"}...</a></div>
					<div id="contributorsMoreSection" style="display:none">
				{/if}
				<div class="sidebarValue"><a href="{$path}/Author/Home?author={$contributor|trim|escape:"url"}">{$contributor|escape}</a></div>
			{/foreach}
			{if $smarty.foreach.loop.index >= 5}
				<div class="sidebarValue" id="contributorsLessLink"><a href="#" onclick="$('#contributorsMoreSection').toggle();$('#contributorsMoreLink').toggle();">{translate text="less"}</a></div>
				</div>
			{/if}
		{/if}
		
		{if $published}
			<div class="sidebarLabel">{translate text='Published'}:</div>
			{foreach from=$published item=publish name=loop}
				<div class="sidebarValue">{$publish|escape}</div>
			{/foreach}
		{/if}
		
		{if $streetDate}
			<div class="sidebarLabel">{translate text='Street Date'}:</div>
			<div class="sidebarValue">{$streetDate|escape}</div>
		{/if}
		
		<div class="sidebarLabel">{translate text='Format'}:</div>
		{if is_array($recordFormat)}
		 {foreach from=$recordFormat item=displayFormat name=loop}
			 <div class="sidebarValue"><span class="icon {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$displayFormat}</span></div>
		 {/foreach}
		{else}
			<div class="sidebarValue"><span class="icon {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">&nbsp;</span><span class="iconlabel">{translate text=$recordFormat}</span></div>
		{/if}
		
        {if $subjects}
				<div class="sidebarLabel">{translate text='Subjects'}</div>
					<div class="sidebarValue">
						{foreach from=$subjects item=subject name=loop}
							{if $smarty.foreach.loop.index == 5}
								<div id="subjectsMoreLink"><a href="#" onclick="$('#subjectsMoreSection').toggle();$('#subjectsMoreLink').toggle();">{translate text="more"}...</a></div>
								<div id="subjectsMoreSection" style="display:none">
							{/if}
							{foreach from=$subject item=subjectPart name=subloop}
								{if !$smarty.foreach.subloop.first} -- {/if}
								<a href="{$path}/Search/Results?lookfor=%22{$subjectPart.search|escape:"url"}%22&amp;basicType=Subject">{$subjectPart.title|escape}</a>
							{/foreach}
							<br />
						{/foreach}
						{if $smarty.foreach.loop.index >= 5}
							<div id="subjectsLessLink"><a href="#" onclick="$('#subjectsMoreSection').toggle();$('#subjectsMoreLink').toggle();">{translate text="less"}</a></div>
							</div>
						{/if}
					</div>
			{/if}
        
		{if $mpaaRating}
			<div class="sidebarLabel">{translate text='Rating'}:</div>
			<div class="sidebarValue">{$mpaaRating|escape}</div>
		{/if}
				
		{if $physicalDescriptions}
			<div class="sidebarLabel">{translate text='Physical Desc'}:</div>
			{foreach from=$physicalDescriptions item=physicalDescription name=loop}
				<div class="sidebarValue">{$physicalDescription|escape}</div>
			{/foreach}
		{/if}
				
		<div class="sidebarLabel">{translate text='Language'}:</div>
		{foreach from=$recordLanguage item=lang}
			<div class="sidebarValue">{$lang|escape}</div>
		{/foreach}
		
		{if $editionsThis}
			<div class="sidebarLabel">{translate text='Edition'}:</div>
			{foreach from=$editionsThis item=edition name=loop}
				<div class="sidebarValue">{$edition|escape}</div>
			{/foreach}
		{/if}
				
		{if $isbns}
			<div class="sidebarLabel">{translate text='ISBN'}:</div>
			{foreach from=$isbns item=tmpIsbn name=loop}
				<div class="sidebarValue">{$tmpIsbn|escape}</div>
			{/foreach}
		{/if}
				
		{if $issn}
			<div class="sidebarLabel">{translate text='ISSN'}:</div>
			<div class="sidebarValue">{$issn}</div>
			{if $goldRushLink}
				<div class="sidebarValue"><a href='{$goldRushLink}' target='_blank'>Check for online articles</a></div>
			{/if}
		{/if}
				
		{if $upc}
			<div class="sidebarLabel">{translate text='UPC'}:</div>
			<div class="sidebarValue">{$upc|escape}</div>
		{/if}
		
		{if $series}
			<div class="sidebarLabel">{translate text='Series'}:</div>
			{foreach from=$series item=seriesItem name=loop}
				<div class="sidebarValue"><a href="{$path}/Search/Results?lookfor=%22{$seriesItem|escape:"url"}%22&amp;type=Series">{$seriesItem|escape}</a></div>
			{/foreach}
		{/if}
		
		{if $arData}
			<div class="sidebarLabel">{translate text='Accelerated Reader'}:</div>
			<div class="sidebarValue">{$arData.interestLevel|escape}</div>
			<div class="sidebarValue">Level {$arData.readingLevel|escape}, {$arData.pointValue|escape} Points</div>
		{/if}
				
		{if $lexileScore}
			<div class="sidebarLabel">{translate text='Lexile Score'}:</div>
			<div class="sidebarValue">{$lexileScore|escape}</div>
		{/if}
				
	</div>