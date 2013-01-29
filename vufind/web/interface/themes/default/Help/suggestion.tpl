<div id='suggestions'>
	{if $message}
		<div class='error'>{$message}</div>
	{/if}
	<div class='suggestionDescription'>
		<p>
		We love to hear from you.  
		If you have a suggestion for how to improve our catalog or if you have a problem with the catalog please let us know. 
		If you provide your name and e-mail, we will only use it to contact you if we have questions about your suggestion.
		</p>
	</div>
	<form id='suggestionsForm' method="post" action="/Help/Suggestion">
		<div>
			<label for='name' class='suggestionLabel'>Your Name:</label><input type='text' name='name' id="name" value='{$name|escape:htmlall}'/> (Optional)<br />
			<label for='email' class='suggestionLabel'>E-mail:</label><input type='text' name='email' id="email" value='{$email|escape:htmlall}'/> (Optional)<br />
			<label for='suggestion' class='suggestionLabel'>Suggestion:</label><textarea rows="6" cols="80" name='suggestion' id="suggestion">{$suggestion|escape:htmlall}</textarea><br />
			{$captcha}
			<input type='submit' name='submit' value='Save Suggestion'/>
		</div>
	</form>
</div>