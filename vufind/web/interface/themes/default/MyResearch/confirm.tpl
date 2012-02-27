<div align="left">

  {if $listName}
  <h3>{$listName|escape}</h3>
  {/if}

  {if $infoMsg || $errorMsg}
  <div class="messages">
  {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
  {if $infoMsg}<div class="userMsg">{$infoMsg|translate}</div>{/if}
  </div>
  {/if}

  <div id="popupMessages"></div>
  <div id="popupDetails">

    <form action="{$url}/MyResearch/Confirm" method="post">

    {if $listID}
    <input type="hidden" name="listID" value="{$listID|escape}" />
    {/if}
    <input type="hidden" name="{$confirmAction|escape}" />
    <input type="submit" name="confirm" value="{translate text='Confirm'}" /> <input type="submit" name="cancel" value="{translate text='Cancel'}" />
    </form>

  </div>
</div>

