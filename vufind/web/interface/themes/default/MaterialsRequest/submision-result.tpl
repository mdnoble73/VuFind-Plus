<div id="page-content" class="content">
	<div id="main-content">
		<h2>{translate text='Materials Request Result'}</h2>
		{if $success == 0}
			<div class="error">
			{$error}
			</div>
		{else}
			<div class="result">
			Your request for {$materialsRequest->title} by {$materialsRequest->author} was submitted successfully.  You can view the status of all your material requests <a href='{$path}/MaterialsRequest/MyRequests'>here</a>. 
			</div>
		{/if}
	</div>
</div>
