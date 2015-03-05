{css filename="listWidget.css"}

	<div id="main-content">
		<h1>Edit List Widget</h1>
		<div class="btn-group">
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets">All Widgets</a>
			<a class="btn btn-sm btn-default" href="{$path}/Admin/ListWidgets?objectAction=view&id={$object->id}"/>View</a>
			<a class="btn btn-sm btn-default" href="{$path}/API/SearchAPI?method=getListWidget&id={$object->id}"/>Preview</a>
			<a class="btn btn-sm btn-danger" href="{$path}/Admin/ListWidgets?objectAction=delete&id={$object->id}" onclick="return confirm('Are you sure you want to delete {$object->name}?');"/>Delete</a>
		</div>

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