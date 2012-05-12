{if $user}
  <div id="sidebar-wrapper"><div id="sidebar">
    {include file="MyResearch/menu.tpl"}
  </div></div>
{/if}
<div id="main-content">
  {if $module=="Summon"}
    {include file="Summon/searchbox.tpl"}
  {elseif $module=="WorldCat"}
    {include file="WorldCat/searchbox.tpl"}
  {else}
    {include file="Search/searchbox.tpl"}
  {/if}
  {include file='API/listWidgetTabs.tpl'}
</div>
