<p>
1) Please select the device you are trying to read or listen to a book on.
<select name="device" id="device" onchange="loadEContentHelpTopic()">
	<option value="selectone">Select One...</option>
	<option value="pc">Windows PC</option>
	<option value="mac">Macintosh Computer</option>
	<option value="kindle">Kindle (Black and White)</option>
	<option value="kindle_fire">Kindle Fire</option>
	<option value="nook">Nook</option>
	<option value="android">Android Phone or Tablet</option>
	<option value="ios">iPad, iPhone, or iPod</option>
</select>
</p>
<p>
2) Please select the format you are trying to read or listen to.
<select name="format" id="format" onchange="loadEContentHelpTopic()">
	<option value="selectone">Select One...</option>
	<option value="ebook">EPUB eBook</option>
	<option value="kindle">Kindle eBook</option>
	<option value="mp3">MP3 Audiobook</option>
	<option value="wma">WMA Audio Book</option>
	<option value="eMusic">eMusic</option>
	<option value="eVideo">eVideo</option>
</select>
</p>
<div id="stepByStepInstructions" style="display:none">
	<h2>Instructions</h2>
	<div id="helpInstructions"></div>
</div>