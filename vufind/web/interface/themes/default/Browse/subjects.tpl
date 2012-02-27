{if !empty($facets)}
  {foreach from=$facets item=facet}
    <li>
      <a style="float: right; font-size:70%;" href="{$url}/Search/Results?lookfor=%22{$facet.0|escape:"url"}%22&amp;type={$facet_field|escape:"url"}">{translate text='View Records'}</a>
      <a href="" onClick="highlightBrowseLink(this); LoadOptions(&quot;{$facet_field|escape:"url"}:%22{$facet.0|escape:"url"}%22&quot;, '{$query_field|escape}', 'list4'); return false;">{$facet.0|escape} ({$facet.1})</a>
    </li>
  {/foreach}
{/if}
