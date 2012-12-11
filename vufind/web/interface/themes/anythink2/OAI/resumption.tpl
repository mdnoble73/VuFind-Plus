    <resumptionToken {if $tokenExpiration}expirationDate="{$tokenExpiration|escape}"{/if}
      completeListSize="{$listSize|escape}"
      cursor="{$oldCursor|escape}">{$resumptionToken|escape}</resumptionToken>
