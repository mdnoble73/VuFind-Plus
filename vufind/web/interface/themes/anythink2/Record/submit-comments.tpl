<div id="commentForm{$shortId}">
  <textarea name="comment" id="comment{$shortId}" rows="4" cols="40"></textarea>
  <a href="#" class="tool button" onclick='SaveComment("{$id|escape}", "{$shortId}", {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal}); return false;'>{translate text="Add your comment"}</a>
</div>
