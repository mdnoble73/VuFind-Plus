<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
		{include file="MyResearch/menu.tpl"}
	</div>
  
	<div id="main-content" class="col-md-9">
		<h3>Attach eContent to Records</h3>
		<div id="importMarcContainer">
			<form action="{$path}" method="post" enctype="multipart/form-data">
				<p>Enter the folder to import files from.  The folder must be readable by the server.</p>
				<div>
				<label for="sourcePath">Source Path: </label><input type="text" size="60" name="sourcePath" id="sourcePath"/>
				</div>
				<div>
				
				<input type="submit" name="submit" value="Attach eContent" class="btn btn-primary"/>
				</div>
				<p>eContent will be attached to records in a batch process.  You can check the status of your import by viewing the <a href="AttachEContentLog">import log</a>.</p>
			</form>
		</div>
	</div>
</div>