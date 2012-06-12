<div id="sidebar-wrapper"><div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
    <h2>Packaging Details Report</h2>
    {if $error}
        <div class="error">{$error}</div>
    {else}
        <div id="materialsRequestFilters">
            <fieldset>
            <legend>Filters:</legend>
            <form action="{$path}/EContent/PackagingDetails" method="get">
                <div>
                <div>
                    <label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="10"/>
                    <label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="10"/>
                </div>

                <div>
                <label for="distributorFilter">Distributor</label>
                <select id="distributorFilter" name="distributorFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
                {foreach from=$distributorFilter item=distributor}
                    <option value="{$distributor|escape}" {if in_array($distributor,$selectedDistributorFilter)}selected='selected'{/if}>{$distributor|escape}</option>
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

                <div><input type="submit" name="submit" value="Update Filters"/></div>
                </div>
            </form>
            </fieldset>
        </div>
    {/if}

    {$packagingDetailsTable}

    {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}

    <form action="{$fullPath}" method="get">
        <input type="hidden" name="startDate" value="{$startDate}"/>
        <input type="hidden" name="endDate" value="{$endDate}"/>
        {foreach from=$selectedDistributorFilter item=distributor}
          <input type="hidden" name="distributorFilter[]" value="{$distributor|escape}"/>
        {/foreach}
        {foreach from=$selectedStatusFilter item=status}
          <input type="hidden" name="statusFilter[]" value="{$status|escape}"/>
        {/foreach}
        <div>
        <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
        </div>
    </form>

    {* Export to Excel option *}
</div>
<script type="text/javascript">
{literal}
    $("#startDate").datepicker();
    $("#endDate").datepicker();
    function popupDetails(id) {
        $('#detailsDialog').load(path+'/EContent/AJAX?method=getPackagingDetails&id='+id)
            .dialog({modal:true, title:'Packaging Details', width: 800, height: 500});
    }
{/literal}
</script>
<div id="detailsDialog"></div>