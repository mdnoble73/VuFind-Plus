<div id="page-content" class="content">
  {if $error}<p class="error">{$error}</p>{/if} 
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}

    {include file="Admin/menu.tpl"}
  </div>
  <div id="main-content">
    <h1>List Widget</h1>
    <a class="button" href="{$path}/Admin/ListWidgets">All Widgets</a>
    <a class="button" href="{$path}/Admin/ListWidgets?objectAction=edit&amp;id={$object->id}"/>Edit</a>
    <a class="button" href="{$path}/API/SearchAPI?method=getListWidget&amp;id={$object->id}"/>Preview</a>
    <a class="button" href="{$path}/Admin/ListWidgets?objectAction=delete&amp;id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');"/>Delete</a>
    {* Show details for the selected widget *}
    <h2>{$object->name}</h2>
    <div id='selectedWidgetDescription'>{$object->description}</div>
    <div id='selectedWidgetCss'>{if $object->customCss}{$object->customCss}{else}No custom css defined{/if}</div>
    <div id='selectedWidgetDisplayType'>Display lists as: {$object->listDisplayType}</div>
    
    {if count($object->lists) > 0}
	    <div id='selectedWidgetListsHeader'>Lists</div>
	    <table id="selectedWidgetLists">
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
    	<h2>Integration notes</h2>
    	<p>
    	To integrate this widget into another site, insert an iFrame into your site with a source of: <br/> <b>{$path}/API/SearchAPI?method=getListWidget&amp;id={$object->id}</b>.
    	</p>
    	<p>
    	&lt;iframe src=&quot;{$path}/API/SearchAPI?method=getListWidget&amp;id={$object->id}&quot; width=&quot;100%&quot; height=&quot;250&quot;&gt;&lt;/iframe&gt;
    	</p>
			<p>
			Width and height can be adjusted as needed to fit within your site. 
			</p>
    </div>
    
  </div>
</div>