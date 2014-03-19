<div id="page-content" class="content">
	<div id="main-content">
		<h2>{translate text='Materials Request Update'}</h2>
		{if $success == 0}
			<div class="error">
			{$error}
			</div>
		{else}
			<div class="result">
			The request for {$materialsRequest->title} by {$materialsRequest->author} was updated successfully.  Return to managing material requests <a href='{$path}/MaterialsRequest/ManageRequests'>here</a>. 
			</div>
		{/if}
	</div>
</div>
