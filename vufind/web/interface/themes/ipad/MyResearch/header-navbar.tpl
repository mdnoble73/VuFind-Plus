{if $user}
  <div data-role="navbar">
    <ul>
      <li><a rel="external" {if $pageTemplate=="checkedout.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/CheckedOut">{translate text='Checked Out'}</a></li>
      <li><a rel="external" {if $pageTemplate=="holds.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Holds">{translate text='Holds'}</a></li>
      <li><a rel="external" {if $pageTemplate=="fines.tpl"} class="ui-btn-active"{/if} href="{$path}/MyResearch/Fines">{translate text='Fines'}</a></li>
    </ul>
  </div> 
{/if}