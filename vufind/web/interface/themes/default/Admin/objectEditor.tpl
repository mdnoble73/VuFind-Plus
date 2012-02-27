<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>{$shortPageTitle} - {$objectName}</h1>
    <br />
    {include file="DataObjectUtil/objectEditForm.tpl"}
  </div>
</div>