<div data-role="page" id="MyResearch-profile">
  {include file="header.tpl"}
  <div data-role="content">
    {if $user->cat_username}
      <h3>{translate text='Your Profile'}</h3>
      <dl class="biblio">
        <dt>{translate text='First Name'}:</dt>
        <dd>{$profile.firstname|escape}</dd>
        <dt>{translate text='Last Name'}:</dt>
        <dd>{$profile.lastname|escape}</dd>
        <dt>{translate text='Address'} 1:</dt>
        <dd>{$profile.address1|escape}</dd>
        <dt>{translate text='Address'} 2:</dt>
        <dd>{$profile.address2|escape}</dd>
        <dt>{translate text='Zip'}:</dt>
        <dd>{$profile.zip|escape}</dd>
        <dt>{translate text='Phone Number'}:</dt>
        <dd>{$profile.phone|escape}</dd>
        <dt>{translate text='Group'}:</dt>
        <dd>{$profile.group|escape}</dd>
      </dl>
    {else}
      {include file="MyResearch/catalog-login.tpl"}
    {/if}
  </div>    
  {include file="footer.tpl"}
</div>
