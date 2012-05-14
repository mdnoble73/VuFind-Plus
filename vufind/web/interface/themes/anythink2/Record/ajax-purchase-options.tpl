<div id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  {translate text='Purchase Options'}
</div>
<div id="popupboxContent" class="content">
  <div id='purchaseOptions'>
    {if $errors}
      <div class="errors">
        {foreach from=$errors item=error}
          <div class="error">{$error}</div>
        {/foreach}
      </div>
    {else}
      <table class="purchaseOptionLinks">
        <tbody>
        {foreach from=$purchaseLinks item=purchaseLink}
          <tr>
            <td>
            {if $purchaseLink.image}
              <img src="{$purchaseLink.image}" alt="{$purchaseLink.storeName}" />
            {else}
              {$purchaseLink.storeName}
            {/if}
            </td>
            <td>
              <a class="button" href="{$path}/Record/{$id}/Purchase?store={$purchaseLink.storeName|escape:"url"}{if $purchaseLink.field856Index}&index={$purchaseLink.field856Index}{/if}" target="_blank">Buy Now</a>
            </td>
          </tr>
        {/foreach}
        </tbody>
      </table>
    {/if}
  </div>
</div>
