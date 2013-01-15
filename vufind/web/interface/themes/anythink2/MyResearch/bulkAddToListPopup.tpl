<div>
	<div id="addToListComments">
	Please enter one or more titles or ISBNs to add to your list.  
	Each title or ISBN should be on it's own line.
	We will search the catalog for each title and add the first matching title for each list to your list. 
	</div>
	<form method="post" name="bulkAddToList" action="{$path}/MyResearch/MyList/{$listId}">
		<div>
			<input type="hidden" name="myListActionHead" value="bulkAddTitles" />
			<textarea rows="5" cols="40" name="titlesToAdd"></textarea>
			<input type="submit" value="Add to List" />
		</div>
	</form>
</div>