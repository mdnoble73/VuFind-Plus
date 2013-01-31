<div id="main-content">
  <p class="intro">Welcome to the Anythink catalog! Find what you're looking for by using the search box.</p>
  <div class="clearfix">
    {if $module=="Summon"}
      {include file="Summon/searchbox.tpl"}
    {elseif $module=="WorldCat"}
      {include file="WorldCat/searchbox.tpl"}
    {else}
      {include file="Search/searchbox.tpl"}
    {/if}
    <div class="request-home"><div class="inner">
      <strong>Can't find what you're looking for?</strong>
      <p>Request new materials for our collection or get it via Interlibrary Loan.</li>
      <div class="action"><a class="button" href="{$path}/MaterialsRequest/NewRequest">Request it</a></div>
    </div></div>
  </div>
</div>
  <div class="clearfix">
    <p class="intro">Need suggestions? Browse for what's hot by clicking on the arrows below or click on the item covers for more details.</p>
  </div>
  {include file='API/listWidgetTabs.tpl'}
</div>
