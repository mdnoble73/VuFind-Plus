{strip}
{capture name=pagelinks}
  <div class="alphaBrowsePageLinks">
    {if isset ($prevpage)}
      <div class="alphaBrowsePrevLink"><a href="{$path}/AlphaBrowse/Results?source={$source|escape:"url"}&amp;from={$from|escape:"url"}&amp;page={$prevpage|escape:"url"}">&laquo; Prev</a></div>
    {/if}

    {if isset ($nextpage)}
      <div class="alphaBrowseNextLink"><a href="{$path}/AlphaBrowse/Results?source={$source|escape:"url"}&amp;from={$from|escape:"url"}&amp;page={$nextpage|escape:"url"}">Next &raquo;</a></div>
    {/if}
    <div class="clearer"><!-- empty --></div>
  </div>
{/capture}

<div id="page-content" class="content">
	{if $result}
		<div class="alphaBrowseResult">
			{$smarty.capture.pagelinks}

			<div class="alphaBrowseHeader">{translate text="alphabrowse_matches"}</div>
			{foreach from=$result.items item=item name=recordLoop}
				<div class="alphaBrowseEntry {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt {/if}">
					<div class="alphaBrowseHeading">
						{if $item.numResults > 0}
							<a href='{$item.searchLink|trim}'>{$item.value|escape:"html"}</a>
						{else}
							{$item.value|escape:"html"}
						{/if}
					</div>
					<div class="alphaBrowseCount">{if $item.numResults > 0}{$item.numResults}{/if}</div>
					<div class="clearer"><!-- empty --></div>
				</div>
			{/foreach}
			{$smarty.capture.pagelinks}
		</div>
	{else}
		{* spacer div so that empty form page isn't too small: *}
		<div style="height:350px; width: 1px;"><!-- empty -->
			{if $error}{$error}{/if}
		</div>
	{/if}
</div>
{/strip}