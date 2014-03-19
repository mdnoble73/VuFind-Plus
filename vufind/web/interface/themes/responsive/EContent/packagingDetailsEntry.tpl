<dl>
  <dt>File Name:</dt>
  <dd>{$logEntry->filename|escape}</dd>
  <dt>Distributor ID:</dt>
  <dd>{$logEntry->distributorId|escape}</dd>
  <dt>Copies:</dt>
  <dd>{$logEntry->copies|escape}</dd>
  <dt>Created:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->created|escape}</dd>
  {if $logEntry->lastUpdate}
  <dt>Last Update:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->lastUpdate|escape}</dd>
  {/if}
  {if $logEntry->packagingStartTime}
  <dt>Packaging Start Time:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->packagingStartTime|escape}</dd>
  {/if}
  {if $logEntry->packagingEndTime}
  <dt>Packaging End Time:</dt>
  <dd>{'m/d/Y H:i:s T'|date:$logEntry->packagingEndTime|escape}</dd>
  {/if}
  {if $logEntry->acsError}
  <dt>ACS Error:</dt>
  <dd>{$logEntry->acsError|escape}</dd>
  {/if}
  {if $logEntry->acsId}
  <dt>ACS ID:</dt>
  <dd>{$logEntry->acsId|escape}</dd>
  {/if}
  {if $logEntry->previousAcsId}
  <dt>Previous ACS ID:</dt>
  <dd>{$logEntry->previousAcsId|escape}</dd>
  {/if}
  <dt>Status:</dt>
  <dd>{$logEntry->status|escape}</dd>
</dl>