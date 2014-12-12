{if $user->cat_username}
  <h2>{translate text='Fines'}</h2>
  {if $userNoticeFile}
    {include file=$userNoticeFile}
  {/if}

  {$finesData}
{else}
  You must login to view this information. Click <a href="{$path}/MyAccount/Login">here</a> to login.
{/if}
