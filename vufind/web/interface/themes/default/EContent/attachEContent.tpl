<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
		{include file="Admin/menu.tpl"}
	</div>
  
	<div id="main-content">
		<h1>Attach eContent to Records</h1>
		
		<div id="importMarcContainer">
			<form action="{$path}" method="post" enctype="multipart/form-data">
				<p>Enter the folder to improt files from.  The folder must be readale by the server.</p> 
				<div>
				<label for="sourcePath">Source Path: </label><input type="text" size="60" name="sourcePath" id="sourcePath"/>
				</div>
				<div>
				
				<input type="submit" name="submit" value="Attach eContent"/>
				</div>
				<p>eContent will be attached to records in a batch process.  You can check the status of your import by viewing the <a href="AttachEContentLog">import log</a>.</p>
			</form>
		</div>
	</div>
</div>