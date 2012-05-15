<div id="sidebar-wrapper"><div id="sidebar">
  {include file="Admin/menu.tpl"}
</div></div>
<div id="main-content">
  <h1>System Administrators</h1>
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
