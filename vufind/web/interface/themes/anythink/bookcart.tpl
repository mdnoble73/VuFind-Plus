<div id="book_bag" style="display:none;">
  {* Top toolbar that is always displayed *}
  <div id="bag_open_button">
    <div class = "icon plus" id="bag_summary_holder">
      <span id ="bag_summary"></span>
    </div>
  </div>
   
  {* Canvas that displays when the bookcart is opened.  Hidden by default. *}
  <div id="book_bag_canvas" class="round left-side-round" style="display: none; ">
    <div id="book_bag_header">
    	<span id ="bag_summary_header">Cart summary</span> 
    	<a href="#" id="bag_empty_button_header" class="empty_cart">empty cart</a> 
    </div>
    {* Placeholder for items.  Will be filled with code in JavaScript *}
    <div id="bag_items"></div>
    
    {* Actions that are displayed when the user selects an action to perform. *} 
    <div id="bag_actions">
      {* e-mail book cart *}
      <div id="email_to_box" class="bag_box">
         <h3>Email Your Items</h3>
         To: 
         <input type="text" id="email_to_field" size="40"/><input type="button" class="bag_perform_action_button" value="Send"/> <a href="#" class="button round less-round bag_hide_button">x</a>               
      </div>
      
      <div id="bookcart_login" class="bag_box">
          <div id='bag_login'>
            <form method="post" action="{$url}/MyResearch/Home" id="loginForm_bookbag">
              <div>
              {translate text='Enter the barcode from your library card'}: <br/>
              <input type="text" name="username" id="bag_username" value="{$username|escape}" size="25"/>
              <br/>
              {translate text='Password'}:<br/>
              <input type="password" name="password" id="bag_password" size="25"/>
              <br/>
              <a href="#" class="button round less-round" id="bag_login_submit">Login</a>
              <a href="#" class="button round less-round bag_hide_button" id="bag_login_cancel">Cancel</a>
              <div id="bookcartLoginOptions">
                <div id='needCardLabel'>
					        <a href="{$path}/MyResearch/GetCard">I need a Library Card</a>
					      </div>
					      <div id='emailPinLabel' class='loginFormRow'>
					        <a href="{$path}/MyResearch/EmailPin">I forgot my PIN number</a>
					      </div>
              </div>
              </div>
           </form>
         </div>
        
      </div>
      
      {* Save items to a list *}          
      <div id="save_to_my_list_tags" class="bag_box">
        <div id='bag_choose_list'>
	        <a href="#" class="button round less-round longer-button" id="new_list">Create a new List</a>
	        {* Controls for creating a new list*}
	        {if $listError}<p class="error">{$listError|translate}</p>{/if}
	        <div id='new_list_controls' style='display:none'>
				<form method="post" action="{$url}/MyResearch/ListEdit" id="listFormBookCart"
				      onsubmit='bagAddList(this, &quot;{translate text='add_list_fail'}&quot;); return false;'>
				  <div>
				  {translate text="List"}:<br/>
				  <input type="text" id="listTitleBag" name="title" value="{$list->title|escape:"html"}" size="34"/><br/>
				  {translate text="Description"}:<br/>
				  <textarea name="desc" id="listDesc" rows="2" cols="40">{$list->desc|escape:"html"}</textarea><br/>
				  {translate text="Access"}:<br/>
				  {translate text="Public"} <input type="radio" name="public" value="1"/>
				  {translate text="Private"} <input type="radio" name="public" value="0" checked="checked"/><br/>
				  <input type="submit" name="submit" value="{translate text="Create List"}"/>
		          <a href="#" class="button round less-round longer-button" id="choose_existing_list">Select Existing List</a>
		          <a href="#" class="button round less-round bag_hide_button">Cancel</a>
		          </div>
				</form>
	        </div>
	        
	        {* Controls for adding the titles to an existing list *}
	        <div id='existing_list_controls'>
		        - or -<br/>
		        {translate text='Choose a List:'}<br/>
		        <select name="bookbag_list_select" id="bookbag_list_select">
		          {foreach from=$userLists item="list"}
		          <option value="{$list.id}">{$list.title|escape:"html"}</option>
		          {foreachelse}
		          <option value="">{translate text='My Favorites'}</option>
		          {/foreach}
		        </select>
		        <div id='bag_tags'>
		          Tags:<br/> <input type="text" id="save_tags_field" size="34"/><br/>
		          Tags will apply to all items being added.  Use commas to separate tags. If you would like to have a comma within a tag, enclose it within quotes.
		        </div>
		        <input type="button" class="bag_perform_action_button" value="Add" /> 
		        <a href="#" class="button round less-round bag_hide_button">Cancel</a>
	        </div>
	      </div>
      </div>
      <div id="bag_action_in_progress" class="bag_box">                
          <span id="bag_action_in_progress_text">Processing....</span>
      </div>
      <div id="bag_errors" class="bag_box">Warning: <span id="bag_error_message"></span></div> 
    </div>
    
    <div id="bag_links">
      {*
      <div class="button round less-round hide logged-in-button" style="display: none; "><a href="#" id="bag_email_button" class="icon email_bag">Email</a></div>    
      *}                                
      <div class="button round less-round longer-button" ><a href="#" id="bag_request_button" class="">Place Hold</a></div>
      <div class="button round less-round"><a href="#" id="bag_print_button" class="print_bag">Print</a></div>
      <div id="bag_add_to_my_list_button" class="logged-in-button" style="display: none; "><a href="#" >Add to <span class="myListLabel">My List</span></a></div>  
      <div class="button round less-round longer-button logged-out-button" id="login_bag">Login</div>
   </div>
       
  </div>
</div> 