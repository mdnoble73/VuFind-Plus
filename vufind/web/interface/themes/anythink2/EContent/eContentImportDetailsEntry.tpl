<dl id="eContentImportDetails">
  <dt>File Name:</dt>
  <dd>{$logEntry->filename|escape}</dd>
  {if $logEntry->libraryFilename}
  <dt>Library File Name:</dt>
  <dd>{$logEntry->libraryFilename|escape}</dd>
  {/if}
  <dt>Publisher:</dt>
  <dd>{$logEntry->publisher|escape}</dd>
  <dt>Distributor ID:</dt>
  <dd>{$logEntry->distributorId|escape}</dd>
  {if $logEntry->copies}
  <dt>Copies:</dt>
  <dd>{$logEntry->copies|escape}</dd>
  {/if}
  {if $logEntry->dateFound}
  <dt>Date Found:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->dateFound|escape}</dd>
  {/if}
  {if $logEntry->econtentRecordId}
  <dt>eContent Record ID:</dt>
  <dd><a href="{$path}/EcontentRecord/{$logEntry->econtentRecordId|escape}">{$logEntry->econtentRecordId|escape}</a></dd>
  {/if}
  {if $logEntry->econtentItemId}
  <dt>eContent Item ID:</dt>
  <dd>{$logEntry->econtentItemId|escape}</dd>
  {/if}
  {if $logEntry->dateSentToPackaging}
  <dt>Date Sent to Packaging:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->dateSentToPackaging|escape}</dd>
  {/if}
  {if $logEntry->packagingId}
  <dt>Packaging ID:</dt>
  <dd>{$logEntry->packagingId|escape}</dd>
  {/if}
  {if $logEntry->acsError}
  <dt>ACS Error:</dt>
  <dd>{$logEntry->acsError|escape}</dd>
  {/if}
  {if $logEntry->acsId}
  <dt>ACS ID:</dt>
  <dd>{$logEntry->acsId|escape}</dd>
  {/if}
  <dt>Status:</dt>
  <dd>{$logEntry->status|escape}</dd>
</dl>