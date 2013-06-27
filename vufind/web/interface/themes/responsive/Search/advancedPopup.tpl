{strip}
{if $savedSearch}
	{$searchTerms|@print_r}
{/if}
<div>
	<form id="advancedPopup" class="modal-form" method="get" action="{$path}/Union/Search">
		<fieldset>
			<div id="advancedSearchRows">
				<div class="control-group advancedRow" id="group1" data-row_number="1">
					<div class="input-prepend">
						<input type="hidden" name="groupStart[1]" id="groupStart1Input" class="groupStartInput" title="Start Group"/>
						<button id="groupStart1" data-toggle="button" data-hidden_element="#groupStart1Input" onclick="return VuFind.toggleHiddenElementWithButton(this);" class="btn groupStartButton">(</button>
						<select name="searchType[1]" class="searchType">
							{foreach from=$advSearchTypes item=searchDesc key=searchVal}
								<option value="{$searchVal}">{$searchDesc|translate}</option>
							{/foreach}
							{foreach from=$facetList item="facet" key="filterName"}
								<option value="{$filterName}">{$facet.label|translate}</option>
							{/foreach}
						</select>
					</div>
					<div class="input-append">
						<input type="text" name="lookfor[1]" class="lookfor" title="Search For" placeholder="Search for"/>
						<input type="hidden" name="groupEnd[1]" id="groupEnd1Input" title="End Group" class="groupEndInput"/>
						<button id="groupEnd1" data-toggle="button" data-hidden_element="#groupEnd1Input" onclick="return VuFind.toggleHiddenElementWithButton(this);" class="btn groupEndButton">)</button>
					</div>
					{/strip} {strip}
					<div class="btn-group input-append">
						<select name="join[1]" class="input-small joinOption">
							<option value="AND">{translate text="AND"}</option>
							<option value="AND NOT">{translate text="AND NOT"}</option>
							<option value="OR">{translate text="OR"}</option>
							<option value="OR NOT">{translate text="OR NOT"}</option>
						</select>
						<button name="addCriteria" class="btn addCriteria" onclick="return VuFind.Searches.addAdvancedGroup(this);" title="Add Criteria">
							<i class="icon-plus-sign"></i>
						</button>
						<button name="deleteCriteria" class="btn deleteCriteria" onclick="return VuFind.Searches.deleteAdvancedGroup(this);" title="Delete Criteria">
							<i class="icon-minus-sign"></i>
						</button>
					</div>
				</div>
			</div>
			<div class="btn-group">
				<button type="submit" name="submit" class="btn btn-primary">{translate text="Find"}</button>
			</div>
		</fieldset>
	</form>
</div>
{/strip}
{literal}
<script type="text/javascript">
	$(document).ready(function(){
		{/literal}
		{foreach from=$searchGroups key=groupIndex item=searchGroup}
		VuFind.Searches.searchGroups[{$groupIndex}] = {literal}{{/literal}
			groupStart: '{$searchGroup.groupStart}',
			lookfor: '{$searchGroup.lookfor}',
			searchType: '{$searchGroup.searchType}',
			groupEnd: '{$searchGroup.groupEnd}',
			join: '{$searchGroup.join}'

			{literal}}{/literal};
		{/foreach}
		{literal}

		VuFind.Searches.loadSearchGroups();
	});
</script>
{/literal}