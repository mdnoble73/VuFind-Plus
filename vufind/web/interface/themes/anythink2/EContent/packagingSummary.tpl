<script type="text/javascript" src="{$path}/js/tablesorter/jquery.tablesorter.min.js"></script>
<div id="sidebar-wrapper"><div id="sidebar">
    {include file="MyResearch/menu.tpl"}
    {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
    <h2>Packaging Summary Report</h2>
    {if $error}
        <div class="error">{$error}</div>
    {else}
        <div id="materialsRequestFilters">
            <fieldset>
            <legend>Filters:</legend>
            <form action="{$path}/EContent/PackagingSummary" method="get">
                <div>
                <div>
                    <label for="period">Period</label>
                    <select name="period" id="period" onchange="$('#startDate').val('');$('#endDate').val('');";>
                        <option value="day" {if $period == 'day'}selected="selected"{/if}>Day</option>
                        <option value="week" {if $period == 'week'}selected="selected"{/if}>Week</option>
                        <option value="month" {if $period == 'month'}selected="selected"{/if}>Month</option>
                        <option value="year" {if $period == 'year'}selected="selected"{/if}>Year</option>
                    </select>
                </div>
                <div>
                    <label for="startDate">From</label> <input type="text" id="startDate" name="startDate" value="{$startDate}" size="8"/>
                    <label for="endDate">To</label> <input type="text" id="endDate" name="endDate" value="{$endDate}" size="8"/>
                </div>

                <div>
                <label for="distributorFilter">Distributor</label>
                <select id="distributorFilter" name="distributorFilter[]" multiple="multiple" size="5" class="multiSelectFilter">
                {foreach from=$distributorFilter item=distributor}
                    <option value="{$distributor|escape}" {if in_array($distributor,$selectedDistributorFilter)}selected='selected'{/if}>{$distributor|escape}</option>
                {/foreach}
                </select>
                </div>

                <div><input type="submit" name="submit" value="Update Filters"/></div>
                </div>
            </form>
            </fieldset>
        </div>

        <h3>Summary by Distributor</h3>
        {* Display results as graph *}
        {if $chartByDistributor}
        <div id="chartByDistributor">
            <img src="{$chartByDistributor}" />
            </div>
        {/if}

        {* Display results in table*}
        <table id="summaryTableByDistributor" class="tablesorter">
            <thead>
                <tr>
                    <th>Date</th>
                    {foreach from=$distributors item=distributor}
                        <th>{$distributor|escape}</th>
                    {/foreach}
                </tr>
            </thead>
            <tbody>
                {foreach from=$periodDataByDistributor item=periodInfo key=periodStart}
                    <tr>
                        <td>
                            {* Properly format the period *}
                            {if $period == 'year'}
                                {$periodStart|date_format:'%Y'}
                            {elseif $period == 'month'}
                                {$periodStart|date_format:'%h %Y'}
                            {else}
                                {$periodStart|date_format}
                            {/if}
                        </td>
                        {foreach from=$distributors item=distributor}
                            <th>{if $periodInfo.$distributor}{$periodInfo.$distributor}{else}0{/if}</th>
                        {/foreach}
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <h3>Summary by Status</h3>
        {* Display results as graph *}
        {if $chartByStatus}
        <div id="chartByStatus">
            <img src="{$chartByStatus}" />
            </div>
        {/if}

        {* Display results in table*}
        <table id="summaryTableByStatus" class="tablesorter">
            <thead>
                <tr>
                    <th>Date</th>
                    {foreach from=$statuses item=status}
                        <th>{$status|translate}</th>
                    {/foreach}
                </tr>
            </thead>
            <tbody>
                {foreach from=$periodDataByStatus item=periodInfo key=periodStart}
                    <tr>
                        <td>
                            {* Properly format the period *}
                            {if $period == 'year'}
                                {$periodStart|date_format:'%Y'}
                            {elseif $period == 'month'}
                                {$periodStart|date_format:'%h %Y'}
                            {else}
                                {$periodStart|date_format}
                            {/if}
                        </td>
                        {foreach from=$statuses item=status}
                            <th>{if $periodInfo.$status}{$periodInfo.$status}{else}0{/if}</th>
                        {/foreach}
                    </tr>
                {/foreach}
            </tbody>
        </table>

    {/if}

    {* Export to Excel option *}
    <form action="{$fullPath}" method="get">
        <input type="hidden" name="period" value="{$period}"/>
        <input type="hidden" name="startDate" value="{$startDate}"/>
        <input type="hidden" name="endDate" value="{$endDate}"/>
        {foreach from=$selectedDistributorFilter item=distributor}
          <input type="hidden" name="distributorFilter[]" value="{$distributor|escape}"/>
        {/foreach}
        <div>
        <input type="submit" id="exportToExcel" name="exportToExcel" value="Export to Excel">
        </div>
    </form>

</div>
<script type="text/javascript">
{literal}
    $("#startDate").datepicker();
    $("#endDate").datepicker();
    $("#summaryTableByDistributor").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });
    $("#summaryTableByStatus").tablesorter({cssAsc: 'sortAscHeader', cssDesc: 'sortDescHeader', cssHeader: 'unsortedHeader', headers: { 0: { sorter: 'date'} } });
{/literal}
</script>