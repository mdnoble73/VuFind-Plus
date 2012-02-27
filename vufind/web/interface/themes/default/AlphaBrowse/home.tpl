<script  type="text/javascript"></script>

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

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
      {* Do not show a separate browse search
      <div class="resulthead alphaBrowseForm">
        <form method="GET" action="{$path}/AlphaBrowse/Results">
          {translate text='Browse Alphabetically'}
          <select name="source">
            {foreach from=$alphaBrowseTypes key=key item=item}
              <option value="{$key|escape}" {if $source == $key}selected{/if}>{translate text=$item}</option>
            {/foreach}
          </select>
          {translate text='starting from'}
          <input type="text" name="from" value="{$from|escape:"html"}">
          <input type="submit" value="{translate text='Browse'}">
        </form>
      </div>
			*}
			
      {if $result}
        <div class="alphaBrowseResult">
        {$smarty.capture.pagelinks}

        <div class="alphaBrowseHeader">{translate text="alphabrowse_matches"}</div>
          {foreach from=$result.Browse.items item=item name=recordLoop}
            <div class="alphaBrowseEntry {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt {/if}">
            <div class="alphaBrowseHeading">
              {if $item.count > 0}
                {capture name="searchLink"}
                  {* linking using bib ids is generally more reliable than
                     doing searches for headings, but headings give shorter
                     queries and don't look as strange. *}
                  {if $item.count < 5}
                    {$path}/Search/Results?basicType=ids&amp;lookfor={foreach from=$item.ids item=id}{$id}+{/foreach}
                  {else}
                    {$path}/Search/Results?basicType={$source|capitalize|escape:"url"}Browse&amp;lookfor={$item.heading|escape:"url"}
                  {/if}
                {/capture}
                <a href="{$smarty.capture.searchLink|trim}">{$item.heading|escape:"html"}</a>
              {else}
                {$item.heading|escape:"html"}
              {/if}
            </div>
            <div class="alphaBrowseCount">{if $item.count > 0}{$item.count}{/if}</div>
            <div class="clearer"><!-- empty --></div>

            {if $item.useInstead|@count > 0}
              <div class="alphaBrowseRelatedHeading">
                <div class="title">{translate text="Use instead"}:</div>
                <ul>
                  {foreach from=$item.useInstead item=heading}
                    <li><a href="{$path}/AlphaBrowse/Results?source={$source|escape:"url"}&amp;from={$heading|escape:"url"}">{$heading|escape:"html"}</a></li>
                  {/foreach}
                </ul>
              </div>
            {/if}

            {if $item.seeAlso|@count > 0}
              <div class="alphaBrowseRelatedHeading">
                <div class="title">{translate text="See also"}:</div>
                <ul>
                  {foreach from=$item.seeAlso item=heading}
                    <li><a href="{$path}/AlphaBrowse/Results?source={$source|escape:"url"}&amp;from={$heading|escape:"url"}">{$heading|escape:"html"}</a></li>
                  {/foreach}
                </ul>
              </div>
            {/if}

            {if $item.note}
              <div class="alphaBrowseRelatedHeading">
                <div class="title">{translate text="Note"}:</div>
                <ul>
                  <li>{$item.note|escape:"html"}</li>
                </ul>
              </div>
            {/if}

            </div>
          {/foreach}

          {$smarty.capture.pagelinks}

        </div>
      {else}
        {* spacer div so that empty form page isn't too small: *}
        <div style="height:350px; width: 1px;"><!-- empty --></div>
      {/if}
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>
