<div id="main-content">
  <p class="intro">Welcome to the Anythink catalog! Find what you're looking for by using the search box.<br/>Need suggestions? Browse for what's hot by clicking on the arrows below or click on the item covers for more details.</p>
  {if $module=="Summon"}
    {include file="Summon/searchbox.tpl"}
  {elseif $module=="WorldCat"}
    {include file="WorldCat/searchbox.tpl"}
  {else}
    {include file="Search/searchbox.tpl"}
  {/if}
  {include file='API/listWidgetTabs.tpl'}
</div>
