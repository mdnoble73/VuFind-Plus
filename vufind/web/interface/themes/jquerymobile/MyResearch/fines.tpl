<div data-role="page" id="MyResearch-fines">
  {include file="header.tpl"}
  <div data-role="content">
    {if $user->cat_username}
      <h3>{translate text='Your Fines'}</h3>
      {if $profile.finesval > 0}
        <span title="Please Contact your local library to pay fines or Charges." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to pay fines or Charges.')">Your account has {$profile.fines} in fines.</span>
        {if $showEcommerceLink && $profile.finesval > $minimumFineAmount}
        <a href='https://www.millennium.marmot.org/patroninfo~S93' target='_blank'><br/>Click to Pay Fines Online</a>
        {/if}
      {else}
        <p>{translate text='You do not have any fines'}.</p>
      {/if}
    {else}
      You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
    {/if}
  </div>
  {include file="footer.tpl"}
</div>
