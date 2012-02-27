{* Generate links for each go deeper option *}
<div id='goDeeperContent'>
<div id='goDeeperLinks'>
{foreach from=$options item=option key=dataAction}
<div class='goDeeperLink'><a href='#' onclick="getGoDeeperData('{$dataAction}', '{$id}', '{$isbn}', '{$upc}');return false;">{$option}</a></div>
{/foreach}
</div>
<div id='goDeeperOutput'>{$defaultGoDeeperData}</div>
</div>