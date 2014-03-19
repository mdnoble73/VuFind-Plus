<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/js/econtent.js" ></script>
<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>{if $isNew}Add an eContentRecord File{else}Edit eContentRecord File{/if}</h1>
    {$editForm}
  </div>
</div>