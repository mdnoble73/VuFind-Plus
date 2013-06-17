{if $searchId}
	<li>{translate text="Search"}: {$lookfor|capitalize|escape:"html"}</li>
{elseif $pageTemplate=="newitem.tpl" || $pageTemplate=="newitem-list.tpl"}
	<li>{translate text="New Items"}</li>
{elseif $pageTemplate=="view-alt.tpl"}
	<li>{translate text=$subTemplate|replace:'.tpl':''|capitalize|translate}</li>
{elseif $pageTemplate!=""}
	<li>{translate text=$pageTemplate|replace:'.tpl':''|capitalize|translate}</li>
{/if}
