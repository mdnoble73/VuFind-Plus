{strip}
	<div class="col-xs-12">
		{if $shortPageTitle || $objectName}
			<h2>{$shortPageTitle} - {$objectName}</h2>
		{/if}
		<p>
			{if $showReturnToList}
				<a class="btn btn-default" href='{$path}/{$module}/{$toolName}?objectAction=list'>Return to List</a>
			{/if}
			{if $id > 0 && $canDelete}<a class="btn btn-danger" href='{$path}/{$module}/{$toolName}?id={$id}&amp;objectAction=delete' onclick='return confirm("Are you sure you want to delete this {$objectType}?")'>Delete</a>{/if}
		</p>
		<div class="btn-group">
			{foreach from=$additionalObjectActions item=action}
				<a class="btn btn-default btn-sm" href='{$action.url}'>{$action.text}</a>
			{/foreach}
		</div>
		{include file="DataObjectUtil/objectEditForm.tpl"}
	</div>
{/strip}