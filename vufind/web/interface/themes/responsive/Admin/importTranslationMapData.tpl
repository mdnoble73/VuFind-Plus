<div id="main-content">
	<h1>{$shortPageTitle}</h1>
	<a class="btn btn-sm btn-default" href='/Admin/TranslationMaps?objectAction=list'>Return to List</a>
	<div class="helpTextUnsized">
		<p>Translation map values can be loaded from either an INI formatted record
			or from a CSV formatted record.
			<ol>
				<li>value = translation</li>
				<li>value, translation</li>
			</ol>
			The translation and value can optionally have quotes surrounding it.
		</p>
	</div>
	<form name="importTranslationMaps" action="/Admin/TranslationMaps" method="post" id="importTranslationMaps">
		<div>
			<input type="hidden" name="objectAction" value="doAppend" id="objectAction"/>
			<input type="hidden" name="id" value="{$id}"/>
			<textarea rows="20" cols="80" name="translationMapData"></textarea>
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
