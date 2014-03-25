<div id="page-content" class="col-xs-12">
	{if $error}<p class="error">{$error}</p>{/if} 
	<form action="" method="post" id="offlineCircForm" class="form-horizontal">
		<div id="main-content" class="full-result-content">
			<h2>Offline Circulation</h2>

			{if $results}
				<div class="error">
					{$results}
				</div>
			{/if}

			<div id="sidebar" class="row">
				<div class="form-group col-sm-6">
					<div class="sidebarLabel"><label for="login">Login</label>:</div>
					<div class="sidebarValue"><input type="text" name="login" id="login" value="{$lastLogin}"/> </div>
				</div>
				<div class="form-group col-sm-6">
					<div class="sidebarLabel"><label for="password1">Password</label>:</div>
					<div class="sidebarValue"><input type="password" name="password1" id="password1" value="{$lastPassword1}"/></div>
				</div>
				{*
				<div class="sidebarLabel"><label for="initials">Initials</label>:</div>
				<div class="sidebarValue"><input type="text" name="initials" id="initials" value="{$lastInitials}"/> </div>
				<div class="sidebarLabel"><label for="password2">Password</label>:</div>
				<div class="sidebarValue"><input type="password" name="password2" id="password2" value="{$lastPassword2}"/></div>
				*}
				<div class="sidebarLabel"><button name="submit" onclick="return $('$offlineCircForm').submit()">Submit</button></div>
			</div>
			<fieldset>
				<legend>Checkout titles</legend>
				<div class="form-group">
					<label for="patronBarcode">Patron Barcode</label>
					<input type="text" name="patronBarcode" id="patronBarcode"/><br/>
				</div>
				<label for="barcodesToCheckOut">Enter barcodes to check out (one per line).</label>
				<textarea rows="10" cols="20" name="barcodesToCheckOut" id="barcodesToCheckOut"></textarea>
			</fieldset>
			<div class="sidebarLabel"><button name="submit" onclick="return $('$offlineCircForm').submit()">Submit</button></div>
		</div>
	</form>
</div>

{literal}
<script type="text/javascript">
	function checkCptKey(e)
	{
		var shouldBubble = true;
		switch (e.keyCode)
		{
			// user pressed the Tab
			case 9:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				//shouldBubble = false;
				break;
			};
			// user pressed the Enter
			case 13:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				shouldBubble = false;
				break;
			};
			// user pressed the ESC
			case 27:
			{
				//$(".cptIcdProcedureSelect").toggleClass("cptIcdProcedureSelectVisible");
				break;
			};
		};
		/* this propagates the jQuery event if true */
		return shouldBubble;
	};
	/* user pressed special keys while in Selector */
	$("#patronBarcode").keydown(function(e)
	{
		return checkCptKey(e, $(this));
	});
</script>
{/literal}