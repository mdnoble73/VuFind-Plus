{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent">
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<img src="{$searchComponentImage}" width="100" height="100" alt="Search" class="archiveComponentImage">
					<div class="archiveComponentControls">
						<div class="archiveComponentHeader">Search This Collection</div>
						<form action="/Archive/Results" id="searchComponentForm">
							<div class="input-group">
								<input type="text" name="lookfor" size="25" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." autocomplete="off" class="form-control" placeholder="">
								<div class="input-group-btn" id="search-actions">
									<button class="btn btn-default" type="submit">GO</button>
								</div>
								<input type="hidden" name="islandoraType" value="IslandoraKeyword"/>
								{if count($subCollections) > 0}
									<input type="hidden" name="filter[]" value='RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$pid}"{foreach from=$subCollections item=subCollectionPID} OR RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$subCollectionPID}"{/foreach}'/>
								{else}
									<input type="hidden" name="filter[]" value='RELS_EXT_isMemberOfCollection_uri_ms:"info:fedora/{$pid}"'/>
								{/if}
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
{/strip}