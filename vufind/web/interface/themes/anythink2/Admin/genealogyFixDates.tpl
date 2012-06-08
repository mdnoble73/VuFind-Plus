<script  type="text/javascript" src="{$path}/js/genealogy/fixDates.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>Fix Genealogy Dates</h1>
  {if $importMessage}
    <div class='error'>{$importMessage}</div>
  {/if}
  <br />
  <form name='genealogyReindex' method="post" action="{$path}/Admin/GenealogyFixDates">
    {if !$startDateFix}
    <p>In the initial version of the Genealogy database, you had to enter exact dates which doesn't work well
    in many cases since obituaries do not always provide complete information about the dates. This tool converts
    the old fixed date format to the new flexible format. This tool should only need to be run once.</p>
    <input type="submit" name="submit" value="Start Fixing Dates" />
    {else}
      Date Fix started, there are {$numRecords} to fix, <span id='currentRecord'>0</span> fixed so far. 
      <div id="progressbar"></div>
      <script type="text/javascript">
      {literal}$(function() {
        $( "#progressbar" ).progressbar({
          value: 0
        });
      });
      doGenealogyDateFix();
      {/literal}
      </script>
      <div id="completionMessage" style="display:none">Date Fix completed!</div>
    {/if}
  </form>
</div>
