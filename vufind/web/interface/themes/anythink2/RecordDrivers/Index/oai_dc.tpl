<oai_dc:dc 
         xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" 
         xmlns:dc="http://purl.org/dc/elements/1.1/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ 
         http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
  <dc:title>{$oaiFullTitle|escape}</dc:title>
  {if !empty($coreMainAuthor)}<dc:creator>{$coreMainAuthor|escape}</dc:creator>{/if}
  {if !empty($coreCorporateAuthor)}<dc:creator>{$coreCorporateAuthor|escape}</dc:creator>{/if}
  {if !empty($coreContributors)}
    {foreach from=$coreContributors item=field name=loop}
      <dc:creator>{$field|escape}</dc:creator>
    {/foreach}
  {/if}
  {foreach from=$recordLanguage item=lang}
    <dc:language>{$lang|escape}</dc:language>
  {/foreach}
  {if !empty($oaiPublishers)}
    {foreach from=$oaiPublishers item=field name=loop}
      <dc:publisher>{$field|escape}</dc:publisher>
    {/foreach}
  {/if}
  {if !empty($oaiPubDates)}
    {foreach from=$oaiPubDates item=field name=loop}
      <dc:date>{$field|escape}</dc:date>
    {/foreach}
  {/if}
  {if !empty($coreSubjects)}
    {foreach from=$coreSubjects item=field name=loop}
      {assign var=subject value=""}
      {foreach from=$field item=subfield name=subloop}
        {if $subject == ""}
          {assign var=subject value="$subfield"}
        {else}
          {assign var=subject value="$subject -- $subfield"}
        {/if}
      {/foreach}
      <dc:subject>{$subject|escape}</dc:subject>
    {/foreach}
  {/if}
</oai_dc:dc>