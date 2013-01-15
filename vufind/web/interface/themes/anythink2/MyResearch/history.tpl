<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyResearch/menu.tpl"}
  </div>
  
  <div id="main-content">
        {if $user->cat_username}
          <div class="resulthead"><h3>{translate text='Your Reading History'}</h3></div>
          <div class="page">
          {if $transList}
          <ul class="filters">
          {foreach from=$transHistory item=resource name="recordLoop"}
            {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
            <li class="result alt">
            {else}
            <li class="result">
            {/if}
              <div class="yui-ge">
                <div class="yui-u first">
                  <img src="{$coverUrl}/bookcover.php?id={$resource.id}&amp;isn={$resource.isbn|@formatISBN}&amp;size=small&amp;category={$record.format_category.0|escape:"url"}" class="alignleft">

                  <div class="resultitem">
                    <a href="{$path}/Record/{$resource.id|escape:"url"}" class="title">{$resource.title|escape}</a><br />
                    {if $resource.author}
                    {translate text='by'}: <a href="{$path}/Author/Home?author={$resource.author|escape:"url"}">{$resource.author|escape}</a><br />
                    {/if}
                    {if $resource.tags}
                    {translate text='Your Tags'}:
                    {foreach from=$resource.tags item=tag name=tagLoop}
                      <a href="{$path}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape}</a>{if !$smarty.foreach.tagLoop.last},{/if}
                    {/foreach}
                    <br />
                    {/if}
                    {if $resource.notes}
                    {translate text='Notes'}: {$resource.notes|escape}<br />
                    {/if}

                    {if is_array($resource.format)}
                      {foreach from=$resource.format item=format}
                        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
                      {/foreach}
                    {else}
                      <span class="iconlabel {$resource.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$resource.format}</span>
                    {/if}

                    <br />
                    
                    <b>{translate text='Due'}: {$resource.duedate|escape}</b>

                  </div>
                </div>

              </div>
            </li>
          {/foreach}
          </ul>
          {else}
          {translate text='You do not have any items in your Reading History.'}.
          {/if}
        {else}
          <div class="page">
          You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
        {/if}</div>

    </div>
  </div>

</div>