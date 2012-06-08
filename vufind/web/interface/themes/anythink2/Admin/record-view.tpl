<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>View Record</h1>

          {if $record}
            <table class="citation">
              {foreach from=$record item=value key=field}
                {if is_array($value)}
                  {foreach from=$value item=current}
                    <tr>
                    <th>{$field}: </th>
                    <td>
                      <div style="width: 350px; overflow: auto;">{$current|escape}</div>
                    </td>
                    </tr>
                  {/foreach}
                {else}
                  <tr>
                  <th>{$field}: </th>
                  <td>
                    <div style="width: 350px; overflow: auto;">{$value|escape}</div>
                  </td>
                  </tr>
                {/if}
              {/foreach}
            </table>
          {else}
          <p>Could not load record {$recordId|escape}.</p>
          {/if}
        </div>
      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>