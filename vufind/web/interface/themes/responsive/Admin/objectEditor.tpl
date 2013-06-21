<div id="page-content" class="row-fluid">
	<div id="sidebar" class="span3">
		{include file="MyResearch/menu.tpl"}
		
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content" class="span9">
		<h2>{$shortPageTitle} - {$objectName}</h2>
		{if $id > 0 && $canDelete}<a class="btn" href='{$path}/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("Are you sure you want to delete this {$objectType}?")'>Delete</a>{/if}
		{if $showReturnToList} 
			<a class="btn" href='{$path}/{$module}/{$toolName}?objectAction=list'>Return to List</a>
		{/if}
		<br/>
		{foreach from=$additionalObjectActions item=action}
			<a class="btn btn-small" href='{$action.url}'>{$action.text}</a>
		{/foreach}
		<br />
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
</div>