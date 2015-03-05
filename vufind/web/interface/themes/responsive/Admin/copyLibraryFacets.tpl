	<div id="main-content">
		<h1>Copy Library Facets</h1>
		{if count($allLibraries) == 0}
			<div>Sorry, there are no libraries available for you to copy facets from.</div>
		{else}
			<form action="/Admin/Libraries" method="get">
				<div>
					<input type="hidden" name="id" value="{$id}"/>
					<input type="hidden" name="objectAction" value="copyFacetsFromLibrary"/>
					<label for="libraryToCopyFrom">Select a library to copy facets from:</label>
					<select id="libraryToCopyFrom" name="libraryToCopyFrom">
						{foreach from=$allLibraries item=library}
							<option value="{$library->libraryId}">{$library->displayName}</option>
						{/foreach}
					</select>
					<input type="submit" name="submit" value="Copy Facets"/>
				</div>
			</form>
		{/if}
	</div>
