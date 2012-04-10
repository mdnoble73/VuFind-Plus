<div id="page-content" class="content">
  
  <div id="main-content">
    <div class="resulthead"><h3>{translate text='PIN Reminder'}</h3></div>
    <div class="page">
    	{if $emailResult.error}
    		<p>{$emailResult.error}</p>
    		<div>
    			<a href="{$path}/MyResearch/EmailPin">Try Again</a>
    		</div>
    	{else}
    		Your PIN number has been sent to the email address we have on file.
    		<p> 
    		<a href="{$path}/MyResearch/Login">Login here</a>
    		</p>
			{/if}
    </div>
  </div>
</div>

