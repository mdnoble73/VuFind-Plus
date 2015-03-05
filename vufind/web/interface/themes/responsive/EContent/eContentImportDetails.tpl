{strip}
<div id="main-content" class="col-sm-12">
	<h2>eContent Import Details Report</h2>
	{if $error}
		<div class=" error">{$error}</div>
	{else}
		<div>
			<fieldset>
				<legend>Filters:</legend>
				<form action="{$path}/EContent/EContentImportDetails" method="get">
					<div class="row">
						<div class="col-sm-4">
							<div class="form-group">
								<label for="startDate">From</label>
								<input type="text" id="startDate" name="startDate" value="{$startDate}" size="10" class="form-control"/>
							</div>
							<div class="form-group">
								<label for="endDate">To</label>
								<input type="text" id="endDate" name="endDate" value="{$endDate}" size="10" class="form-control"/>
							</div>
						</div>

						<div class="col-sm-4 form-group">
							<label for="publisherFilter">Publisher</label>
							<select id="publisherFilter" name="publisherFilter[]" multiple="multiple" size="5" class="form-control">
								{foreach from=$publisherFilter item=publisher}
									<option value="{$publisher|escape}" {if in_array($publisher,$selectedPublisherFilter)}selected='selected'{/if}>{$publisher|escape}</option>
								{/foreach}
							</select>
						</div>

						<div class="col-sm-4 form-group">
							<label for="statusFilter">Status</label>
							<select id="statusFilter" name="statusFilter[]" multiple="multiple" size="5" class="form-control">
								{foreach from=$statusFilter item=status}
									<option value="{$status|escape}" {if in_array($status,$selectedStatusFilter)}selected='selected'{/if}>{$status|translate|escape}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div class="row">
						<div class="form-group col-sm-12">
							<label for="packagingIds">Packaging IDs (comma separated)</label>
							<input id="packagingIds" size="70" type="text" name="packagingIds" value="{$packagingIds}" class="form-control"/>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<input type="submit" name="submit" value="Update Filters" class="btn btn-sm btn-info"/>
						</div>
					</div>
				</form>
			</fieldset>
		</div>
	{/if}

	{$importDetailsTable}

	{if $pageLinks.all}
		<div class="pagination">{$pageLinks.all}</div>
	{/if}

	{* Export to Excel option *}
	<form action="{$fullPath}" method="get">
		<input type="hidden" name="startDate" value="{$startDate}"/>
		<input type="hidden" name="endDate" value="{$endDate}"/>
		{foreach from=$selectedPublisherFilter item=publisher}
			<input type="hidden" name="publisherFilter[]" value="{$publisher|escape}"/>
		{/foreach}
		{foreach from=$selectedStatusFilter item=status}
			<input type="hidden" name="statusFilter[]" value="{$status|escape}"/>
		{/foreach}
		<input type="hidden" name="packagingIds" value="{$packagingIds}"/>

		<div>
			<input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel" class="btn btn-sm btn-default">
		</div>
	</form>
</div>

<script type="text/javascript">
	{literal}
	$("#startDate").datepicker();
	$("#endDate").datepicker();
	function popupDetails(id) {
		$('#detailsDialog').load(path + '/EContent/AJAX?method=getEContentImportDetails&id=' + id)
						.dialog({modal: true, title: 'eContent Import Details', width: 800, height: 500});
	}
	{/literal}
</script>
<div id="detailsDialog"></div>
{/strip}