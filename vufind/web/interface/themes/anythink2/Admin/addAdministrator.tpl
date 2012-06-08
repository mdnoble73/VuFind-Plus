<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
        <h1>Assign roles to a new user</h1>
        {if $error}
          <div class='error'>{$error}</div>
        {/if}
        <br />
        <form name='addAdministrator' method="post" enctype="multipart/form-data">
        <input type='hidden' name='objectAction' value='processNewAdministrator' />
        <div class='editor'>
          <label for='login' class='objectLabel'>Barcode</label>: <input type='text' name='login' id='login'/>
          <div class='propertyDescription'>Enter the barcode for the user who should be given administration privileges</div>

          {* Display the list of roles to add *}
          {assign var=property value=$structure.roles}
          {assign var=propName value=$property.property}
          {if isset($property.listStyle) && $property.listStyle == 'checkbox'}
            {foreach from=$property.values item=propertyName key=propertyValue}
              <br /><input name='{$propName}[{$propertyValue}]' type="checkbox" value='{$propertyValue}' {if is_array($propValue) && in_array($propertyValue, array_keys($propValue))}checked='checked'{/if}>{$propertyName}</input>
            {/foreach}
          {else}
            <br />
            <select name='{$propName}' id='{$propName}' multiple="multiple">
            {foreach from=$property.values item=propertyName key=propertyValue}
              <option value='{$propertyValue}' {if $propValue == $propertyValue}selected='selected'{/if}>{$propertyName}</option>
            {/foreach}
            </select>
          {/if}

          <br/>

          <input type="submit" name="submit" value="Update User"/>  <a href='{$path}/Admin/{$toolName}?objectAction=list'>Return to List</a>
        </div>
        </form>

</div>
