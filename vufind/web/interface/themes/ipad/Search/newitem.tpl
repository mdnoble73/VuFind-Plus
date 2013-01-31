<div data-role="page" id="Search-reserves">
  {include file="header.tpl"}
  <div data-role="content">
    <h3>{translate text='Find New Items'}</h3>
    <form method="get" action="{$url}/Search/NewItem" data-ajax="false">
      <div data-role="fieldcontain">
        <fieldset data-role="controlgroup">
          <legend>{translate text='Range'}:</legend>
          {foreach from=$ranges item="range" key="key"}
            <input id="newitem_range_{$key}" type="radio" name="range" value="{$range|escape}"{if $key == 0} checked="checked"{/if}/>
            <label for="newitem_range_{$key}">
            {if $range == 1}
              {translate text='Yesterday'}
            {else}
              {translate text='Past'} {$range|escape} {translate text='Days'}
            {/if}
            </label>
          {/foreach}
        </fieldset>
      </div>
      {if is_array($fundList) && !empty($fundList)}
        <div data-role="fieldcontain">
          <label for="newitem_department">{translate text='Department'}:</label>
          <select id="newitem_department" name="department">
          {foreach from=$fundList item="fund" key="fundId"}
            <option value="{$fundId|escape}">{$fund|escape}</option>
          {/foreach}
          </select>
        </div>
      {/if}
      <div data-role="fieldcontain">
        <input type="submit" name="submit" value="{translate text='Find'}"/>
      </div>
    </form>    
  </div>
  {include file="footer.tpl"}
</div>
