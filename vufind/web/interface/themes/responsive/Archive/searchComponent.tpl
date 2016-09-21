{strip}
	<div class="archiveComponentContainer nopadding col-sm-12 col-md-6">
		<div class="archiveComponent">
			<div class="archiveComponentHeader">Search This Collection</div>
			<div class="archiveComponentBody">
				<div class="archiveComponentBox">
					<form action="/Archive/Results">
						<div class="input-group">
							<input type="text" name="lookfor" size="30" title="Enter one or more terms to search for.	Surrounding a term with quotes will limit result to only those that exactly match the term." autocomplete="off" class="form-control" placeholder="">
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
{/strip}