<form action="{$path}/Record/{$id|escape}/AddTag" method="post" name="tagRecord" data-ajax="false">
  <input type="hidden" name="submit" value="1" />
  <input type="hidden" name="id" value="{$id|escape}" />
  <div data-role="fieldcontain">
    <label for="addtag_tag">{translate text="Tags"}:</label>
    <input id="addtag_tag" type="text" name="tag" value=""/>
    <p>{translate text="add_tag_note"}</p>
  </div>
  <div data-role="fieldcontain">
    <input type="submit" value="{translate text='Save'}"/>
  </div>
</form>