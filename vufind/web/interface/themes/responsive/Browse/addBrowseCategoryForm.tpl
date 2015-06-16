<div>
	<div id="createBrowseCategoryComments">
		<p class="alert alert-info">
			Please enter a name for the browse category to be created.
		</p>
	</div>
	<form method="post" name="createBrowseCategory" id="createBrowseCategory" action="{$path}/Browse/AJAX" class="form">
		<div>
			<input type="hidden" name="searchId" value="{$searchId}" id="searchId">
			<input type="hidden" name="method" value="createBrowseCategory">
			<div class="form-group">
				<label for="categoryName" class="control-label">New Category Name</label>
				<input type="text" id="categoryName" name="categoryName" value="" class="form-control required">
			</div>
			{if $property} {* If data for Select tag is present, use the object editor template to build the <select> *}
			<div class="form-group">
				<label for="make-as-a-sub-category-ofSelect" class="control-label">Add as a Sub-Category to (optional) : </label>
				{include file="DataObjectUtil/enum.tpl"} {* create select list *}
			</div>
			{/if}
		</div>
	</form>
</div>

<script type="text/javascript">
	{literal}
	$("#createBrowseCategory").validate({
		submitHandler: function(){
			VuFind.Browse.createBrowseCategory()
		}
	});
	{/literal}
</script>