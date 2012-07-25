{if count($holdings) > 0}
  <table>
  <thead>
    <tr><th>Type</th><th>Source</th><th>Usage</th>{if $showEContentNotes}<th>Notes</th>{/if}<th>Size</th><th>&nbsp;</th>
  </thead>
  <tbody>
  {foreach from=$holdings item=eContentItem key=index}
    {if get_class($eContentItem) == 'OverdriveItem'}
      <tr id="itemRow{$index}">
        <td>{translate text=$eContentItem->format}</td>
        <td>OverDrive</td>
        <td>Must be checked out to read</td>
        <td>{$eContentItem->size}</td>
        <td>
          {* Options for the user to view online or download *}
          {foreach from=$eContentItem->links item=link}
            <a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
          {/foreach}
        </td>
      </tr>
    {else}
      <tr id="itemRow{$eContentItem->id}">
        <td>{translate text=$eContentItem->item_type}</td>
        <td>{$eContentItem->source}</td>
        <td>{if $eContentItem->getAccessType() == 'free'}No Usage Restrictions{elseif $eContentItem->getAccessType() == 'acs' || $eContentItem->getAccessType() == 'singleUse'}Must be checked out to read{/if}</td>
        {if $showEContentNotes}<td>{$eContentItem->notes}</td>{/if}
        <td>{$eContentItem->getSize()|file_size}</td>
        <td>
          {* Options for the user to view online or download *}
          {foreach from=$eContentItem->links item=link}
            <a href="{if $link.url}{$link.url}{else}#{/if}" {if $link.onclick}onclick="{$link.onclick}"{/if} class="button">{$link.text}</a>
          {/foreach}
          {if $user && $user->hasRole('epubAdmin')}
            <a href="#" onclick="return editItem('{$id}', '{$eContentItem->id}')" class="button">Edit</a>
            <a href="#" onclick="return deleteItem('{$id}', '{$eContentItem->id}')" class="button">Delete</a>
          {/if}
        </td>
      </tr>
    {/if}
  {/foreach}
  </tbody>
  </table>
{else}
  No Copies Found
{/if}

{assign var=firstItem value=$holdings.0}
{if strcasecmp($source, 'OverDrive') == 0}
  <a href="#" onclick="return addOverDriveRecordToWishList('{$id}')" class="button">Add&nbsp;to&nbsp;Wish&nbsp;List</a>
{/if}
{if strcasecmp($source, 'OverDrive') != 0 && $user && $user->hasRole('epubAdmin')}
  <a href="#" onclick="return addItem('{$id}');" class="button">Add Item</a>
{/if}

{if strcasecmp($source, 'OverDrive') == 0}
  <div id='overdriveMediaConsoleInfo'>
    <img src="{$path}/images/overdrive.png" width="125" height="42" alt="Powered by Overdrive" class="alignleft"/>
    <p>{translate text='Library eContent often requires special software to use.  Visit <a href="http://help.overdrive.com">help.overdrive.com</a> to find required software, view how-tos, and get troubleshooting advice.'}</p>
  </div>
{/if}

