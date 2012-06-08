{css filename="listWidget.css"}
{if $error}<p class="error">{$error}</p>{/if} 
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>Edit List Widget</h1>
  <a class="button" href="{$path}/Admin/ListWidgets">All Widgets</a>
  <a class="button" href="{$path}/Admin/ListWidgets?objectAction=view&id={$object->id}"/>View</a> 
  <a class="button" href="{$path}/API/SearchAPI?method=getListWidget&id={$object->id}"/>Preview</a>
  <a class="button" href="{$path}/Admin/ListWidgets?objectAction=delete&id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');"/>Delete</a>
  {$editForm}
</div>
{if $edit}
<script type="text/javascript">{literal}
	$(document).ready(function(){
		$('#selectedWidgetLists tbody').sortable({
			update: function(event, ui){
				var listOrder = $(this).sortable('toArray').toString();
				alert("ListOrder = " + listOrder);
			}
		});
		$('#selectedWidgetLists tbody').disableSelection();
	});{/literal}
</script>
{/if}
