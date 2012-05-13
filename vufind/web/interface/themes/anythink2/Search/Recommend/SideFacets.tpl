{if $recordCount > 0 || $filterList || ($sideFacetSet && $recordCount > 0)}
<div class="sidegroup">
  <h2>{translate text='Narrow Search'}</h2>
  {if isset($checkboxFilters) && count($checkboxFilters) > 0}
    <table>
      {foreach from=$checkboxFilters item=current}
          <tr{if $recordCount < 1 && !$current.selected} style="display: none;"{/if}>
            <td style="vertical-align:top; padding: 3px;">
              <input type="checkbox" name="filter[]" value="{$current.filter|escape}"
                {if $current.selected}checked="checked"{/if}
                onclick="document.location.href='{$current.toggleUrl|escape}';" />
            </td>
            <td>
              {translate text=$current.desc}<br />
            </td>
          </tr>
      {/foreach}
    </table>
  {/if}
  {if $filterList}
    <div id="active-filters-wrapper">
      <h3>{translate text='Active Filters'}</h3>
      <ul id="active-filters">
      {foreach from=$filterList item=filters key=field}
        {foreach from=$filters item=filter}
          <li><a href="{$filter.removalUrl|escape}" title="{translate text='Remove'}" class="remove-facet">{translate text=$field}: {$filter.display|escape}</a></li>
        {/foreach}
      {/foreach}
      </ul>
      <p class="small fine-print"><em>Click to remove</em></p>
    </div>
  {/if}
  {if $sideFacetSet && $recordCount > 0}
    {foreach from=$sideFacetSet item=cluster key=title}
    <div id="facet-{$cluster.label|escape:'html'|replace:' ':'-'}" class="facet-group cf">
      <h3 >{translate text=$cluster.label}</h3>
      {if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear'}
          <form id='{$title}Filter' action='{$fullPath}'>
            <div>
            <label for="{$title}yearfrom" class='yearboxlabel'>From:</label>
            <input type="text" size="4" maxlength="4" class="yearbox year-from" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
            <label for="{$title}yearto" class='yearboxlabel'>To:</label>
            <input type="text" size="4" maxlength="4" class="yearbox year-to" name="{$title}yearto" id="{$title}yearto" value="" />
            {* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
            {foreach from=$smarty.get item=parmValue key=paramName}
              {if is_array($smarty.get.$paramName)}
                {foreach from=$smarty.get.$paramName item=parmValue2}
                  {* Do not include the filter that this form is for. *}
                  {if strpos($parmValue2, $title) === FALSE}
                    <input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
                  {/if}
                {/foreach}
              {else}
                <input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
              {/if}
            {/foreach}
            <input type="submit" value="Go" id="goButton" />
            <br/>
            {if $title == 'publishDate'}
            <ul class='inline left small'>
              <li><a class="prefill" data-year="2005" href='#'>Since 2005</a></li>
              <li><a class="prefill" data-year="2000" href='#'>Since 2000</a></li>
              <li><a class="prefill" data-year="1995" href='#'>Since 1995</a></li>
            </ul>
            {/if}
          </div>
          </form>
      {elseif $title == 'rating_facet'}
          <ul>
          {foreach from=$ratingLabels item=curLabel}
            {assign var=thisFacet value=$cluster.list.$curLabel}
            {if $thisFacet.isApplied}
              {if $curLabel == 'Unrated'}
                <li>{$thisFacet.value|escape} <a href="{$thisFacet.removalUrl|escape}" class="active-facet small" title="{translate text='Remove'}">(remove)</a></li>
              {else}
                <li><img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate}"/> <a href="{$thisFacet.removalUrl|escape}" class="active-facet small" title="{translate text='Remove'}">(remove)</a></li>
              {/if}
            {else}
              {if $curLabel == 'Unrated'}
                <li>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}&nbsp;({$thisFacet.count})</li>
              {else}
                <li>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}<img src="{$path}/images/{$curLabel}.png" alt="{$curLabel|translate}"/>{if $thisFacet.url !=null}</a>{/if}&nbsp;({if $thisFacet.count}{$thisFacet.count}{else}0{/if})</li>
              {/if}
            {/if}
          {/foreach}
          </ul>
      {elseif $title == 'lexile_score' || $title == 'accelerated_reader_reading_level' || $title == 'accelerated_reader_point_value'}
          <form id='{$title}Filter' action='{$fullPath}'>
            <div>
            <label for="{$title}from" class='yearboxlabel'>From:</label>
            <input type="text" size="4" maxlength="4" class="yearbox" name="{$title}from" id="{$title}from" value="" />
            <label for="{$title}to" class='yearboxlabel'>To:</label>
            <input type="text" size="4" maxlength="4" class="yearbox" name="{$title}to" id="{$title}to" value="" />
            {* To make sure that applying this filter does not remove existing filters we need to copy the get variables as hidden variables *}
            {foreach from=$smarty.get item=parmValue key=paramName}
              {if is_array($smarty.get.$paramName)}
                {foreach from=$smarty.get.$paramName item=parmValue2}
                  {* Do not include the filter that this form is for. *}
                  {if strpos($parmValue2, $title) === FALSE}
                    <input type="hidden" name="{$paramName}[]" value="{$parmValue2|escape}" />
                  {/if}
                {/foreach}
              {else}
                <input type="hidden" name="{$paramName}" value="{$parmValue|escape}" />
              {/if}
            {/foreach}
            <input type="submit" value="Go" id="goButton" />
          </div>
          </form>
      {else}
          <ul>
          {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
            {if false && $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
            <li id="more{$title}"><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></li>
            {/if}
            <li {if $smarty.foreach.narrowLoop.iteration > $cluster.valuesToShow}class="less"{/if}>
            {if $thisFacet.isApplied}
              {$thisFacet.value|escape} <a href="{$thisFacet.removalUrl|escape}" class="active-facet small" title="{translate text='Remove'}">(remove)</a>
            {else}
              {if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if}&nbsp;({$thisFacet.count})
            {/if}
            </li>
          {/foreach}
          </ul>
          {if false && $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}<li><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></li>{/if}
      {/if}
      </div>
    {/foreach}
  {/if}
</div>
{/if}