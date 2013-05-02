{foreach from=$commentList item=comment}
  <div class='comment'>
  	<div class="commentHeader">
    <div class='commentDate'>{$comment->created|date_format}
	    {if $user && ($comment->user_id == $user->id || $user->hasRole('opacAdmin'))}
	    <span onclick='deleteEContentComment({$id|escape:"url"}, {$comment->id}, {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal});' class="deleteComment"><span class="silk delete">&nbsp;</span>{translate text='Delete'}</span>
	    {/if}
    </div>
    <div class="posted"><strong>{translate text='Review by'} {if strlen($comment->displayName) > 0}{$comment->displayName}{else}{$comment->fullname}{/if}</strong></div>
    </div>
    {$comment->comment|escape:"html"}
    
    
  </div>
{foreachelse}
  <div>{translate text='Be the first to leave a comment'}!</div>
{/foreach}