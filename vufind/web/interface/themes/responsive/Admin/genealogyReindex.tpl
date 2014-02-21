<script  type="text/javascript" src="{$path}/js/genealogy/import.js"></script>
<div id="page-content" class="row">
	<div id="sidebar" class="col-md-3">
    {include file="MyResearch/menu.tpl"}
  </div>
	
  <div id="main-content" class="col-md-9">
    {if $importMessage}
      <div class='error'>{$importMessage}</div>
    {/if}
    <br />
    <form name='genealogyReindex' method="post" action="{$path}/Admin/GenealogyReindex">
	    <legend>Reindex Genealogy Information</legend>
      {if !$startReindex}
	      <p>Reindexing Genealogy information can take several hours.  During this period all records will not be available.  Don't run this function unless absolutely needed.</p>
        <input type="submit" name="submit" value="Start Reindexing Process" class="btn btn-primary"/>
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