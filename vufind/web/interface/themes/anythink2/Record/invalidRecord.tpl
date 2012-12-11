<div id="page-content" class="content">
	{* Narrow Search Options *}
	<div id="sidebar">
		
	</div>
	
	<div id="main-content">
		<div class="resulthead"><h3>{translate text='Invalid Record'}</h3></div>
			
		<p class="error">Sorry, we could not find a record with that id in our catalog.	Please try your search again.</p>
		{if $enableMaterialsRequest}
		<p>
		Can't find what you are looking for? Try our <a href="{$path}/MaterialsRequest/NewRequest">Materials Request Service</a>.
		</p>
		{/if}
		
	</div>
</div>