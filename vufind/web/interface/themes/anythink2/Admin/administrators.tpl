<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>System Administrators</h1>
          <br />

          <table class="citation">
            <tr>
              <th>Login</th>
              <th>Username</th>
            </tr>
            {if isset($administrators) && is_array($administrators)}
	            {foreach from=$administrators item=admin key=id}
	            <tr>
	              <td>{$admin->login}</td>
	              <td>{$admin->name}</td>
	            </tr>
	            {/foreach}
	        {/if}
          </table>
        </div>
      </div>
      <b class="bbot"><b></b></b>      
    </div>
  </div>
</div>
