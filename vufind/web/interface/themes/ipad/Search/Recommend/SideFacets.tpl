{if !empty($sideFacetSet)}
<div data-role="dialog" id="Search-narrow">
  <div data-role="header" data-theme="d" data-position="inline">
    <h1>{translate text="Narrow Search"}</h1>
  </div>
  <div data-role="content">
    <div data-role="collapsible-set" class="narrow-search">
      {foreach from=$sideFacetSet item=cluster key=title name="facetLoop"}
      <div data-role="collapsible" data-collapsed="{if $smarty.foreach.facetLoop.first}false{else}true{/if}">
        <h4>{translate text=$cluster.label}</h4>
        <ul class="narrow" data-role="listview" data-inset="true">
          {foreach from=$cluster.list item=thisFacet name="narrowLoop"}
            {if $thisFacet.isApplied}
              <li data-icon="check" class="checked"><a href="" data-rel="back">{$thisFacet.value|escape}</a> <span class="ui-li-count">{$thisFacet.count}</span></li>
            {else}
              <li><a rel="external" href="{$thisFacet.url|escape}">{$thisFacet.value|escape}</a> <span class="ui-li-count">{$thisFacet.count}</span></li>
            {/if}
          {/foreach}
        </ul>
      </div>
      {/foreach}      
    </div>
  </div>
</div>
{/if}
