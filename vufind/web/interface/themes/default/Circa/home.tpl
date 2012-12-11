<div id="page-content" class="content">
	{if $error}<p class="error">{$error}</p>{/if} 
	<form action="" method="post">
		<div id="main-content" class="full-result-content">
			<h1>Circa Inventory</h1>
			<label for="initials">Login</label>: <input type="text" name="login" id="login"/> 
			<label for="password2">Password</label>: <input type="text" name="password1" id="password1"/><br/>
			<label for="initials">Initials</label>: <input type="text" name="initials" id="initials"/> 
			<label for="password2">Password</label>: <input type="text" name="password2" id="password2"/><br/>
			<label for="barcodes">Enter barcodes one per line.</label>
			<textarea rows="20" cols="20" name="barcodes" id="barcodes"></textarea>
			<h2>Statuses to change to on-shelf</h2>
			<input type="submit" value="Submit Inventory"/>
		</div>
	</form>
</div>