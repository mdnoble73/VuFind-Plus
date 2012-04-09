<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
	  <b class="btop"><b></b></b>
	  <div class="page">
	    
	  <form name='placeHoldForm' action="{$url}/Record/{$id|escape:"url"}/Hold" method="post">
	    {if $holdDisclaimer}
	      <div id="holdDisclaimer">{$holdDisclaimer}</div>
	    {/if}
	    {if (!isset($profile)) }
	    <label for="username">{translate text='Username'}:</label>
	    <input type="text" name="username" id="username" size="40"><br/>
	    <label for="username">{translate text='Password'}:</label>
	    <input type="password" name="password" id="password" size="40"><br/>
      <input id="loginButton" type="button" onclick="return GetPreferredBranches('{$id|escape}');" value="Login"/>
      {/if}
	    <div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
	    <div id='pickupLocationOptions'>
	    <b>{translate text="I want to pick this up at"}:</b>
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
	    {if $showHoldCancelDate == 1}
	    <div id='cancelHoldDate'><b>{translate text="Automatically cancel this hold if not filled by"}:</b>
	    <input type="text" name="canceldate" id="canceldate" size="10">
	    <br /><i>If this date is reached, the hold will automatically be cancelled for you.  This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
	    </div>
	    {/if}
	    <br />
	    <input type="hidden" name="holdType" value="hold">
	    <input type="submit" name="submit" id="requestTitleButton" value="{translate text='Request This Title'}" {if (!isset($profile))}disabled="disabled"{/if}>
	    <input type="checkbox" name="autologout" id="autologout"/> <label for="autologout">Log me out after requesting the item.</label> 
	    </div>
	  </form>
	  </div>
  	  <b class="bbot"></b>
    </div>
  </div>
</div>
<script  type="text/javascript">
  {literal}
  $(function() {
    $( "#canceldate" ).datepicker({ minDate: 0, showOn: "button", buttonImage: "{/literal}{$path}{literal}/interface/themes/marmot/images/silk/calendar.png", numberOfMonths: 2,  buttonImageOnly: true});
  });
  {/literal}
</script>

