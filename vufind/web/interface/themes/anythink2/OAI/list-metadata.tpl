  <ListMetadataFormats>
    {foreach from=$supported key=prefix item=current}
      <metadataFormat>
        <metadataPrefix>{$prefix|escape}</metadataPrefix>
        {if $current.schema}<schema>{$current.schema|escape}</schema>{/if}
        {if $current.namespace}<metadataNamespace>{$current.namespace|escape}</metadataNamespace>{/if}
      </metadataFormat>
    {/foreach}
  </ListMetadataFormats>