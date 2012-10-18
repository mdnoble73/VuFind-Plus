  <{$verb|escape}>
    {foreach from=$xmlParts item=chunk}
      {* DO NOT ESCAPE -- RAW XML: *}{$chunk}
    {/foreach}
  </{$verb|escape}>