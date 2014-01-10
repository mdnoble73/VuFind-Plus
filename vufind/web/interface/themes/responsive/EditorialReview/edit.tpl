<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/ckeditor/ckeditor.js"></script>
<div id="page-content" class="row">
  <div id="sidebar" class="col-md-3">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content" class="col-md-9">
    <h1>{if $isNew}Add an Editorial Review{else}Edit an Editorial Review{/if}</h1>
    {$editForm}
  </div>
</div>