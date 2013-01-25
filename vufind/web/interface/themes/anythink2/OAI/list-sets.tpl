  <ListSets>
    {foreach from=$sets key=prefix item=current}
      <set>
        {if $current.spec}<setSpec>{$current.spec|escape}</setSpec>{/if}
        {if $current.name}<setName>{$current.name|escape}</setName>{/if}
      </set>
    {/foreach}
  </ListSets>