{if $subTemplate == '../MyResearch/login.tpl'}
  {include file="MyResearch/login.tpl"}
{else}
  <div data-role="page" id="Record-view">
    {include file="header.tpl"}
    <div class="record" data-role="content" data-record-id="{$id}">
      {include file="Record/$subTemplate"}
    </div>    
    {include file="footer.tpl"}
  </div>
{/if}
