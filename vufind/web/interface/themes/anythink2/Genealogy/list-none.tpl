<p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

{if $parseError}
    <p class="error">{translate text='nohit_parse_error'}</p>
{/if}

{if $spellingSuggestions}
<div class="correction">{translate text='nohit_spelling'}:<br/>
{foreach from=$spellingSuggestions item=details key=term name=termLoop}
  {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
{/foreach}
</div>
<br/>
{/if}

{if $userIsAdmin}
<a href='{$path}/Admin/People?objectAction=addNew' class='button'>Add someone new</a> 
{/if}
