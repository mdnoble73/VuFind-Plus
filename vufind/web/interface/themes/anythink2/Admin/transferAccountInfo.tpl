<div id="sidebar-wrapper"><div id="sidebar">
  {include file="MyResearch/menu.tpl"}
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>Transfer Account Information to new Barcode</h1>
  {if $message}
    <div class="error">{$message}</div>
  {/if}
  <div id="transferAccountContainer">
    <form action="{$path}" method="post" enctype="multipart/form-data">
      <div>
      <label for="oldBarcode">Old Barcode: </label><input type="text" size="15" name="oldBarcode" id="oldBarcode"/>
      </div>
      <div>
      <label for="newBarcode">New Barcode: </label><input type="text" size="15" name="newBarcode" id="newBarcode"/>
      </div>
      <div>
      <input type="submit" name="submit" value="Transfer Account Information"/>
      </div>
      <p>All patron information recorded in VuFind will be transfered from the old account to the new account.</p>
    </form>
  </div>
</div>
