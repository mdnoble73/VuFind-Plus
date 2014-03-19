{strip}
	{if (isset($title)) }
		<script type="text/javascript">
			alert("{$title}");
		</script>
	{/if}
	<div id="page-content" class="content">
		<div id="sidebar">
			{include file="MyResearch/menu.tpl"}
			{include file="Admin/menu.tpl"}
		</div>

		<div id="main-content">
			{if $user}
				<h1>Patron Status Report</h1>
				{foreach from=$errors item=error}
					<div class="error">{$error}</div>
				{/foreach}
				<form id="patronStatusInput" method="post" enctype="multipart/form-data">
					<fieldset>
						<legend>Patron Report Files</legend>
						<label for="patronReport">Patron Report: </label><input type="file" name="patronReport" id="patronReport">
						<br/>
						<label for="itemReport">Item Report: </label><input type="file" name="itemReport" id="itemReport">
						<br/>
						<br/>
						<input type="submit" name="submit" id="submit" value="Generate Report" onclick="return processPatronStatusSubmit();"/>
						<div class="warning" style="display:none" id="patronStatusProcessing">
							Processing the patron status report.  This may take several minutes.  Please do not refresh the page.
						</div>
					</fieldset>
				</form>
			{else}
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			{/if}
		</div>
	</div>
{/strip}
{literal}
<script type="text/javascript">
	function processPatronStatusSubmit(){
		$("#submit").hide();
		$("#patronStatusProcessing").show();

		return true;
	}
</script>
{/literal}