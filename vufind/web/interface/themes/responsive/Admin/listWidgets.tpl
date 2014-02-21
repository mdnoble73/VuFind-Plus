{strip}
<div id="page-content" class="row">
	{if $error}<p class="error">{$error}</p>{/if} 
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
	<div id="main-content" class="col-md-9">
		<h3>Available List Widgets</h3>
		<div id="widgets"></div>
		{* Select a widget to edit *}
		<div id="availableWidgets"> 
		<table class="citation"> 
		<thead><tr><th>Id</th><th>Name</th><th>Library</th><th>Description</th><th>Actions</th></tr></thead>
		<tbody>
			{foreach from=$availableWidgets key=id item=widget}
				<tr><td>{$widget->id}</td><td>{$widget->name}</td><td>{$widget->getLibraryName()}</td><td>{$widget->description}</td><td>
					<div class="btn-group">
						<a class="btn" href="{$path}/Admin/ListWidgets?objectAction=view&id={$widget->id}"/>View</a>
						<a class="btn" href="{$path}/Admin/ListWidgets?objectAction=edit&id={$widget->id}"/>Edit</a>
						<a class="btn" href="{$path}/API/SearchAPI?method=getListWidget&id={$widget->id}"/>Preview</a>
						{if $canDelete}
							<a class="btn" href="{$path}/Admin/ListWidgets?objectAction=delete&id={$widget->id}" onclick="return confirm('Are you sure you want to delete {$widget->name}?');"/>Delete</a>
						{/if}
					</div>
				</td>
			{/foreach}
		</tbody>
		</table>
		{if $canAddNew}
			<input type="button" class="btn" name="addWidget" value="Add Widget" onclick="window.location = '{$path}/Admin/ListWidgets?objectAction=add';"/>
		{/if}
		</div>
	</div>
</div>
{/strip}