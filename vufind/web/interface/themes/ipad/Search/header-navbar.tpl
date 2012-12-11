{if $pageTemplate=='history.tpl' && $user}
  {* if we're in /Search/History and logged in, then use MyResearch header navbar instead *}
  {include file='MyResearch/header-navbar.tpl'}
{else}
  {if $recordCount > 0 && ($pageTemplate == 'list.tpl' || $pageTemplate == 'reserves-list.tpl' || $pageTemplate == 'newitem-list.tpl')} 
  <div data-role="navbar">
    <ul>
      <li><a href="#Search-narrow" data-rel="dialog" data-transition="flip">{translate text="Narrow Search"}</a></li>
      <li>
        {if $savedSearch}
          <a rel="external" href="{$path}/MyResearch/SaveSearch?delete={$searchId}">{translate text='save_search_remove'}</a>
        {else}
          <a rel="external" href="{$path}/MyResearch/SaveSearch?save={$searchId}">{translate text='save_search'}</a>
        {/if}
      </li>
    </ul>
  </div>
  {/if}
{/if}