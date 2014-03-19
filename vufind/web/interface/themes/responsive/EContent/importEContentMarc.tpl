<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Import Marc Record</h1>
		
		{if $errors}
		<div class="errors">
			<ul>
				{foreach from=$errors item=error}
				<li>{$error}</li>
				{/foreach}
			</ul>
		</div>
		{/if}
		<div id="importMarcContainer">
			<form action="{$path}" method="post" enctype="multipart/form-data">
				
				<div>
					<label for="marcFile">Marc File: </label><input type="file" size="60" name="marcFile" id="marcFile"/>
					<p>Select a marc record to import</p>
				</div>
				
				<div>
					<label for="suplementalCSV">Supplemental CSV: </label><input type="file" size="60" name="suplementalCSV" id="suplementalCSV"/>
					<p>Select the CSV file with collection codes to be applied to the records.  VuFind will process the following columns headers which should be contained in the first row of the file:</p>
					<pre>ISBN, Title, Collection, Series</pre>
					<p>Columns can be provided in any order provided the headers match those shown above.  Supplemental information will only be used if the field is not provided in the Marc Record.  Additional columns may exist within the file, but they will be ignored.</p>
				</div>
				
				<div>
					<label for="source">Source: </label><input type="text" size="60" name="source" id="source"/>
					<p>The source (or publisher) of the records.  I.e. Lerner, OverDrive, Gale Group, etc.</p>
				</div>
				
				<div>
					<label for="accessType">Access Type: </label>
					<select name="accessType" id="accessType">
						<option value="free">Free</option>
						<option value="singleUse">Single Usage</option>
						<option value="acs" selected="selected">ACS Protected</option>
					</select>
					<p>The type of protection to be applied to the records.</p>
				</div>
				<div>
					<input type="submit" name="submit" value="Import Marc Records"/>
				</div>
				<p>Marc Records will be imported in a batch process.  You can check the status of your import by viewing the <a href="{$path}/EContent/MarcImportLog">import log</a>.</p>
			</form>
		</div>
	</div>
</div>