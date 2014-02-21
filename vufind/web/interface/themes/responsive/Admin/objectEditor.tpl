<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
	
	<div id="main-content" class="col-md-9">
		<h2>{$shortPageTitle} - {$objectName}</h2>
		{if $id > 0 && $canDelete}<a class="btn" href='{$path}/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("Are you sure you want to delete this {$objectType}?")'>Delete</a>{/if}
		{if $showReturnToList} 
			<a class="btn" href='{$path}/{$module}/{$toolName}?objectAction=list'>Return to List</a>
		{/if}
		<br/>
		{foreach from=$additionalObjectActions item=action}
			<a class="btn btn-sm" href='{$action.url}'>{$action.text}</a>
		{/foreach}
		<br />
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
</div>