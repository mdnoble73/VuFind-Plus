<ul class="results comments" data-role="listview" data-split-icon="minus" data-split-theme="d" data-inset="true" data-dividertheme="e">
  <li data-role="list-divider">{translate text="Comments"}</li>
  {foreach from=$commentList item=comment}
  <li>
    <a href="#">
    <p>{$comment->comment|escape}</p>
    <p class="posted-by">{translate text='Posted by'} <strong>{$comment->fullname|escape:"html"}</strong></p>
    <span class="ui-li-aside">{$comment->created|date_format:'%D'|escape:"html"}</span>
    </a>
    {if $comment->user_id == $user->id}    
      <a rel="external" href="{$path}/Record/{$id|escape}/UserComments?delete={$comment->id}" data-comment-id="{$comment->id|escape}" class="deleteRecordComment">    
        {translate text="Delete"}  
      </a>
    {/if}
  </li>
  {foreachelse}
    <li><p>{translate text='Be the first to leave a comment'}!</p></li>
  {/foreach}
</ul>
