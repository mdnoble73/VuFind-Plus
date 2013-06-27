<div id='suggestions' class="row-fluid">
	{if $message}
		<div class='error'>{$message}</div>
	{/if}
	<form id='suggestionsForm' method="post" action="/Help/Suggestion" class="form-horizontal">
		<fieldset>
			{if $lightbox == false}
			<legend>Make a Suggestion</legend>
			{/if}
			<p>
				We love to hear from you.
				If you have a suggestion for how to improve our catalog or if you have a problem with the catalog please let us know.
				If you provide your name and e-mail, we will only use it to contact you if we have questions about your suggestion.
			</p>
			<div>
				<div class="control-group">
					<label for='name' class='control-label'>Your Name:</label>
					<div class="controls">
						<input type='text' name='name' id="name" value='{$name|escape:htmlall}' {if $lightbox == false}class="input-xxlarge"{/if} placeholder="(optional)"/>
					</div>
				</div>
				<div class="control-group">
					<label for='email' class='control-label'>E-mail:</label>
					<div class="controls">
						<input type='text' name='email' id="email" value='{$email|escape:htmlall}' {if $lightbox == false}class="input-xxlarge"{/if} placeholder="(optional)"/>
					</div>
				</div>
				<div class="control-group">
					<label for='suggestion' class='control-label'>Suggestion:</label>
					<div class="controls">
						<textarea rows="6" cols="80" name='suggestion' id="suggestion" {if $lightbox == false}class="input-xxlarge"{/if}>{$suggestion|escape:htmlall}</textarea>
					</div>
				</div>
				<div class="control-group">
					<div class="controls">
						{$captcha}
					</div>
				</div>
				<div class="control-group">
					<div class="controls">
						<input type='submit' name='submit' value='Save Suggestion' class="btn btn-primary"/>
					</div>
				</div>
			</div>
		</fieldset>
	</form>
</div>