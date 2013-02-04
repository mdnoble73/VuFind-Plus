{if $coreThumbMedium}
<div class="recordcover">
  {if $coreThumbLarge}<a rel="external" href="{$coreThumbLarge|escape}">{/if}
    <img alt="{translate text='Cover Image'}" class="recordcover" src="{$coreThumbMedium|escape}"/>
  {if $coreThumbLarge}</a>{/if}
</div>
{/if}

<h3>{$coreShortTitle|escape}
    {if $coreSubtitle}{$coreSubtitle|escape}{/if}
    {if $coreTitleSection}{$coreTitleSection|escape}{/if}
</h3>

{if $coreSummary}<p>{$coreSummary|truncate:200:"..."|escape}</p>{/if}

<dl class="biblio" title="{translate text='Bibliographic Details'}">
{if !empty($coreNextTitles)}
  <dt>{translate text='New Title'}:</dt>
  <dd>
  {foreach from=$coreNextTitles item=field name=loop}
    <p><a rel="external" href="{$path}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Title">{$field|escape}</a></p>
  {/foreach}
  </dd>
{/if}

{if !empty($corePrevTitles)}
  <dt>{translate text='Previous Title'}:</dt>
  <dd>
  {foreach from=$corePrevTitles item=field name=loop}
    <p><a rel="external" href="{$path}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Title">{$field|escape}</a></p>
  {/foreach}
  </dd>
{/if}

{if !empty($coreMainAuthor)}
  <dt>{translate text='Main Author'}:</dt>
  <dd><a rel="external" href="{$path}/Author/Home?author={$coreMainAuthor|escape:"url"}">{$coreMainAuthor|escape}</a></dd>
{/if}

<dt>{translate text='Format'}:</dt>
<dd>
     {if is_array($recordFormat)}
      {foreach from=$recordFormat item=displayFormat name=loop}
        <span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span>
      {/foreach}
    {else}
      <span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span>
    {/if}  
</dd>

<dt>{translate text='Language'}:</dt>
<dd>{foreach from=$recordLanguage item=lang}{$lang|escape} {/foreach}</dd>

{if !empty($corePublications)}
  <dt>{translate text='Published'}:</dt>
  <dd>
      {foreach from=$corePublications item=field name=loop}
        <p>{$field|escape}</p>
      {/foreach}
  </dd>
{/if}

{if !empty($coreEdition)}
  <dt>{translate text='Edition'}:</dt>
  <dd>{$coreEdition|escape}</dd>
{/if}

{if !empty($coreSubjects)}
  <dt>{translate text='Subjects'}:</dt>
  <dd>
  {foreach from=$coreSubjects item=field name=loop}
  <p>
    {assign var=subject value=""}
    {foreach from=$field item=subfield name=subloop}
      {if !$smarty.foreach.subloop.first}--{/if}
      {assign var=subject value="$subject $subfield"}
      <a rel="external" href="{$path}/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;type=Subject">{$subfield|escape}</a>
    {/foreach}
  </p>
  {/foreach}
  </dd>
{/if}

{if !empty($coreCorporateAuthor)}
  <dt>{translate text='Corporate Author'}:</dt> 
  <dd>
    <p><a rel="external" href="{$path}/Author/Home?author={$coreCorporateAuthor|escape:"url"}">{$coreCorporateAuthor|escape}</a></p>
  </dd>
{/if}

{if !empty($coreContributors)}
  <dt>{translate text='Other Authors'}:</dt>
  <dd>
  {foreach from=$coreContributors item=field name=loop}
    <p><a rel="external" href="{$path}/Author/Home?author={$field|escape:"url"}">{$field|escape}</a></p>
  {/foreach}
  </dd>
{/if}

{* Display series section if at least one series exists. *}
{if !empty($coreSeries)}
  <dt>{translate text='Series'}:</dt>
  <dd>
  {foreach from=$coreSeries item=field name=loop}
    {* Depending on the record driver, $field may either be an array with
       "name" and "number" keys or a flat string containing only the series
       name.  We should account for both cases to maximize compatibility. *}
    {if is_array($field)}
      {if !empty($field.name)}
        <p>
        <a rel="external" href="{$path}/Search/Results?lookfor=%22{$field.name|escape:"url"}%22&amp;type=Series">{$field.name|escape}</a>
        {if !empty($field.number)}
          {$field.number|escape}
        {/if}
        </p>
      {/if}
    {else}
      <p><a rel="external" href="{$path}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Series">{$field|escape}</a></p>
    {/if}
  {/foreach}
  </dd>
{/if}

{if !empty($coreURLs) || $coreOpenURL}
  <dt>{translate text='Online Access'}:</dt>
  <dd>
  {foreach from=$coreURLs item=desc key=u name=loop}
    <p><a rel="external" href="{if $proxy}{$proxy}/login?qurl={$u|escape:'url'}{else}{$u|escape}{/if}">{$desc|escape}</a></p>
  {/foreach}
  {if $coreOpenURL}
    {include file="Search/openurl.tpl" openUrl=$coreOpenURL}
  {/if}
  </dd>
{/if}

{if $tagList}
  <dt>{translate text='Tags'}:</dt>
  <dd>
    {foreach from=$tagList item=tag name=tagLoop}
      <a rel="external" href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt}){if !$smarty.foreach.tagLoop.last}, {/if}
    {/foreach}
  </dd>
{/if}
</dl>