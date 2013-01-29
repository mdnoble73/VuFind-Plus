{strip}
<div id="book_bag" style="display:none;">
	{* Top toolbar that is always displayed *}
	<div id="bag_open_button">
		<div id="bag_summary_holder">
			<span class="silk img_icon_plus" id="bag_open_button_icon">&nbsp;</span>
			<span id ="bag_summary"></span> 
			<a href="#" id="bag_empty_button" class="empty_cart">empty cart&nbsp;&nbsp;<span class="silk bin_empty">&nbsp;</span></a> 
		</div>
	</div>
	 
	{* Canvas that displays when the bookcart is opened. Hidden by default. *}
	<div id="book_bag_canvas" class="round left-side-round" style="display: none;">
		{* Top Panel only one element will be displayed at a time within the top panel. *}
		<div id="bag_top_panel">
			
			{* Placeholder for items.	Will be filled with code in JavaScript *}
			<div id="bag_items" class="bag_box" ></div>
			
			{* e-mail book cart *}
			<div id="email_to_box" class="bag_box" style="display:none">
				<h3>Email Your Items</h3>
				To: 
				<input type="text" id="email_to_field" size="40" />
			</div>
			
			{* login *}
			<div id="bookcart_login" class="bag_box" style="display:none">
				<h3>Login</h3>
				<div id='bag_login'>
					<form method="post" action="{$path}/MyResearch/Home" id="loginForm_bookbag">
						<div>
							{translate text='Username'}: <br />
							<input type="text" name="username" id="bag_username" value="{$username|escape}" size="25"/>
							<br />
							{translate text='Password'}:<br />
							<input type="password" name="password" id="bag_password" size="25"/>
							<br />
							<input type="checkbox" id="showPwdCart" name="showPwd" onclick="return pwdToText('bag_password')"/><label for="showPwdCart">{translate text="Reveal Password"}</label>
						</div>
					</form>
				 </div>
			</div>
			
			<div id='create_list' class="bag_box" style="display:none">
				{* Controls for creating a new list*}
				{if $listError}<p class="error">{$listError|translate}</p>{/if}
				<form method="post" action="{$path}/MyResearch/ListEdit" id="listForm" onsubmit='bagAddList(); return false;'>
					<div>
					{translate text="List"}:<br />
					<input type="text" id="listTitleBag" name="title" value="{$list->title|escape:"html"}" size="50"/><br />
					{translate text="Description"}:<br />
					<textarea name="desc" id="listDesc" rows="2" cols="40">{$list->desc|escape:"html"}</textarea><br />
					{translate text="Access"}:<br />
					{translate text="Public"} <input type="radio" name="public" id="bagListPublic" value="1" />
					{translate text="Private"} <input type="radio" name="public" id="bagListPrivate" value="0" checked="checked" /><br />
					
					</div>
				</form>
			</div>
			
			{* Save items to a list *}
			<div id="bag_choose_list" class="bag_box" style="display:none">
				{* Controls for adding the titles to an existing list *}
				<div id='existing_list_controls'>
					{translate text='Choose a List:'}<br />
					<select name="bookbag_list_select" id="bookbag_list_select">
						{foreach from=$userLists item="list"}
						<option value="{$list.id}">{$list.title|escape:"html"}</option>
						{foreachelse}
						<option value="">{translate text='My Favorites'}</option>
						{/foreach}
					</select>
					<div id='bag_tags'>
						Tags:<br /> <input type="text" id="save_tags_field" size="40"/><br />
						Tags will apply to all items being added.	Use commas to separate tags. If you would like to have a comma within a tag, enclose it within quotes.
					</div>
				</div>
			</div>
			
			<div id="bag_action_in_progress" class="bag_box" style="display:none">
				<span id="bag_action_in_progress_text">Processing....</span>
			</div>
			
			<div id="bag_errors" class="bag_box" style="display:none">
				Warning: <span id="bag_error_message"></span>
			</div> 
		</div> {* End of top panel*}
		
		<div id="bag_links">
			<div id="bag_actions_bag_items" class="bag_button_group"> 
				<div class="button logged-in-button" style="display: none;"><span class="silk star_gold">&nbsp;</span><a href="#" id="bag_add_to_my_list_button">Save To List</a></div>	
				<div class="button"><a href="#" id="bag_email_button" ><span class="silk email">&nbsp;</span>Email</a></div>		
				<div class="button" ><a href="#" id="bag_request_button" ><span class="silk book_next">&nbsp;</span>Place Hold</a></div>
				<div class="button"><a href="#" id="bag_print_button" ><span class="silk printer">&nbsp;</span>Print</a></div>
				<div class="button logged-out-button" id="login_bag"><span class="silk user_go">&nbsp;</span>Login to Save to List</div>
			</div>
			<div id="bag_actions_email_to_box" class="bag_button_group" style="display:none">
				<a href="#" class="button" id="bag_email_submit"><span class="silk email">&nbsp;</span>Send</a>
				<a href="#" class="button bag_hide_button"><span class="silk list">&nbsp;</span>Return To Cart</a>
			</div>
			<div id="bag_actions_bookcart_login" class="bag_button_group" style="display:none">
				<a href="#" class="button" id="bag_login_submit">Login</a>
				<a href="#" class="button bag_hide_button" id="bag_login_cancel"><span class="silk list">&nbsp;</span>Return To Cart</a>
			</div>
			<div id="bag_actions_bag_choose_list" class="bag_button_group" style="display:none">
				<a href="#" class="button" id="bag_save_to_list_submit"><span class="silk star_gold">&nbsp;</span>Save To List</a>
				<a href="#" class="button bag_hide_button"><span class="silk list">&nbsp;</span>Return To Cart</a>
				<br />
				<a href="#" class="button" id="new_list"><span class="silk star_gold">&nbsp;</span>Create a new List</a>
			</div>
			<div id="bag_actions_bag_action_in_progress" class="bag_button_group" style="display:none">
				<a href="#" class="button bag_hide_button"><span class="silk list">&nbsp;</span>Return To Cart</a>
				<a href="#" class="button bag_clear_button"><span class="silk bin_empty">&nbsp;</span>Clear Cart</a>
			</div>
			<div id="bag_actions_bag_errors" class="bag_button_group" style="display:none">
				<a href="#" class="button bag_hide_button"><span class="silk list">&nbsp;</span>Return To Cart</a>
				<a href="#" class="button bag_clear_button"><span class="silk bin_empty">&nbsp;</span>Clear Cart</a>
			</div>
			<div id="bag_actions_create_list" class="bag_button_group" style="display:none">
				<a href="#" class="button" id="bag_create_list_button"><span class="silk star_gold">&nbsp;</span>{translate text="Create List"}</a>
				<a href="#" class="button" id="choose_existing_list"><span class="silk star_gold">&nbsp;</span>Select Existing List</a>
				<a href="#" class="button bag_hide_button"><span class="silk list">&nbsp;</span>Return To Cart</a>
			</div>
	 </div>
			 
	</div>
</div>
{/strip}