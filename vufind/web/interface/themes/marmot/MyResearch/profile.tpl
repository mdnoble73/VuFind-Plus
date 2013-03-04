<div id="page-content" class="content">
	<div id="sidebar">
		{include file="MyResearch/menu.tpl"}
			
		{include file="Admin/menu.tpl"}
	</div>
	
	<div id="main-content">
		{if $user->cat_username}
			<div class="resulthead">
			{if $profile.web_note}
				<div id="web_note">{$profile.web_note}</div>
			{/if}
		
			<h3>{translate text='Your Profile'}</h3></div>
			
			<div class="page">
			<form action='' method='post'>
			<table class="citation" width="100%">
				<tr><th width="100px">{translate text='Full Name'}:</th><td>{$profile.fullname|escape}</td></tr>
				<tr><th>{translate text='Fines'}:</th><td>{$profile.fines|escape}</td></tr>
				<tr><th>{translate text='Expiration Date'}:</th><td>{$profile.expires|escape}</td></tr>
				<tr><th>{translate text='Home Library'}:</th><td>{$profile.homeLocation|escape}</td></tr>
			</table>
			
			<br/>
			<div id="additionalProfileOptions">
				<ul>
					<li><a href="#contacttab">Contact Info</a></li>
					<li><a href="#catalogtab">Catalog Options</a></li>
					<li><a href="#overdrivetab">OverDrive Options</a></li>
					{if count($user->roles) > 0}
						<li><a href="#rolestab">Roles</a></li>
					{/if}
				</ul>
				
				<div id="contacttab">
					<table class="citation" width="100%">
						<tr><th>{translate text='Address'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='address1' value='{$profile.address1|escape}' size='50' maxlength='75' />{else}{$profile.address1|escape}{/if}</td></tr>
						<tr><th>{translate text='City'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='city' value='{$profile.city|escape}' size='50' maxlength='75' />{else}{$profile.city|escape}{/if}</td></tr>
						<tr><th>{translate text='State'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='state' value='{$profile.state|escape}' size='50' maxlength='75' />{else}{$profile.state|escape}{/if}</td></tr>
						<tr><th>{translate text='Zip'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='zip' value='{$profile.zip|escape}' size='50' maxlength='75' />{else}{$profile.zip|escape}{/if}</td></tr>
						<tr><th>{translate text='Phone Number'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='phone' value='{$profile.phone|escape}' size='50' maxlength='75' />{else}{$profile.phone|escape}{/if}</td></tr>
						<tr><th>{translate text='E-mail'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='email' value='{$profile.email|escape}' size='50' maxlength='75' />{else}{$profile.email|escape}{/if}</td></tr>
					</table>
				</div>
				
				<div id="catalogtab">
					<table class="citation" width="100%">
						<tr><th>{translate text='My First Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation1" options=$locationList selected=$profile.myLocation1Id}{else}{$profile.myLocation1|escape}{/if}</td></tr>
						<tr><th>{translate text='My Second Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation2" options=$locationList selected=$profile.myLocation2Id}{else}{$profile.myLocation2|escape}{/if}</td></tr>
						{if $userIsStaff}
						<tr><th>{translate text='Bypass Automatic Logout'}:</th><td>{if $edit == true}<input type='radio' name="bypassAutoLogout" value='yes' {if $profile.bypassAutoLogout==1}checked='checked'{/if}/>Yes&nbsp;&nbsp;<input type='radio' name="bypassAutoLogout" value='no' {if $profile.bypassAutoLogout==0}checked='checked'{/if}/>No{else}{if $profile.bypassAutoLogout==0}No{else}Yes{/if}{/if}</td></tr>
						{/if}				
					</table>
				</div>
				
				<div id="overdrivetab">
					<table class="citation" width="100%">
						<tr><th>{translate text='OverDrive Hold e-mail'}:</th><td>{if $edit == true}<input name='overdriveEmail' value='{$profile.overdriveEmail|escape}' size='50' maxlength='75' />{else}{$profile.overdriveEmail|escape}{/if}</td></tr>
						<tr><th>{translate text='Prompt for OverDrive e-mail'}:</th><td>{if $edit == true}<input type='radio' name="promptForOverdriveEmail" value='yes' {if $profile.promptForOverdriveEmail==1}checked='checked'{/if}/>Yes&nbsp;&nbsp;<input type='radio' name="promptForOverdriveEmail" value='no' {if $profile.promptForOverdriveEmail==0}checked='checked'{/if}/>No{else}{if $profile.promptForOverdriveEmail==0}No{else}Yes{/if}{/if}</td></tr>
					</table>
					{if $overDriveLendingOptions}
						<h3>Lending Options</h3>
						<p>Select how long you would like to checkout each type of material from OverDrive.</p>
						<table class="citation">
							{foreach from=$overDriveLendingOptions item=lendingOption}
								<tr>
									<th>{$lendingOption.name}:</th>
									<td>
										<div id="{$lendingOption.id}Buttons">
											{foreach from=$lendingOption.options item=option}
												{if $edit}
													<input type="radio" name="{$lendingOption.id}" value="{$option.value}" id="{$lendingOption.id}_{$option.value}" {if $option.selected}checked="checked"{/if}><label for="{$lendingOption.id}_{$option.value}">{$option.name}</label>
												{elseif $option.selected}
													{$option.name}
												{/if}
											{/foreach}
										</div>
										{literal}
										<script type="text/javascript">
										$(function() {
											$( "#{/literal}{$lendingOption.id}Buttons{literal}" ).buttonset();
										});
										</script>
										{/literal}
									</td>
								</tr>
							{/foreach}
						</table>
					{/if}
				</div>
				
				{* Display user roles if the user has any roles*}
				{if count($user->roles) > 0}
					<div id="rolestab">
						<table class='citation'>
							{foreach from=$user->roles item=role}
								<tr><td>{$role}</td></tr>
							{/foreach} 
						</table>
					</div>
				{/if}
			</div>
			
			{literal}
			<script type="text/javascript">
				$(function() {
					$("#additionalProfileOptions").tabs();
				});
			</script>
			{/literal}
			
			<br />
			{if $edit == true}
			<input type='submit' value='Update Options' name='update'/>
			{else}
			<input type='submit' value='Edit Options' name='edit'/>
			{/if}
			
			</form>
			
		{else}
			<div class="page">
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}</div>
		<b class="bbot"><b></b></b>
		</div>
	
	</div>
</div>
