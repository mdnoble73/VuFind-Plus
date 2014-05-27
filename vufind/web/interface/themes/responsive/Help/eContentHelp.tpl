<p>
<label for="device">1) Please select the device you are trying to read or listen to a book on.</label>
<select name="device" id="device" onchange="VuFind.loadEContentHelpTopic()" class="form-control">
	<option value="selectone">Select One...</option>
	<option value="pc" {if $defaultDevice == 'pc'}selected="selected"{/if}>Windows PC</option>
	<option value="mac" {if $defaultDevice == 'mac'}selected="selected"{/if}>Macintosh Computer</option>
	<option value="kindle" {if $defaultDevice == 'kindle'}selected="selected"{/if}>Kindle (Black and White)</option>
	<option value="kindle_fire" {if $defaultDevice == 'kindle_fire'}selected="selected"{/if}>Kindle Fire</option>
	<option value="nook" {if $defaultDevice == 'nook'}selected="selected"{/if}>Nook</option>
	<option value="android" {if $defaultDevice == 'android'}selected="selected"{/if}>Android Phone or Tablet</option>
	<option value="ios" {if $defaultDevice == 'ios'}selected="selected"{/if}>iPad, iPhone, or iPod</option>
	<option value="other" {if $defaultDevice == 'other'}selected="selected"{/if}>Other</option>
</select>
</p>
<p>
<label for="format">2) Please select the format you are trying to read or listen to.</label>
<select name="format" id="format" onchange="VuFind.loadEContentHelpTopic()" class="form-control">
	<option value="selectone">Select One...</option>
	<option value="ebook" {if $defaultFormat == 'ebook'}selected="selected"{/if}>EPUB eBook</option>
	<option value="kindle" {if $defaultFormat == 'kindle'}selected="selected"{/if}>Kindle eBook</option>
	{* <option value="springerlink" {if $defaultFormat == 'springerlink'}selected="selected"{/if}>SpringerLink eBook</option> *}
	<option value="ebsco" {if $defaultFormat == 'ebsco'}selected="selected"{/if}>EBSCO eBook</option>
	<option value="mp3" {if $defaultFormat == 'mp3'}selected="selected"{/if}>MP3 Audiobook</option>
	<option value="wma" {if $defaultFormat == 'wma'}selected="selected"{/if}>WMA Audio Book</option>
	<option value="eMusic" {if $defaultFormat == 'eMusic'}selected="selected"{/if}>eMusic</option>
	<option value="eVideo" {if $defaultFormat == 'eVideo'}selected="selected"{/if}>eVideo</option>
	<option value="other"> {if $defaultFormat == 'other'}selected="selected"{/if}Other</option>
</select>
</p>
<div id="stepByStepInstructions" style="display:none">
	<h2>Instructions</h2>
	<div id="helpInstructions"></div>
</div>
<script type="text/javascript">
	VuFind.loadEContentHelpTopic();
</script>