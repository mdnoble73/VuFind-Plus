<div id="main-content">
	<h1>{$shortPageTitle}</h1>
	<a class="btn btn-sm btn-default" href='/Admin/LocationMaps?objectAction=list'>Return to List</a>
	<div class="alert alert-info">
		<p>Location map values can be loaded from a CSV formatted text file.
			<dl>
				<dt>location code</dt><dd>The location code to map</dd>
				<dt>sub location code</dt><dd>The sub location code if used.  Leave blank if none</dd>
				<dt>collection code</dt><dd>The collection code if used.  Leave blank if none</dd>
				<dt>library systems</dt><dd>A comma separated list of library subdomains to include the location in</dd>
				<dt>location codes</dt><dd>A comma separated list of location codes to include the location in</dd>
				<dt>shelf location</dt><dd>The shelf location to display for this location</dd>
			</dl>
			The values can optionally have quotes surrounding them.  You must add quotes if there is a comma in the list.
		</p>
	</div>
	<form name="importLocationMaps" action="/Admin/LocationMaps" method="post" id="importLocationMaps">
		<div>
			<input type="hidden" name="objectAction" value="doAppend" id="objectAction"/>
			<input type="hidden" name="id" value="{$id}"/>
			<textarea rows="20" cols="140" name="locationMapData"></textarea>
			<br/>
			<input type="submit" name="reload" value="Append/Overwrite Values" class="btn btn-primary" onclick="setObjectAction('doAppend')"/>
			<input type="submit" name="reload" value="Reload Map Values" class="btn btn-primary" onclick="setObjectAction('doReload')"/>
		</div>
	</form>
</div>

<script type="text/javascript">
	{literal}
	function setObjectAction(newValue){
		$("#objectAction").value(newValue);
	}
	{/literal}
</script>
