<div id="page-content" class="content">
	
		<div id='placeHoldForm'>
			<h3 class="myAccountTitle">Placing holds on <span id='newHoldCount'>{$ids|@count}</span> titles.</h3>
            <form name='placeHoldForm' action="{$path}/MyResearch/HoldMultiple" method="post">
				{foreach from=$ids item=id}
					<input type="hidden" name="selected[{$id|escape:url}]" value="on" />
				{/foreach}
				<ol class="showNumbers">
					{foreach from=$holdings item=holding}
						<li>{$holding}</li>
					{/foreach}
				</ol>
				<input type="hidden" name="holdCount" id="holdCount" value="{$ids|@count}"/>
				<div class="pageWarning" id="overHoldCountWarning" {if !$showOverHoldLimit}style="display:none"{/if}>Warning: There is a maximum of <span class='maxHolds'>{$maxHolds}</span> holds allowed on your account.  You currently have <span class='currentHolds'>{$currentHolds}</span> on your account. Holds for more than <span class='maxHolds'>{$maxHolds}</span> will not be placed.</div>
			

				<ul class="loginSidebar">
                <li>Holds let you have any item delivered to you at your branch library. All holds deliveries are free.</li>
				<li>We will notify you when your items are ready for pick up. Upon notification, you will have at least seven days to pick up the item.</li>
                <li>In Account Settings, you may choose to be notified by email, phone, or postcard.</li>
                </ul>

			
			{if $fromCart}
				<input type="hidden" name="fromCart" value="true" />
			{/if}
			{if $holdDisclaimer}
				<div id="holdDisclaimer">{$holdDisclaimer}</div>
			{/if}
			
			{if (!isset($profile)) }
		<div id='loginFormFields'>
			<div id ='loginUsernameRow' class='loginFormRow'>
				<div class='loginLabel'>{translate text='Username'}: </div>
                <div class='loginField'><input type="text" pattern="[0-9]*" name="username" id="username" value="{$username|escape}" size="28" nblur="this.value=this.value.replace(/\s+/g,'')"/></div>
            </div>
            <div id ='loginPasswordRow' class='loginFormRow'>
                <div class='loginLabel'>{translate text='Password'}: </div>
                <div class='loginField'><input type="password" pattern="[0-9]*" name="password" id="password" size="28"/></div>
            </div>
			{if $allowPinReset}
			<div id ='loginPasswordRow2' class='loginFormRow'>
				<div class='loginLabel'>&nbsp;</div>
                <div class='loginField'><a href="#" onclick="ajaxLightbox('/MyResearch/AJAX?method=getPinResetForm')">Forgot PIN?</a></div>
            </div>
			{/if}
			{if !$inLibrary}
			<div id ='loginPasswordRow3' class='loginFormRow'>
				<div class='loginLabel'>&nbsp;</div>
			<!--
                        <input type="checkbox" id="rememberMe" name="rememberMe" checked="checked" /><label for="rememberMe">{translate text="Remember Me"}</label>
						-->
                        <div class='grayUnavailalbeMessage'>Remember me currently unavailable</div>
			</div>
			{/if}                
			<div id='loginSubmitButtonRow' class='loginFormRow'>
				<div class='loginLabel'>&nbsp;</div>
				<div class='loginField center'><input id="loginButton" type="button" onclick="GetPreferredBranches('{$id|escape}');" value="Login"/></div>
			</div>
			{/if}
            </div>
			<div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
				<div class='pickupLocationOptions loginFormRow'>
					<div class='loginLabel'>{translate text="I want to pick this up at"}: </div>
					<div class='loginField'>
						<select name="campus" id="campus">
							{if count($pickupLocations) > 0}
								{foreach from=$pickupLocations item=location}
									<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
								{/foreach}
							{else} 
								<option>placeholder</option>
							{/if}
						</select>
					</div>
				</div>
				{if $showHoldCancelDate == 1}
					<div id='cancelHoldDate' class='loginFormRow'>
						<div class='loginLabel'>{translate text="Automatically cancel this hold if not filled by"}:</div>
						<div class='loginField'>
							<input type="text" name="canceldate" id="canceldate" size="10" {if $defaultNotNeededAfterDays}value="{$defaultNotNeededAfterDays}"{/if}>
						</div>
						<div class='loginFormRow'>
							<i>If this date is reached, the hold will automatically be cancelled for you.	This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
						</div>
						
					</div>
				{/if}
				<br />
				<input type="hidden" name="holdType" value="hold"/>
				<input type="submit" name="submit" id="requestTitleButton" value="{translate text='Submit Hold Request'}" {if (!isset($profile))}disabled="disabled"{/if}/>
				<input type="checkbox" name="autologout" {if $inLibrary == true}checked="checked"{/if}/> Log me out after requesting the item. 
			</div>
            	</form>
		</div>

</div>
<script	type="text/javascript">
	{literal}
	$(function() {
		$( "#canceldate" ).datepicker({ minDate: 0, showOn: "button", buttonImage: "{/literal}{$path}{literal}/images/silk/calendar.png", numberOfMonths: 2,	buttonImageOnly: true});
	});
	{/literal}
</script>
