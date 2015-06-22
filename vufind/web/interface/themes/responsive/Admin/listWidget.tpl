	<div id="main-content" class="col-md-12">
		<h3>List Widget</h3>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets">All Widgets</a>
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets?objectAction=edit&amp;id={$object->id}"/>Edit</a>
			<a class="btn btn-sm btn-default" href="{$path}/API/SearchAPI?method=getListWidget&amp;id={$object->id}"/>Preview</a>
			<a class="btn btn-sm btn-danger" href="{$path}/Admin/ListWidgets?objectAction=delete&amp;id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');"/>Delete</a>
		</div>
		{* Show details for the selected widget *}
		<h2>{$object->name}</h2>
		<hr>
		<h4>Available to</h4>
		<div id='selectedWidgetLibrary' class="well well-sm">{$object->getLibraryName()}</div>
		<h4>Description</h4>
		<div id='selectedWidgetDescription' class="well well-sm">{$object->description}</div>
		<h4>Style Sheet</h4>
		<div id='selectedWidgetCss' class="well well-sm">{if $object->customCss}{$object->customCss}{else}No custom css defined{/if}</div>
		<h4>Widget Style</h4>
		{assign var=selectedStyle value=$object->style}
		<div id='selectedWidgetDisplayType' class="well well-sm">{$object->styles.$selectedStyle}</div>
		<h4>Display Type</h4>
		{assign var="selectedDisplayType" value=$object->listDisplayType}
		<div id='selectedWidgetDisplayType' class="well well-sm">{$object->displayTypes.$selectedDisplayType}</div>
		
		{if count($object->lists) > 0}
			<h4 id='selectedWidgetListsHeader'>Lists</h4>
			<table id="selectedWidgetLists" class="table table-bordered">
			<thead>
			<tr><th>Name</th><th>Display For</th><th>Source</th></tr>
			</thead>
			<tbody>
			{foreach from=$object->lists item=list}
				<tr class="sortable" id="{$list->id}">
				<td>{$list->name}</td>
				<td>{$list->displayFor}</td>
				<td>{$list->source}</td>
				</tr>
			{/foreach}
			</tbody>
			</table>
		{else}
			<p>This widget has no lists defined for it.</p>
		{/if}
		<div id="listWidgetHelp">
			<h4>Integration notes</h4>
			<div class="well">
				<p>To integrate this widget into another site, insert an iFrame into your site with a source of :</p>
				<p style="font-weight: bold;">{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}</p>
				<p><code style="white-space: normal">&lt;iframe src=&quot;{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}&quot;
						width=&quot;{$width}&quot; height=&quot;{$height}&quot;
						scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
					</code></p>
				<p>Width and height can be adjusted as needed to fit within your site.</p>
				<br>
				<p class="alert alert-warning"> Note: Please avoid using percentages for the iframe width &amp; height as these values are not respected on iPads.</p>
				<p class="alert alert-warning"> Note: Text Only List Widgets use the iframe's scrollbar.</p>
			</div>
		</div>
		<h4>Live Preview</h4>
		<iframe src="{$url}/API/SearchAPI?method=getListWidget&id={$object->id}" width="{$width}" height="{$height}" scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}">
			<p>Your browser does not support iframes. :( </p>
		</iframe>
	</div>
