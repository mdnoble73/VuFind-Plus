<script type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
<div id="page-content" class="content">
	<div id="main-content">
	  <form name='placeHoldForm' action="{$url}/MyResearch/HoldMultiple" method="post">
	  <div>
	    {if $holdDisclaimer}
	      <div id="holdDisclaimer">{$holdDisclaimer}</div>
	    {/if}
	    {foreach from=$ids item=id}
	       <input type="hidden" name="selected[{$id|escape:url}]" value="on" />
	    {/foreach}
	    {if (!isset($profile)) }
	    <b>{translate text='Username'}:</b><br />
	    <input type="text" name="username" size="40" /><br />
	    <b>{translate text='Password'}:</b><br />
	    <input type="password" name="password" size="40" /><br />
      <input id="loginButton" type="button" onclick="GetPreferredBranches('{$id|escape}');" value="Login" />
	    {/if}
	    <div id='holdOptions' {if (!isset($profile)) }style='display:none'{/if}>
	    <div id='pickupLocationOptions'>
	    <input type="hidden" name ="recordId" value = "{$id|escape}" />
	    <b>{translate text="I want to pick this up at"}:</b><br />
	    {html_options name="campus" options=$pickupLocations selected=$profile.homeLocationId}
	    </div>
	    {if $showHoldCancelDate == 1}
      <div id='cancelHoldDate'><b>{translate text="Automatically cancel this hold if not filled by"}:</b>
      <input type="text" name="canceldate" id="canceldate" size="10">
      <br /><i>If this date is reached, the hold will automatically be cancelled for you.  This is a great way to handle time sensitive materials for term papers, etc. If not set, the cancel date will automatically be set 6 months from today.</i>
      </div>
      {/if}
	    <br />
	    <input type="hidden" name="holdType" value="hold" />
      <input type="submit" name="submit" id="requestTitleButton" value="{translate text='Request This Title'}" {if (!isset($profile))}disabled="disabled"{/if} />
      <input type="checkbox" name="autologout" /> Log me out after requesting the items. 
	    </div>
	  </div>
	  </form>
	</div>
</div>
