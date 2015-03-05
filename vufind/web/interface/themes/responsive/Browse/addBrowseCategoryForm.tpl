<div>
	<div id="createBrowseCategoryComments">
		<p>
			Please enter a name for the browse category to be created.
		</p>
	</div>
	<form method="post" name="createBrowseCategory" id="createBrowseCategory" action="{$path}/Browse/AJAX" class="form">
		<div>
			<input type="hidden" name="searchId" value="{$searchId}" id="searchId" />
			<input type="hidden" name="method" value="createBrowseCategory" />
			<div class="form-group">
				<label for="categoryName" class="control-label">New Category Name</label>
				<input type="text" id="categoryName" name="categoryName" value="" class="form-control"/>
			</div>
		</div>
	</form>
</div>