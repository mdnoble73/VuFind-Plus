<div data-role="navbar">
  <ul>        
    {if $user}
      <li><a rel="external" {if $pageTemplate=="favorites.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Favorites">{translate text='Favorites'}</a></li>
      <li><a rel="external" {if $pageTemplate=="history.tpl"} class="ui-btn-active"{/if} href="{$path}/Search/History?require_login">{translate text='History'}</a></li>    
      <li><a rel="external" href="{$path}/MyResearch/Logout">{translate text="Logout"}</a></li>
    {else}
      <li><a data-rel="dialog" href="#Language-dialog" data-transition="pop">{translate text="Language"}</a></li>
      <li><a rel="external" href="{$path}/MyResearch/Home">{translate text="Account"}</a></li>
    {/if}
  </ul>
</div>