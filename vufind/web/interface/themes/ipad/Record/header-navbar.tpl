<div data-role="navbar">
  <ul>
    <li>
      <a {if $tab == 'Holdings'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/Holdings">{translate text='Holdings'}</a>
    </li>
    <li>
      <a {if $tab == 'Description'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/Description">{translate text='Description'}</a>
    </li>
    {if $hasTOC}
      <li>
        <a {if $tab == 'TOC'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/TOC">{translate text='Contents'}</a>
      </li>
    {/if}
    {if $hasReviews}
      <li>
        <a {if $tab == 'Reviews'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/Reviews">{translate text='Reviews'}</a>
      </li>
    {/if}
    {if false && $hasExcerpt}
      <li>
        <a {if $tab == 'Excerpt'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/Excerpt">{translate text='Excerpt'}</a>
      </li>
    {/if}
    <li>
      <a {if $tab == 'UserComments'} class="ui-btn-active"{/if} rel="external" href="{$path}/Record/{$id|escape:"url"}/UserComments">{translate text='Comments'}</a>
    </li>    
  </ul>    
</div>
