{if !empty($facets)}
  {foreach from=$facets item=facet}
    <li>
      <a style="float: right; font-size:70%;" href="{$url}/Search/Results?lookfor=%22{$facet.0|escape:"url"}%22&amp;type={$facet_field|escape:"url"}&amp;filter[]={$query|escape:"url"}">{translate text='View Records'}</a>
      {if $next_query_field}
        <a href="" onClick="highlightBrowseLink(this); LoadOptions(&quot;{$facet_field|escape:"url"}:%22{$facet.0|escape:"url"}%22+AND+{$query|escape:"url"}&quot;, '{$next_facet_field|escape}', '{$next_target|escape}'); return false;">{$facet.0|escape} ({$facet.1})</a>
      {else}
        <a href="{$url}/Search/Results?lookfor=%22{$facet.0|escape:"url"}%22&amp;type={$facet_field|escape:"url"}&amp;filter[]={$query|escape:"url"}">{$facet.0|escape} ({$facet.1})</a>
      {/if}
    </li>
  {/foreach}
{/if}