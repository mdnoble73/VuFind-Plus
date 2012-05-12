<h4>Staff Reviews</h4>
{foreach from=$staffCommentList item=comment}
  <div class='comment'>
  	<div class="commentHeader">
    <div class='commentDate'>{$comment->created|date_format}
	    {if $comment->user_id == $user->id}
	    <span onclick='deleteComment({$id|escape:"url"}, {$comment->id}, {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal});' class="delete tool deleteComment">{translate text='Delete'}</span>
	    {/if}
    </div>
    <div class="posted"><strong>{translate text='Posted by'} {if strlen($comment->displayName) > 0}{$comment->displayName}{else}Anonymous{/if}</strong></div>
    </div>
    {$comment->comment|escape:"html"}
    
    
  </div>
{foreachelse}
  <div>{translate text='No staff reviews have been posted yet'}.</div>
{/foreach}