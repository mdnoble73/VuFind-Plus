<div id="commentForm{$id}">
  <textarea name="comment" id="comment{$id}" rows="4" cols="48"></textarea>
  <span class="tool button" onclick='SaveComment("{$id|escape}", {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal}); return false;'>{translate text="Add your comment"}</span>
</div>