{strip}
<div id="page-content" class="content">
  {if $error}<p class="error">{$error}</p>{/if} 
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}

    {include file="Admin/menu.tpl"}
  </div>
  <div id="main-content">
    <h1>Available List Widgets</h1>
    <div id="widgets"></div>
    {* Select a widget to edit *}
    <div id="availableWidgets"> 
    <table class="citation"> 
    <thead><tr><th>Id</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
    	{foreach from=$availableWidgets key=id item=widget}
    		<tr><td>{$widget->id}</td><td>{$widget->name}</td><td>{$widget->description}</td><td>
    		<a class="button" href="{$path}/Admin/ListWidgets?objectAction=view&id={$widget->id}"/>View</a> 
    		<a class="button" href="{$path}/Admin/ListWidgets?objectAction=edit&id={$widget->id}"/>Edit</a>
    		<a class="button" href="{$path}/API/SearchAPI?method=getListWidget&id={$widget->id}"/>Preview</a>
    		<a class="button" href="{$path}/Admin/ListWidgets?objectAction=delete&id={$widget->id}" onclick="return confirm('Are you sure you want to delete {$widget->name}?');"/>Delete</a></td>
    	{/foreach}
    </tbody>
    </table>
    <input type="button" class="button" name="addWidget" value="Add Widget" onclick="window.location = '{$path}/Admin/ListWidgets?objectAction=add';"/>
    </div>
  </div>
</div>
{/strip}