<div class="ui-grid-a">
	<div class="ui-block-a">
		<img src='{$bookCoverUrl}' />	
	</div>
	<div class="ui-block-b">
		<div class="ui-grid-a">
			<div class="ui-block-a infoEcontent">
				{$summTitle|highlight:$summTitle}<br/>{translate text='by'|highlight:"by"}
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
				<br/>
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
			</div>
			<div class="ui-block-b buttonsEcontent">
				botones
			</div>
		</div>
	</div>	   
</div>