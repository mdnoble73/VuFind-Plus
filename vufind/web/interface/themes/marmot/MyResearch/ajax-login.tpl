<div id="page-content" class="content">
  {if $message}<div class="error">{$message|translate}</div>{/if}
  <div class="resulthead">
  <h3>{translate text='Login to your account'}</h3>
  </div>
  <div>
	  <form method="post" action="{$path}/MyResearch/Home" id="loginForm">
	    <div >
	     <table class="citation" width="100%">
          <tr>
            <td style="width:80px">{translate text='Username'}: </td>
            <td><input type="text" name="username" value="{$username|escape}" size="15"/></td>
          </tr>
          <tr>
            <td>{translate text='Password'}: </td>
            <td><input type="password" name="password" size="15"/></td>
          </tr>
          <tr style="border:0;">
            <td></td>
            <td>
              <input type="submit" name="submit" value="{translate text='Login'}"/>
              {if $followup}
                <input type="hidden" name="followup" value="{$followup}"/>
                {if $followupModule}<input type="hidden" name="followupModule" value="{$followupModule}"/>{/if}
                {if $followupAction}<input type="hidden" name="followupAction" value="{$followupAction}"/>{/if}
                {if $recordId}<input type="hidden" name="recordId" value="{$recordId|escape:"html"}"/>{/if}
              {/if}
              {if $returnUrl}<input type="hidden" name="returnUrl" value="{$returnUrl}"/>{/if}
                 
              {if $comment}
                <input type="hidden" name="comment" name="comment" value="{$comment|escape:"html"}"/>
              {/if}
            </td>
          </tr>
        </table>
	    </div>
	  </form>
  </div>
  <script type="text/javascript">$('#username').focus();</script>
</div>

