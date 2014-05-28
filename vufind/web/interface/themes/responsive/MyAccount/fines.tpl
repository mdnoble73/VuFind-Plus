<div id="page-content" class="content">
  <div id="sidebar">
    {include file="MyAccount/menu.tpl"}
    
    {include file="Admin/menu.tpl"}
  </div>
  
  <div id="main-content">
    {if $user->cat_username}
      <div class="myAccountTitle">{translate text='Fines'}</div>
      {if $userNoticeFile}
        {include file=$userNoticeFile}
      {/if}
      
      {$finesData}
    {else}
      You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
    {/if}
  </div>

</div>
