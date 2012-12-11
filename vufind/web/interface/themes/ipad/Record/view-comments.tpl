{* Pull in comments from a separate file -- this separation allows the same template
   to be used for refreshing this list via AJAX. *}
{include file="Record/view-comments-list.tpl"}

<form name="comments_form" id="comments_form" action="{$path}/Record/{$id|escape:"url"}/UserComments" method="post" data-ajax="false">
  <input type="hidden" name="id" value="{$id|escape}"/>
  <div data-role="fieldcontain">
    <label for="comments_form_comment">{translate text="Your Comment"}:</label>
    <textarea id="comments_form_comment" name="comment"></textarea>
  </div>
  <div data-role="fieldcontain">
    <input type="submit" value="{translate text="Add your comment"}"/>
  </div>
</form>
