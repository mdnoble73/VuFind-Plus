<div id="page-content" class="row-fluid">
	<div id="main-content">
		<h2>{translate text='Invalid Record'}</h2>
			
		<p class="error">Sorry, we could not find a record with an id of {$id} in our catalog.	Please try your search again.</p>
		{if $enableMaterialsRequest}
		<p>
		Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest">Materials Request Service</a>.
		</p>
		{/if}
		
	</div>
</div>