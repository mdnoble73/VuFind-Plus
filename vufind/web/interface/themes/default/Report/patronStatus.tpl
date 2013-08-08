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
				
			{else}
				You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
			{/if}
		</div>
	</div>
{/strip}