{strip}
<div id="main-content" class="col-md-12">
		<h3>List Widget</h3>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets">All Widgets</a>
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets?objectAction=edit&amp;id={$object->id}">Edit</a>
			<a class="btn btn-sm btn-default" href="{$path}/API/SearchAPI?method=getListWidget&amp;id={$object->id}">Preview</a>
			{if $canDelete}
				<a class="btn btn-sm btn-danger" href="{$path}/Admin/ListWidgets?objectAction=delete&amp;id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');">Delete</a>
			{/if}
		</div>
		{* Show details for the selected widget *}
		<h2>{$object->name}</h2>
		<hr>
		<h4>Available to</h4>
		<div id="selectedWidgetLibrary" class="well well-sm">{$object->getLibraryName()}</div>
		<h4>Description</h4>
		<div id="selectedWidgetDescription" class="well well-sm">{$object->description}</div>
		<h4>Style Sheet</h4>
		<div id="selectedWidgetCss" class="well well-sm">{if $object->customCss}{$object->customCss}{else}No custom css defined{/if}</div>
		<h4>Widget Style</h4>
		{assign var=selectedStyle value=$object->style}
		<div id="selectedWidgetDisplayType" class="well well-sm">{$object->styles.$selectedStyle}</div>
		<h4>Display Type</h4>
		{assign var="selectedDisplayType" value=$object->listDisplayType}
		<div id="selectedWidgetDisplayType" class="well well-sm">{$object->displayTypes.$selectedDisplayType}</div>
		
		{if count($object->lists) > 0}
			<h4 id="selectedWidgetListsHeader">Lists</h4>
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
				<blockquote class="alert-info" style="font-weight: bold;">{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}</blockquote>
				<p>
					<code style="white-space: normal">&lt;iframe src=&quot;{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}&quot;
						width=&quot;{$width}&quot; height=&quot;{$height}&quot;
						scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
					</code>
				</p>
				<p>Width and height can be adjusted as needed to fit within your site.</p>
				<blockquote class="alert-warning"> Note: Please avoid using percentages for the iframe width &amp; height as these values are not respected on iPads and other iOS devices & browsers.</blockquote>
				<blockquote class="alert-warning"> Note: Text Only List Widgets use the iframe's scrollbar.</blockquote>
			</div>
		</div>

		<h4>Live Preview</h4>

		<iframe src="{$url}/API/SearchAPI?method=getListWidget&id={$object->id}" width="{$width}" height="{$height}" scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}">
			<p>Your browser does not support iframes. :( </p>
		</iframe>
	<hr>
		<h3>List Widget with Resizing </h3>
		<h4>Integration notes</h4>
		<div class="well">
			<p>
				To have a list widget which adjusts it's height based on the html content within the list widget use the source url :
			</p>
			<blockquote class="alert-info">
			{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}<span style="font-weight: bold;">&resizeIframe=on</span>
			</blockquote>
			<p>
				Include the iframe tag and javascript tags in the site :
			</p>
			<p>
				{/strip}
<code style="white-space: normal">
	&lt;iframe id=&quot;listWidget{$object->id}&quot; src=&quot;{$url}/API/SearchAPI?method=getListWidget&amp;id={$object->id}&amp;resizeIframe=on&quot;
	width=&quot;{$width}&quot; scrolling=&quot;{if $selectedStyle == "text-list"}yes{else}no{/if}&quot;&gt;&lt;/iframe&gt;
</code>
<code style="white-space: normal">
	&lt;script type=&quot;text/javascript&quot; src=&quot;{$url}/js/iframeResizer/iframeResizer.min.js&quot;&gt;&lt;/script&gt;
</code>
<code style="white-space: pre">
&lt;script type=&quot;text/javascript&quot;&gt;
	jQuery(&quot;#listWidget{$object->id}&quot;).iFrameResize();
&lt;/script&gt;
</code>
				{strip}
			</p>
			<blockquote class="alert-warning">
				This requires that the site displaying the list widget have the jQuery library.
			</blockquote>

		</div>
	<h4>Live Preview</h4>
	<iframe id="listWidget{$object->id}" src="{$url}/API/SearchAPI?method=getListWidget&id={$object->id}&resizeIframe=on" width="{$width}" {*height="{$height}"*} scrolling="{if $selectedStyle == "text-list"}yes{else}no{/if}">
		<p>Your browser does not support iframes. :( </p>
	</iframe>

	{* Iframe dynamic Height Re-sizing script *}
	<script type="text/javascript" src="{$path}/js/iframeResizer/iframeResizer.min.js"></script>
	{/strip}

	{* Width Resizing Code *}
<script type="text/javascript">
	jQuery('#listWidget{$object->id}').iFrameResize();
</script>
	{*
	<script type="text/javascript">
	resizeWidgetWidth = function(iframe, padding){
		if (padding == undefined) padding = 4;
		var viewPortWidth = jQuery(window).width(),
				newWidth = viewPortWidth - 2*padding,
				width = jQuery(iframe).width();
		/*console.log('viewport Width', viewPortWidth, 'new', newWidth, 'current', width );*/
		if (width > newWidth) {
			wasResized = true;
			return jQuery(iframe).width(newWidth);
		}
		if (wasResized && originalWidth + 2*padding < viewPortWidth){
			wasResized = false;
			return jQuery(iframe).width(originalWidth);
		}
	};

	setWidgetSizing = function(elem, OutsidePadding){
		originalWidth = jQuery(elem).width();
		wasResized = false;
		/*console.log('Original Width', originalWidth );*/
		jQuery(window).resize(function(){
			resizeWidgetWidth(iframe, padding);
		}).resize();
	}
</script>
{/literal}*}

	<br>
		<div class="alert alert-info">
			For more information on how to create List Widgets, please see the <a href="https://docs.google.com/document/d/1RySv7NbaYjaw_F9Gs7cP9pu3P894s_4J05o46m6z3bQ">online documentation</a>
		</div>
	</div>
