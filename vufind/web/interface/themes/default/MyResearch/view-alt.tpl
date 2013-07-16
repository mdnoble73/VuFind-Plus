<div id="page-content" class="content">

	<div class="record">
		{if !empty($recordId)}
			<a href="{$path}/Record/{$recordId|escape:"url"}/Home" class="backtosearch">&laquo; {translate text="Back to Record"}</a>
		{/if}

		{if $pageTitle}<h1>{$pageTitle}</h1>{/if}
		{include file="MyResearch/$subTemplate"}

	</div>

</div>
