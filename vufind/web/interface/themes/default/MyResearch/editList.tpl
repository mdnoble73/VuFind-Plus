<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    <b class="btop"><b></b></b>
      <div class="yui-ge">

        <div class="record">

          <h1>{translate text="edit_list"}</h1>
          {if $infoMsg || $errorMsg}
          <div class="messages">
          {if $errorMsg}<div class="error">{$errorMsg|translate}</div>{/if}
          {if $infoMsg}<div class="userMsg">{$infoMsg|translate}</div>{/if}
          </div>
          {/if}
          <form method="post" name="editListForm">
          {if empty($list)}
            <p>
              {translate text='edit_list_deny'}
            </p>
          {else}
            <table>
                <tr>
                  <td><label for="list_title">{translate text='List'}:</label></td>
                  <td><input type="text" id="list_title" name="title" value="{$list->title|escape:"html"}" size="50" /></td>
                </tr>
                <tr>
                  <td><label for="list_desc">{translate text='Description'}:</label></td>
                  <td>
                    <textarea id="list_desc" name="desc" rows="3" cols="50">{$list->description|escape:"html"}</textarea>
                  </td>
                </tr>
                <tr>
                  <td>{translate text="Access"}:</td>
                  <td>
                    <input type="radio" id="public1" name="public" value="1" {if $list->public == 1}checked="checked"{/if} />
                    <label for="public1">{translate text="Public"}</label>
                    <input type="radio" id="public0" name="public" value="0" {if $list->public == 0}checked="checked"{/if} />
                    <label for="public0">{translate text="Private"}</label>
                  </td>
                </tr>
                <tr><td></td><td><br></td></tr>
              <tr><td></td><td><input type="submit" name="submit" value="{translate text='Save'}"></td></tr>
            </table>
          {/if}
          </form>
        </div>

      </div>
    <b class="bbot"><b></b></b>
    </div>

  </div>
</div>
