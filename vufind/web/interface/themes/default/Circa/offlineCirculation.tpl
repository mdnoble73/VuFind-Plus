<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if} 
	<form action="" method="post">
		
		<div id="sidebar">
			<div class="sidebarLabel"><label for="login">Login</label>:</div>
			<div class="sidebarValue"><input type="text" name="login" id="login" value="{$lastLogin}"/> </div>
			<div class="sidebarLabel"><label for="password1">Password</label>:</div>
			<div class="sidebarValue"><input type="password" name="password1" id="password1" value="{$lastPassword1}"/></div>
			<div class="sidebarLabel"><label for="initials">Initials</label>:</div>
			<div class="sidebarValue"><input type="text" name="initials" id="initials" value="{$lastInitials}"/> </div>
			<div class="sidebarLabel"><label for="password2">Password</label>:</div>
			<div class="sidebarValue"><input type="password" name="password2" id="password2" value="{$lastPassword2}"/></div>
			<div class="sidebarLabel"><input type="submit" name="submit" value="Submit"/></div>
		</div>
		<div id="main-content" class="full-result-content">
			<h1>Offline Circulation</h1>
			{*
			<fieldset>
				<legend>Check-in titles</legend>
				<label for="barcodesToCheckIn">Enter barcodes to check in (one per line).</label>
				<textarea rows="10" cols="20" name="barcodesToCheckIn" id="barcodesToCheckIn"></textarea>
			</fieldset>
			*}
			<fieldset>
				<legend>Checkout titles</legend>
				<label for="patronBarcode">Patron Barcode</label>
				<input type="text" name="patronBarcode" id="patronBarcode"/><br/>
				<label for="barcodesToCheckOut">Enter barcodes to check out (one per line).</label>
				<textarea rows="10" cols="20" name="barcodesToCheckOut" id="barcodesToCheckOut"></textarea>
			</fieldset>
		</div>
	</form>
</div>