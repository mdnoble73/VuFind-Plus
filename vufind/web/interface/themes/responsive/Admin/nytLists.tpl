{strip}
	<h1>Create Lists based on the New York Times API</h1>
	{if $error}
		<div class="alert alert-danger">{$error}</div>
	{/if}

	{if $successMessage}
		<div class="alert alert-info">{$successMessage}</div>
	{/if}

	<form action="" method="post">
		<label for="selectedList">Pick a NYT list to build a Pika list for: </label>
		<!-- Give the user a list of all available lists from NYT -->
		<select name="selectedList" id="selectedList">
		{foreach from=$availableLists->results item="listInfo"}
			<option value="{$listInfo->list_name_encoded}" {if $selectedListName == $listInfo->list_name_encoded}selected="selected"{/if}>{$listInfo->display_name}</option>
		{/foreach}
		</select>
		<button type="submit" name="submit">Create/Update List</button>
	</form>
{/strip}