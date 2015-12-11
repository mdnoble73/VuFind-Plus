{strip}
	{* User's viewing mode toggle switch *}
	<div class="row" id="selected-browse-label">{* browse styling replicated here *}
		<div class="btn-group btn-group-sm" data-toggle="buttons">
			<label for="covers" title="Covers" class="btn btn-sm btn-default"><input onchange="VuFind.Searches.toggleDisplayMode(this.id)" type="radio" id="covers">
				<span class="thumbnail-icon"></span><span> Grid</span>
			</label>
			<label for="list" title="Lists" class="btn btn-sm btn-default"><input onchange="VuFind.Searches.toggleDisplayMode(this.id);" type="radio" id="list">
				<span class="list-icon"></span><span> List</span>
			</label>
		</div>
	</div>
{/strip}