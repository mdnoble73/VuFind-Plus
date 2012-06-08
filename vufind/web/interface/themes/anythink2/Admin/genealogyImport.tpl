<script  type="text/javascript" src="{$path}/js/genealogy/import.js"></script>
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
          <h1>Import Genealogy Information</h1>
          {if $importMessage}
            <div class='error'>{$importMessage}</div>
          {/if}
          <br />
          <form name='genealogyImport' method="post" enctype="multipart/form-data" action="{$path}/Admin/GenealogyImport">
            {if !$startImport}
            <label for="file">File to import:</label>
						<input type="file" name="file" id="file" />
						<br />
						<input type="submit" name="submit" value="Submit" />
						{else}
						  Import started, there are {$numRecords} to import, <span id='currentRecord'>0</span> imported so far. 
						  <div id="progressbar"></div>
						  <script>
						  {literal}$(function() {
						    $( "#progressbar" ).progressbar({
						      value: 0
						    });
						  });
						  doGenealogyImport();
						  </script>{/literal}
						  <div id="completionMessage" style="display:none">Import completed!</div>
						{/if}
          </form>
        </div>
</div>