<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/ckeditor/ckeditor.js"></script>
<div id="page-content" class="row-fluid">
  <div id="sidebar" class="span3">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content" class="span9">
    <h1>{if $isNew}Add an Editorial Review{else}Edit an Editorial Review{/if}</h1>
    {$editForm}
  </div>
</div>