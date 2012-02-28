{if $recordCount > 0 || $filterList || ($sideFacetSet && $recordCount > 0)}
    <div class="sidegroup" id="titleDetailsSidegroup">
      <h4>{translate text="Limit Results"}</h4>
      <div class="sidegroupContents">
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
    <span class="sidebarLabel"><strong>{translate text='Remove Filters'}</strong></span>
    <ul class="filters">
    {foreach from=$filterList item=filters key=field}
        {foreach from=$filters item=filter}
      <li>{translate text=$field}: {$filter.display|escape} <a href="{$filter.removalUrl|escape}"><img src="{$path}/images/silk/delete.png" alt="Delete"/></a></li>
        {/foreach}
    {/foreach}
    </ul>
  {/if}
  {if $sideFacetSet && $recordCount > 0}
    {foreach from=$sideFacetSet item=cluster key=title}
      {if $title == 'publishDate' || $title == 'birthYear' || $title == 'deathYear'}
        <dl class="narrowList navmenu narrow_begin">
          <dt>{translate text=$cluster.label} </dt>
          <dd>
	          <form id='{$title}Filter' action='{$fullPath}'>
	            <div>
		          <label for="{$title}yearfrom" class='yearboxlabel'>From:</label>
		          <input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearfrom" id="{$title}yearfrom" value="" />
		          <label for="{$title}yearto" class='yearboxlabel'>To:</label>
		          <input type="text" size="4" maxlength="4" class="yearbox" name="{$title}yearto" id="{$title}yearto" value="" />
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
		          <div id='yearDefaultLinks'>
			          <a onclick="$('#{$title}yearfrom').val('2005');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2005</a>
			          &bull;<a onclick="$('#{$title}yearfrom').val('2000');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;2000</a>
			          &bull;<a onclick="$('#{$title}yearfrom').val('1995');$('#{$title}yearto').val('');" href='javascript:void(0);'>since&nbsp;1995</a>
		          </div>
		          {/if}
		        </div>
	          </form>
          </dd>
        </dl>
      {else}
      <dl class="narrowList navmenu narrow_begin">
        <dt>{translate text=$cluster.label}</dt>
	        {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
	          {if $smarty.foreach.narrowLoop.iteration == ($cluster.valuesToShow + 1)}
	          <dd id="more{$title}"><a href="#" onclick="moreFacets('{$title}'); return false;">{translate text='more'} ...</a></dd>
	        </dl>
	        <dl class="narrowList navmenu narrowGroupHidden" id="narrowGroupHidden_{$title}">
	          {/if}
	          {if $thisFacet.isApplied}
	            <dd>{$thisFacet.value|escape} <img src="{$path}/images/silk/tick.png" alt="Selected" /> <a href="{$thisFacet.removalUrl|escape}" class="removeFacetLink">(remove)</a></dd>
	          {else}
	            <dd>{if $thisFacet.url !=null}<a href="{$thisFacet.url|escape}">{/if}{$thisFacet.display|escape}{if $thisFacet.url !=null}</a>{/if} ({$thisFacet.count})</dd>
	          {/if}
	        {/foreach}
	        {if $smarty.foreach.narrowLoop.total > $cluster.valuesToShow}<dd><a href="#" onclick="lessFacets('{$title}'); return false;">{translate text='less'} ...</a></dd>{/if}
	      </dl>
      {/if}
    {/foreach}
  {/if}
  </div>
</div>
{/if}
