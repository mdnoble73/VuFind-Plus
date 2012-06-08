<script  type="text/javascript" src="{$path}/js/genealogy/import.js"></script>
<div id="page-content" class="content">
	<div id="sidebar">
    {include file="MyResearch/menu.tpl"}
      
    {include file="Admin/menu.tpl"}
  </div>
	
  <div id="main-content">
          <h1>Reindex Genealogy Information</h1>
          {if $importMessage}
            <div class='error'>{$importMessage}</div>
          {/if}
          <br />
          <form name='genealogyReindex' method="post" action="{$path}/Admin/GenealogyReindex">
            {if !$startReindex}
            <input type="submit" name="submit" value="Start Reindexing Process" />
						{else}
						  Reindex started, there are {$numRecords} to index, <span id='currentRecord'>0</span> indexed so far. 
						  <div id="progressbar"></div>
						  <script type="text/javascript">
						  {literal}$(function() {
						    $( "#progressbar" ).progressbar({
						      value: 0
						    });
						  });
						  doGenealogyReindex();
						  {/literal}
							</script>
						  <div id="completionMessage" style="display:none">Reindex completed!</div>
						{/if}
          </form>
	</div>
</div>