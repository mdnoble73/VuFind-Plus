<div>
	<div id="createWidgetComments">
		<p>
			{if count($existingWidgets) > 0}
				You may either add this {$source} to an existing widget as a new tab, <br/> or you may create a new widget to display this {$source} in.
			{else}
				Please enter a name for the widget to be created.
			{/if}
		</p>
	</div>
	<form method="post" name="bulkAddToList" action="{$path}/Admin/CreateListWidget">
		<div>
			<input type="hidden" name="source" value="{$source}" />
			<input type="hidden" name="id" value="{$id}" />
			{if count($existingWidgets) > 0}
				<label for="widget"><b>Select a widget</b></label>: 
				<select id="widgetId" name="widgetId">
					<option value="-1">Create a new widget</option>
					{foreach from=$existingWidgets item=widgetName key=widgetId}
						<option value="{$widgetId}">{$widgetName}</option>
					{/foreach}
				</select><br/>
			{/if}
			<label for="widgetName"><b>New Widget Name</b></label>: <input type="text" id="widgetName" name="widgetName" value="" />
			<br/>
			<input type="submit" value="Create Widget" />
		</div>
	</form>
</div>