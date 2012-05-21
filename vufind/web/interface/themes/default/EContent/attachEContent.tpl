<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Attach eContent to Records</h1>
		<div id="importMarcContainer">
			<form action="{$path}" method="post" enctype="multipart/form-data">
				<p>Enter the folder to import files from.  The folder must be readale by the server.</p> 
				<div>
				<label for="sourcePath">Source Path: </label><input type="text" size="60" name="sourcePath" id="sourcePath"/>
				</div>
				<div>
				
				<input type="submit" name="submit" value="Attach eContent"/>
				</div>
				<p>eContent will be attached to records in a batch process.  You can check the status of your import by viewing the <a href="AttachEContentLog">import log</a>.</p>
			</form>
		</div>
		
		<h1>Create Items for External Links</h1>
		<div id="createItemsForExternalLinksContainer">
			<form action="{$path}" method="post">
				<p>Enter source you would like to create items for external links for.</p> 
				<div>
					<label for="source">Source:</label> 
					<select name="source" id="source">
						{foreach from=$sourceFilter item="sourceItem"}
							<option value="{$sourceItem}" {if $sourceItem == $source}selected="selected"{/if}>{$sourceItem}</option>
						{/foreach}
					</select>
				</div>
				<p>Enter the tags you would like to look for links in.  Separate multiple tags with colons, ie. 856u:989y</p> 
				<div>
					<input type="text" name="tags" value="856u" name="tagsToProcess"/>
				</div>
				<div>
					<input type="submit" name="submit" value="Process External Links"/>
				</div>
				<p>eContent will be attached to records in a batch process.  You can check the status of your import by viewing the <a href="AttachEContentLog">import log</a>.</p>
			</form>
		</div>
	</div>
</div>