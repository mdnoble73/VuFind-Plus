<script type="text/javascript" src="{$path}/js/validate/jquery.validate.min.js" ></script>
Need help downloading a title or using the title on your device?  Please fill out this support form. 
<form id="eContentSupport" action="{$path}/Help/eContentSupport" method="post">
	{if !$user}
		<div class='propertyInput'>
			<label for='libraryCardNumber' class='objectLabelInline'>Library Card Number: *</label><input type="text" name="libraryCardNumber" id="libraryCardNumber" class="required" maxlength="20" size="20"/>
		</div>
	{/if}
	<div class='propertyInput'>
		<label for='name' class='objectLabelInline'>Name:</label><input type="text" name="name" id="name" class="required" maxlength="120" size="60" value="{$name}"/>
	</div>
	<div class='propertyInput'>
		<label for='email' class='objectLabelInline'>E-mail:</label><input type="text" name="email" id="email" class="required email" maxlength="120" size="60" value="{$email}"/>
	</div>
	<div class='propertyInput'>
		<label for='bookAuthor' class='objectLabelInline'>Book Title/Author:</label><input type="text" name="bookAuthor" id="bookAuthor" maxlength="120" size="60"/>
	</div>
	<div class='propertyInput'>
		<label for='device' class='objectLabelInline'>Device:</label><input type="text" name="device" id="device" maxlength="120" size="60"/>
	</div>
	<div class='propertyInput'>
		<label for='format' class='objectLabelInline'>Format:</label>
		<select id="format" name="format">
			<option value="na">-Select a Format-</option>
			<option value="ePub">Adobe E-pub eBook</option>
			<option value="kindle">Kindle eBook</option>
			<option value="mp3">MP3 Audio Book</option>
			<option value="wma">WMA Audio Book/Music</option>
			<option value="wmv">WMV Video File</option>
			<option value="Unknown">N/A or Unknown</option>
		</select>
	</div>
	<div class='propertyInput'>
		<label for='operatingSystem' class='objectLabelInline'>Operating System:</label>
		<select name="operatingSystem" id="operatingSystem">
			<option value="">-Select an Operating System-</option>
			<option value="XP">Windows XP</option>
			<option value="Vista">Windows Vista</option>
			<option value="Win-7">Windows 7</option>
			<option value="Mac">Max OS X 10.?</option>
			<option value="kindle">Kindle</option>
			<option value="Linux">Linux/Unix</option>
			<option value="Android">Android</option>
			<option value="IOS">iPhone/iPad/iPod</option>
			<option value="other">Other - Please specify Below</option>
		</select>
	</div>
	<div class='propertyInput'>
		<label for='problem' class='objectLabel'>Please describe your issue:</label><br/>
		<textarea rows="10" cols="40" name="problem" id="problem"></textarea>
	</div>
	<div class='propertyInput'>
		<input type="submit" name="submit" value="Submit"/>
	</div>
</form>
{literal}
<script type="text/javascript">
$(document).ready(function(){
	$("#eContentSupport").validate();
	$("#eContentSupport").ajaxForm({
    target: '#popupboxContent'
  });
});
</script>
{/literal}