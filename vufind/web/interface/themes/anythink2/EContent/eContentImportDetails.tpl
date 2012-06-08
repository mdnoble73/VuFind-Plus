<div id="page-content" class="content">
    <div id="sidebar-wrapper"><div id="sidebar">
        {include file="MyResearch/menu.tpl"}
        {include file="Admin/menu.tpl"}
    </div></div>

    <div id="main-content">
        <h2>eContent Import Details Report</h2>
        {if $error}
            <div class="error">{$error}</div>
        {else}
            <div id="materialsRequestFilters">
                <fieldset>
                <legend>Filters:</legend>
                <form action="{$path}/EContent/EContentImportDetails" method="get">
                    <div>
                    <div>
                        <label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="10"/>
                        <label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="10"/>
                    </div>
                    
                    <div>
                    <label for="publisherFilter">Publisher</label>
                    <select id="publisherFilter" name="publisherFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
                    {foreach from=$publisherFilter item=publisher}
                        <option value="{$publisher|escape}" {if in_array($publisher,$selectedPublisherFilter)}selected='selected'{/if}>{$publisher|escape}</option> 
                    {/foreach}
                    </select>
                    </div>
                    
                    <div>
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter" name="statusFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
                    {foreach from=$statusFilter item=status}
                        <option value="{$status|escape}" {if in_array($status,$selectedStatusFilter)}selected='selected'{/if}>{$status|translate|escape}</option> 
                    {/foreach}
                    </select>
                    </div>
                    
                    <div>
                    <label for="packagingIds">Packaging IDs (comma separated)</label>
                    <input id="packagingIds" size="70" type="text" name="packagingIds" value="{$packagingIds}"/>
                    </div>
                    
                    <div><input type="submit" name="submit" value="Update Filters"/></div>
                    </div>
                </form>
                </fieldset>
            </div>            
        {/if}
        
        {$importDetailsTable}
        {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
        
        {* Export to Excel option *}
        <form action="{$fullPath}" method="get">
            <input type="hidden" name="startDate" value="{$startDate}"/>
            <input type="hidden" name="endDate" value="{$endDate}"/>
            {foreach from=$selectedPublisherFilter item=publisher}
              <input type="hidden" name="publisherFilter[]" value="{$publisher|escape}"/>
            {/foreach}
            {foreach from=$selectedStatusFilter item=status}
              <input type="hidden" name="statusFilter[]" value="{$status|escape}"/>
            {/foreach}
            <input type="hidden" name="packagingIds" value="{$packagingIds}"/>
            
            <div>
            <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
{literal}
    $("#startDate").datepicker();
    $("#endDate").datepicker();
    function popupDetails(id) {
        $('#detailsDialog').load(path+'/EContent/AJAX?method=getEContentImportDetails&id='+id)
            .dialog({modal:true, title:'eContent Import Details', width: 800, height: 500});
    }
{/literal}
</script>
<div id="detailsDialog"></div>
