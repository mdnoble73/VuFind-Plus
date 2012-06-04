<div id="commentForm{$id}">
  <textarea name="econtentcomment" id="econtentcomment{$id}" rows="4" cols="40"></textarea>
  <a href="#" class="tool button" onclick='SaveEContentComment("{$id|escape}", {literal}{{/literal}save_error: "{translate text='comment_error_save'}", load_error: "{translate text='comment_error_load'}", save_title: "{translate text='Save Comment'}"{literal}}{/literal}); return false;'>{translate text="Add your comment"}</a>
</div>
