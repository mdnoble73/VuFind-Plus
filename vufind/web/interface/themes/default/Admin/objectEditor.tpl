<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    <h1>{$shortPageTitle} - {$objectName}</h1>
    {if $id > 0}<a class="button" href='{$url}/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("Are you sure you want to delete this {$objectType}?")'>Delete</a>{/if} <a class="button" href='{$url}/{$module}/{$toolName}?objectAction=list'>Return to List</a>
    <br />
    {include file="DataObjectUtil/objectEditForm.tpl"}
  </div>
</div>