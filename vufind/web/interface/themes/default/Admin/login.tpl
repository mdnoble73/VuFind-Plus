<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
      <div class="resulthead"><h3>{translate text='Login'}</h3></div>
        <div class="page">
          {if $message}<div class="error">{$message|translate}</div>{/if}  
          <table class="citation" width="100%">
            <form method="post" action="{$path}/Admin/Home" name="adminLoginForm">
              <tr>
                <td width="80px">{translate text='Administrative Login'}: </td>
                <td><input type="text" name="login" value="{$login|escape}" size="15"/></td>
              </tr>
              <tr>
                <td>Password: </td>
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
               {/if}
               </td>
             </tr>
           </form>
         </table>
         <script type="text/javascript">document.adminLoginForm.login.focus();</script>
      </div>
      <b class="bbot"><b></b></b>
    </div>
</div>