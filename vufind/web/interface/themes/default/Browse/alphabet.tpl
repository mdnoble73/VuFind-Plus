{foreach from=$letters item=letter}
  <li>
    <a href="" onClick="highlightBrowseLink(this); LoadOptions('{$query_field|escape}:{$letter|escape}*', '{$query_field|escape}', 'list4', null, null, '{$letter|escape}'); return false;">{$letter|escape}</a>
  </li>
{/foreach}
