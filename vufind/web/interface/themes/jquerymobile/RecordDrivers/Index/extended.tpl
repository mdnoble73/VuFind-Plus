<dl class="biblio" title="{translate text='Description'}">
  {if !empty($extendedDescription)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Description'}:</dt>
    <dd>
      {$extendedDescription|escape}
    </dd>
  {/if}

  {if !empty($extendedSummary)}
  {assign var=extendedContentDisplayed value=1}

    <dt>{translate text='Summary'}:</dt>
    <dd>
      {foreach from=$extendedSummary item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  
  {/if}

  {if !empty($extendedDateSpan)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Published'}:</dt>
    <dd>
      {foreach from=$extendedDateSpan item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedNotes)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Item Description'}:</dt>
    <dd>
      {foreach from=$extendedNotes item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedPhysical)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Physical Description'}:</dt>
    <dd>
      {foreach from=$extendedPhysical item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedFrequency)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Publication Frequency'}:</dt>
    <dd>
      {foreach from=$extendedFrequency item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedPlayTime)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Playing Time'}:</dt>
    <dd>
      {foreach from=$extendedPlayTime item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedSystem)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Format'}:</dt>
    <dd>
      {foreach from=$extendedSystem item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedAudience)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Audience'}:</dt>
    <dd>
      {foreach from=$extendedAudience item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedAwards)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Awards'}:</dt>
    <dd>
      {foreach from=$extendedAwards item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedCredits)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Production Credits'}:</dt>
    <dd>
      {foreach from=$extendedCredits item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedBibliography)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Bibliography'}:</dt>
    <dd>
      {foreach from=$extendedBibliography item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedISBNs)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='ISBN'}:</dt>
    <dd>
      {foreach from=$extendedISBNs item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedISSNs)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='ISSN'}:</dt>
    <dd>
      {foreach from=$extendedISSNs item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedRelated)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Related Items'}:</dt>
    <dd>
      {foreach from=$extendedRelated item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedAccess)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Access'}:</dt>
    <dd>
      {foreach from=$extendedAccess item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}

  {if !empty($extendedFindingAids)}
  {assign var=extendedContentDisplayed value=1}
    <dt>{translate text='Finding Aid'}:</dt>
    <dd>
      {foreach from=$extendedFindingAids item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
    </dd>
  {/if}
</dl>
