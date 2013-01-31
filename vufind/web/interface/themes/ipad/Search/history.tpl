<div data-role="page" id="Search-history">
  {include file="header.tpl"}
  <div data-role="content">
    {if !$noHistory}
      {if $saved}
        <ul class="results history" data-role="listview" data-dividertheme="e" data-split-icon="minus" data-split-theme="c" data-inset="true">
          <li data-role="list-divider">{translate text="history_saved_searches"}</li>
          {foreach item=info from=$saved name=historyLoop}
          <li>
            <a rel="external" href="{$info.url|escape}">
            <div class="result">
            <h3>
              {if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}
            </h3>
            <span class="ui-li-count">{$info.hits}</span>
            <p><strong>{translate text="history_time"}</strong>: {$info.time}</p>
            {foreach from=$info.filters item=filters key=field}
              {foreach from=$filters item=filter}
                <p><strong>{translate text=$field|escape}</strong>: {$filter.display|escape}</p>
              {/foreach}
            {/foreach}
            </div>
            </a>
            <a rel="external" href="{$path}/MyResearch/SaveSearch?delete={$info.searchId|escape:"url"}&amp;mode=history">{translate text="history_delete_link"}</a>
          </li>
          {/foreach}
        </ul>
      {/if}
      
      {if $links}
        <ul class="results history" data-role="listview" data-dividertheme="e" data-split-icon="plus" data-split-theme="c" data-inset="true">
          <li data-role="list-divider">{translate text="history_recent_searches"}</li>
          {foreach item=info from=$links name=historyLoop}
          <li>
            <a rel="external" href="{$info.url|escape}">
            <div class="result">
            <h3>
              {if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}
            </h3>
            <span class="ui-li-count">{$info.hits}</span>
            <p><strong>{translate text="history_time"}</strong>: {$info.time}</p>
            {foreach from=$info.filters item=filters key=field}
              {foreach from=$filters item=filter}
                <p><strong>{translate text=$field|escape}</strong>: {$filter.display|escape}</p>
              {/foreach}
            {/foreach}
            </div>
            </a>
            <a rel="external" href="{$path}/MyResearch/SaveSearch?save={$info.searchId|escape:"url"}&amp;mode=history">{translate text="history_save_link"}</a>
          </li>
          {/foreach}
        </ul>
      {/if}
      
      <a rel="external" href="{$path}/Search/History?purge=true" data-role="button">{translate text="history_purge"}</a>
      
    {else}
      <p>{translate text="history_no_searches"}</p>
    {/if}
  </div>
  {include file="footer.tpl"}
</div>
