{if $user->cat_username}

  <h2>{translate text='request_place_text'}</h2>

  {* This will always be an error as successes get redirected to MyResearch/Holds.tpl *}
  {if $results.status}
    <p class="error">{translate text=$results.status}</p>
  {/if}
  {if $results.sysMessage}
    <p class="error">{translate text=$results.sysMessage}</p>
  {/if}

  <div class="hold-form">

    <form action="{$url|escape}/Record/{$id|escape}/Hold{$formURL|escape}" method="post">
      
      {if in_array("comments", $extraHoldFields)}
        <div>
        <strong>{translate text="Comments"}:</strong><br>
        <textarea rows="3" cols="20" name="gatheredDetails[comment]">{$gatheredDetails.comment|escape}</textarea>
        </div>
      {/if}

      {if in_array("requiredByDate", $extraHoldFields)}
        <div>
        <strong>{translate text="hold_required_by"}: </strong>
        <div id="requiredByHolder"><input id="requiredByDate" type="text" name="gatheredDetails[requiredBy]" value="{if $gatheredDetails.requiredBy !=""}{$gatheredDetails.requiredBy|escape}{else}{$defaultDuedate}{/if}" size="8" /> <strong>({displaydateformat})</strong></div>
        </div>
      {/if}

      {if in_array("pickUpLocation", $extraHoldFields)}
        <div>
        {if count($pickup) > 1}
          {if $gatheredDetails.pickUpLocation !=""}
            {assign var='selected' value=$gatheredDetails.pickUpLocation}
          {elseif $home_library != ""}
            {assign var='selected' value=$home_library}
          {else}
            {assign var='selected' value=$defaultPickUpLocation}
          {/if}
          <strong>{translate text="pick_up_location"}:</strong><br>
          <select name="gatheredDetails[pickUpLocation]">
          {foreach from=$pickup item=lib name=loop}
            <option value="{$lib.locationID|escape}" {if $selected == $lib.locationID}selected="selected"{/if}>{$lib.locationDisplay|escape}</option>
          {/foreach}
          </select>
        {else}
          <input type="hidden" name="gatheredDetails[pickUpLocation]" value="{$defaultPickUpLocation|escape}" />
        {/if}
        </div>
      {/if}

      <input type="hidden" name="gatheredDetails[id]" value="{$id|escape}" />  
      {if $gatheredDetails.item_id}<input type="hidden" name="gatheredDetails[item_id]" value="{$gatheredDetails.item_id|escape}" />{/if}
      {if $gatheredDetails.holdtype}<input type="hidden" name="gatheredDetails[holdtype]" value="{$gatheredDetails.holdtype|escape}" />{/if}
      {if $gatheredDetails.format_id}<input type="hidden" name="gatheredDetails[format_id]" value="{$gatheredDetails.format_id|escape}" />{/if}
      <input type="submit" name="placeHold" value="{translate text='request_submit_text'}">
    
    </form>
  
  </div>
  
{else}
  {include file="MyResearch/catalog-login.tpl"}
{/if}
