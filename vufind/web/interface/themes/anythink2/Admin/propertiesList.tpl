<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>{$shortPageTitle}</h1>
  <div class='adminTableRegion'>
    <table class="adminTable">
      <thead>
        <tr>
          {foreach from=$structure item=property key=id}
            {if !isset($property.hideInLists) || $property.hideInLists == false}
            <th class='headerCell'><label title='{$property.description}'>{$property.label}</label></th>
            {/if}
          {/foreach}
          <th class='headerCell'>Actions</th>
        </tr>
      </thead>
      <tbody>
        {if isset($dataList) && is_array($dataList)}
          {foreach from=$dataList item=dataItem key=id}
          <tr class='{cycle values="odd,even"} {$dataItem->class}'>
            {foreach from=$structure item=property}
              {assign var=propName value=$property.property}
              {assign var=propOldName value=$property.propertyOld}
              {assign var=propValue value=$dataItem->$propName}
              {assign var=propOldValue value=$dataItem->$propOldName}
              {if !isset($property.hideInLists) || $property.hideInLists == false}
                <td {if $propOldValue}class='fieldUpdated'{/if}>
                {if $property.type == 'text' || $property.type == 'label' || $property.type == 'hidden' || $property.type == 'file'}
                  {$propValue}{if $propOldValue} ({$propOldValue}){/if}
                {elseif $property.type == 'date'}
                  {$propValue}{if $propOldValue} ({$propOldValue}){/if}
                {elseif $property.type == 'partialDate'}
                  {assign var=propNameMonth value=$property.propNameMonth}
                  {assign var=propMonthValue value=$dataItem->$propNameMonth}
                  {assign var=propNameDay value=$property.propNameDay}
                  {assign var=propDayValue value=$dataItem->$propDayValue}
                  {assign var=propNameYear value=$property.propNameYear}
                  {assign var=propYearValue value=$dataItem->$propNameYear}
                  {if $propMonthValue}$propMonthValue{else}??{/if}/{if $propDayValue}$propDayValue{else}??{/if}/{if $propYearValue}$propYearValue{else}??{/if}
                {elseif $property.type == 'currency'}
                  {assign var=propDisplayFormat value=$property.displayFormat}
                  ${$propValue|string_format:$propDisplayFormat}{if $propOldValue} (${$propOldValue|string_format:$propDisplayFormat}){/if}
                {elseif $property.type == 'enum'}
                  {foreach from=$property.values item=propertyName key=propertyValue}
                    {if $propValue == $propertyValue}{$propertyName}{/if}
                  {/foreach}
                  {if $propOldValue}
                    {foreach from=$property.values item=propertyName key=propertyValue}
                      {if $propOldValue == $propertyValue} ({$propertyName}){/if}
                     {/foreach}
                  {/if}
                {elseif $property.type == 'multiSelect'}
                  {if is_array($propValue) && count($propValue) > 0}
                    {foreach from=$property.values item=propertyName key=propertyValue}
                      {if in_array($propertyValue, array_keys($propValue))}{$propertyName}<br/>{/if}
                    {/foreach}
                  {else}
                    No values selected
                  {/if}
                {elseif $property.type == 'checkbox'}
                  {if ($propValue == 1)}Yes{else}No{/if}
                  {if $propOldValue}
                  {if ($propOldValue == 1)} (Yes){else} (No){/if}
                  {/if}
                {else}
                  Unknown type to display {$property.type}
                {/if}
                </td>
              {/if}
            {/foreach}
            {if $dataItem->class != 'objectDeleted'}
            <td class='edit'><a href='{$url}/{$module}/{$toolName}?objectAction=edit&id={$id}'>Edit</a></td>
            {/if}
          </tr>
          {/foreach}
      {/if}
      </tbody>
    </table>
  </div>
  {if $canAddNew}
  <form>
    <input type='hidden' name='objectAction' value='addNew' />
    <button type='submit' value='addNew'>Add New {$objectType}</button>
    </form>
    {/if}
    {foreach from=$customListActions item=customAction}
      <form>
      <input type='hidden' name='objectAction' value='{$customAction.action}' />
      <button type='submit' value='{$customAction.action}'>{$customAction.label}</button>
      </form>
    {/foreach}
    <form>
    <input type='hidden' name='objectAction' value='export' />
    <button type='submit' value='export'>Export to file</button>
    </form>

    <h2>Compare</h2>
    <form enctype="multipart/form-data" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
    <input type="hidden" name='objectAction' value='compare' />
    <div>Choose a file to compare: <input name="uploadedfile" type="file" /> <input type="submit" value="Compare File" /></div>
    </form>

    <h2>Import</h2>
    <form enctype="multipart/form-data" method="POST">
    <input type="hidden" name="MAX_FILE_SIZE" value="100000" />
    <input type="hidden" name='objectAction' value='import' />
    <div>Choose a file to import: <input name="uploadedfile" type="file" /> <input type="submit" value="Import File" /></div>
    This should be a file that was exported from the VuFind Admin console. Trying to import another file could result in having a very long day of trying to put things back together.  In short, don't do it!
  </form>
</div>
