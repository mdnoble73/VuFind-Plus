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
		
			<div class="myAccountTitle">Account Settings</div></div>
			
			<div class="page">
				<form action='' method='post'>
					<table class="citation" width="100%">
						<tr><th width="100px">{translate text='Full Name'}:</th><td>{$profile.fullname|escape}</td></tr>
						{if !$offline}
						<tr><th>{translate text='Fines'}:</th><td>{$profile.fines|escape}</td></tr>
						<tr><th>{translate text='Expiration Date'}:</th><td>{$profile.expires|escape}</td></tr>
						{/if}
						<tr><th>{translate text='Home Library'}:</th><td>{$profile.homeLocation|escape}</td></tr>
					</table>

					<br/>
				<div id="additionalProfileOptions">
                
                	
					<ul>
						<li><a href="#contacttab">Contact Info</a></li>
<!--						<li><a href="#catalogtab">Catalog Options</a></li>	-->
						<li><a href="#overdrivetab">OverDrive Options</a></li>
                        <!--<li><a href="#enewstab">Update eNewsletter Preferences</a></li>-->
						{if count($user->roles) > 0}
							<li><a href="#rolestab">Roles</a></li>
						{/if}
					</ul>

					<div id="contacttab">
						<table class="citation">
							{if !$offline}
								<tr><th>{translate text='Address'}:</th><td>{if $edit && $canUpdateContactInfo && $canUpdateAddress}<input name='address1' value='{$profile.address1|escape}' size='50' maxlength='75' />{else}{$profile.address1|escape}{/if}</td></tr>
								<tr><th>{translate text='City'}:</th><td>{if $edit && $canUpdateContactInfo && $canUpdateAddress}<input name='city' value='{$profile.city|escape}' size='50' maxlength='75' />{else}{$profile.city|escape}{/if}</td></tr>
								<tr><th>{translate text='State'}:</th><td>{if $edit && $canUpdateContactInfo && $canUpdateAddress}<input name='state' value='{$profile.state|escape}' size='50' maxlength='75' />{else}{$profile.state|escape}{/if}</td></tr>
								<tr><th>{translate text='Zip'}:</th><td>{if $edit && $canUpdateContactInfo && $canUpdateAddress}<input name='zip' value='{$profile.zip|escape}' size='50' maxlength='75' />{else}{$profile.zip|escape}{/if}</td></tr>
								<tr><th>{translate text='Primary Phone Number'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='phone' value='{$profile.phone|escape}' size='50' maxlength='75' />{else}{$profile.phone|escape}{/if}</td></tr>
								{if $showWorkPhoneInProfile}
									<tr><th>{translate text='Work Phone Number'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='workPhone' value='{$profile.workPhone|escape}' size='50' maxlength='75' />{else}{$profile.workPhone|escape}{/if}</td></tr>
								{/if}
							{/if}
							<tr><th>{translate text='E-mail'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input name='email' value='{$profile.email|escape}' size='50' maxlength='75' />{else}{$profile.email|escape}{/if}</td></tr>
							{if $showPickupLocationInProfile}
								<tr>
									<th>{translate text='Pickup Location'}:</th>
									<td>
										{if $edit == true && $canUpdateContactInfo == true}
											<select name="pickupLocation" id="pickupLocation">
												{if count($pickupLocations) > 0}
													{foreach from=$pickupLocations item=location}
														<!-- <option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option> -->
														<option value="{$location->code}" {if $location->displayName|escape == $profile.homeLocation|escape}selected="selected"{/if}>{$location->displayName}</option>
													{/foreach}
												{else}
													<option>placeholder</option>
												{/if}
											</select>
										{else}
											{$profile.homeLocation|escape}
										{/if}
									</td>
								</tr>
							{/if}
							{if $showNoticeTypeInProfile}
								<tr><th>{translate text='Receive renew/overdue<br />notices by'}:</th><td>{if $edit == true && $canUpdateContactInfo == true}<input type="radio" value="p" id="notices" name="notices" {if $profile.notices=="p"}checked{/if}>Telephone<br/><input type="radio" value="z" id="notices" name="notices" {if $profile.notices=="z"}checked{/if}>Email{else}{$profile.noticePreferenceLabel|escape}{/if}</td></tr>
							{/if}
                            <tr><th>{translate text='eNewsletter Preferences'}:</th><td><a href="http://www.library.nashville.org/Info/gen_email.asp" target='_blank'>Update Preferences (will open in a new window)</a></td></tr>
						</table>
					</div>

<!--
					<div id="catalogtab">
						<table class="citation">
							<tr><th>{translate text='My First Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation1" options=$locationList selected=$profile.myLocation1Id}{else}{$profile.myLocation1|escape}{/if}</td></tr>
							<tr><th>{translate text='My Second Alternate Library'}:</th><td>{if $edit == true}{html_options name="myLocation2" options=$locationList selected=$profile.myLocation2Id}{else}{$profile.myLocation2|escape}{/if}</td></tr>
							{if $userIsStaff}
							<tr><th>{translate text='Bypass Automatic Logout'}:</th><td>{if $edit == true}<input type='radio' name="bypassAutoLogout" value='yes' {if $profile.bypassAutoLogout==1}checked='checked'{/if}/>Yes&nbsp;&nbsp;<input type='radio' name="bypassAutoLogout" value='no' {if $profile.bypassAutoLogout==0}checked='checked'{/if}/>No{else}{if $profile.bypassAutoLogout==0}No{else}Yes{/if}{/if}</td></tr>
							{/if}
						</table>
					</div>
-->

					<div id="overdrivetab">
						<table class="citation">
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
					
                    <!--
                    <div id="enewstab">
                            {literal}
                            <script type="text/javascript">
                            $("#additionalProfileOptions").on('activate', function(event, ui){
                                 if (ui|.newPanel.attr('id') != |'enewstab'){
                                     $(':sumbit').hide();
                                 }else{
                                     $(':sumbit').show();
                                 }
                            });
                            </script>
                            {/literal}
							 <div id="emailNewsletterSignup">
            					<div class="resulthead">
                					<div class="myAccountTitle">Email Newsletter Options</div>
                				</div>
            					<link href="https://app.e2ma.net/css/signup.lrg.css" rel="stylesheet" type="text/css"><script type="text/javascript" src="https://app.e2ma.net/app2/audience/tts_signup/1761703/8671e7b4d35f3d498f22e225dbe70bd9/19914/?v=a"></script><div id="load_check" class="signup_form_message" >This form needs Javascript to display, which your browser doesn't support. <a href="https://app.e2ma.net/app2/audience/signup/1761703/19914/?v=a"> Sign up here</a> instead </div><script type="text/javascript">signupFormObj.drawForm();</script>
            				</div>
					</div>
					-->
                    
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
				{if !$offline}
					{if $edit == true}
					<input type='submit' value='Update Options' name='update'/>
					{else}
					<input type='submit' value='Edit Options' name='edit'/>
					{/if}
				{/if}
			
			</form>
			
		{else}
			<div class="page">
			You must login to view this information. Click <a href="{$path}/MyResearch/Login">here</a> to login.
		{/if}
			</div>

           
		</div>
	
	</div>
</div>
