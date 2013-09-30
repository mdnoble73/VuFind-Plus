<div class="sidegroup" id="titleDetailsSidegroup">
		
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
				
	</div>