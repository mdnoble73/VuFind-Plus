{if $materialsRequest}
<input type="hidden" name="id" value="{$materialsRequest->id}" />
{else}
<fieldset>
	<legend>User Information</legend>
	{if !$user}
		<div>
			<label for="username">{translate text='Username'}: </label>
			<input type="text" name="username" id="username" value="{$username|escape}" size="15" class="required"/>
		</div>
		<div>
			<label for="password">{translate text='Password'}: </label>
			<input type="password" name="password" id="password" size="15" class="required"/>
		</div>
	{/if}
	<div id="materialRequestContactInfo">
		Please enter your contact information so we can contact you if we have questions about your request.
		<div>
			<label for="phone">{translate text='Phone'}: </label>
			<input type="text" name="phone" id="phone" size="15" class="tel" value="{$defaultPhone}"/>
		</div>
		<div>
			<label for="email">{translate text='Email'}: </label>
			<input type="text" name="email" id="email" size="80" maxlength="80" class="email" value="{$defaultEmail}"/>
		</div>
	</div>
</fieldset>
{/if}
<fieldset>
	<legend>Basic Information</legend>
	<div>
		<label for="format">Format:</label>
		<select name="format" class="required" id="format" onchange="setFieldVisibility();">
			<option value="book"{if $materialsRequest->format=='book'}selected='selected'{/if}>Book</option>
			<option value="dvd"{if $materialsRequest->format=='dvd'}selected='selected'{/if}>DVD</option>
			<option value="cdAudio"{if $materialsRequest->format=='cdAudio'}selected='selected'{/if}>CD Audiobook</option>
			<option value="cdMusic"{if $materialsRequest->format=='cdMusic'}selected='selected'{/if}>Music CD</option>
			<option value="ebook"{if $materialsRequest->format=='ebook'}selected='selected'{/if}>e-Book</option>
			<option value="eaudio"{if $materialsRequest->format=='eaudio'}selected='selected'{/if}>e-Audio</option>
			<option value="playaway"{if $materialsRequest->format=='playaway'}selected='selected'{/if}>Playaway</option>
			<option value="article"{if $materialsRequest->format=='article'}selected='selected'{/if}>Magazine/Journal Article</option>
			<option value="cassette"{if $materialsRequest->format=='cassette'}selected='selected'{/if}>Cassette</option>
			<option value="vhs"{if $materialsRequest->format=='vhs'}selected='selected'{/if}>VHS</option>
		</select>
	</div>
	<div>
		<label for="title">Title:</label>
		<input name="title" id="title" size="80" maxlength="255" class="required" value="{$materialsRequest->title}"/>
	</div>
	<div class="formatSpecificField dvdField vhsField">
		<label for="season">Season:</label>
		<input name="season" id="season" size="80" maxlength="80" value="{$materialsRequest->season}"/>
	</div>
	<div>
		<label for="author" id="authorFieldLabel">Author:</label>
		<input name="author" id="author" size="80" maxlength="255" class="required" value="{$materialsRequest->author}"/>
	</div>
	<div class="formatSpecificField articleField">
		<label for="magazineTitle">Magazine/Journal Title:</label>
		<input name="magazineTitle" id="magazineTitle" size="80" maxlength="255" class="required" value="{$materialsRequest->magazineTitle}"/>
	</div>
	<div class="formatSpecificField articleField">
		<label for="magazineDate">Date:</label>
		<input name="magazineDate" id="magazineDate" size="10" maxlength="20" class="required" value="{$materialsRequest->magazineDate}"/>
	</div>
	<div class="formatSpecificField articleField">
		<label for="magazineVolume">Volume:</label>
		<input name="magazineVolume" id="magazineVolume" size="10" maxlength="20" class="required" value="{$materialsRequest->magazineVolume}"/>
	</div>
	<div class="formatSpecificField articleField">
		<label for="magazinePageNumbers">Page Numbers:</label>
		<input name="magazinePageNumbers" id="magazinePageNumbers" size="10" maxlength="20" class="required" value="{$materialsRequest->magazinePageNumbers}"/>
	</div>
	<div class="formatSpecificField ebookField">
		<label for="ebookFormat">E-book format:</label>
		<select name="ebookFormat">
			<option value="epub"{if $materialsRequest->ebookFormat=='epub'}selected='selected'{/if}>EPUB</option>
			<option value="kindle"{if $materialsRequest->ebookFormat=='kindle'}selected='selected'{/if}>Kindle</option>
			<option value="pdf"{if $materialsRequest->ebookFormat=='pdf'}selected='selected'{/if}>PDF</option>
			<option value="other"{if $materialsRequest->ebookFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
		</select>
	</div>
	<div class="formatSpecificField eaudioField">
		<label for="eudioFormat">E-audio format:</label>
		<select name="eudioFormat">
			<option value="wma"{if $materialsRequest->eudioFormat=='wma'}selected='selected'{/if}>WMA</option>
			<option value="mp3"{if $materialsRequest->eudioFormat=='mp3'}selected='selected'{/if}>MP3</option>
			<option value="other"{if $materialsRequest->eudioFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
		</select>
	</div>
</fieldset>
<fieldset>
	<legend>Identifiers</legend>
	{if $useWorldCat}
	<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eAudioField playawayField cassetteField vhsField">
		<input type="button" id="suggestIdentifiers" value="Lookup ISBN &amp; OCLC Number" onclick="return getWorldCatIdentifiers();"/>
	</div>
	{/if}
	<div id="suggestedIdentifiers" style="display:none"></div>
	<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
		<label for="isbn">ISBN:</label>
		<input name="isbn" id="isbn" size="15" maxlength="15" value="{$materialsRequest->isbn}"/>
	</div>
	<div class="formatSpecificField dvdField cdMusicField vhsField" >
		<label for="upc">UPC:</label>
		<input name="upc" id="upc" size="15" maxlength="15" value="{$materialsRequest->upc}"/>
	</div>
	<div class="formatSpecificField articleField">
		<label for="issn">ISSN:</label>
		<input name="issn" id="issn" size="8" maxlength="8" value="{$materialsRequest->issn}"/>
	</div>
	<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
		<label for="oclcNumber">OCLC Number</label>
		<input name="oclcNumber" id="oclcNumber" size="15" maxlength="30" value="{$materialsRequest->oclcNumber}"/>
	</div>
</fieldset>
<fieldset id="supplementalDetails">
	<legend>Supplemental Details</legend>
	<div class="formatSpecificField bookField cdAudioField ebookField eaudioField playawayField cassetteField">
		<label for="ageLevel">Age Level:</label>
		<select name="ageLevel">
			<option value="adult"{if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
			<option value="teen"{if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
			<option value="children"{if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
			<option value="unknown"{if $materialsRequest->ageLevel=='unknown'}selected='selected'{/if}>Don't Know</option>
		</select>
	</div>
	<div class="formatSpecificField cdAudioField eaudioField playawayField cassetteField">
		<input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}/><label for="unabridged">Unabridged</label> 
		<input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}/><label for="abridged">Abridged</label>  
		<input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}/><label for="na">Not Applicable</label>
	</div>
	
	<div class="formatSpecificField bookField ebookField">
		<label for="bookType">Type:</label>
		<select name="bookType">
			<option value="fiction"{if $materialsRequest->bookType=='fiction'}selected='selected'{/if}>Fiction</option>
			<option value="nonfiction"{if $materialsRequest->bookType=='nonfiction'}selected='selected'{/if}>Non-Fiction</option>
			<option value="graphicNovel"{if $materialsRequest->bookType=='graphicNovel'}selected='selected'{/if}>Graphic Novel</option>
			<option value="unknown"{if $materialsRequest->bookType=='unknown'}selected='selected'{/if}>Don't Know</option>
		</select>
	</div>
	
	<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
		<label for="publisher">Publisher:</label>
		<input name="publisher" id="publisher" size="80" maxlength="255" value="{$materialsRequest->publisher}"/>
	</div>
	<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
		<label for="publicationYear">Publication Year:</label>
		<input name="publicationYear" id="publicationYear" size="4" maxlength="4" value="{$materialsRequest->publicationYear}"/>
	</div>
</fieldset>
{if !$materialsRequest}
<fieldset>
	<legend>Holds</legend>
	<div>
		Place a hold for me when the item is available: 
		<input type="radio" name="placeHoldWhenAvailable" value="1" id="placeHoldYes" onclick="updateHoldOptions();"/><label for="placeHoldYes">Yes</label> 
		<input type="radio" name="placeHoldWhenAvailable" value="0" id="placeHoldNo" checked="checked" onclick="updateHoldOptions();"/><label for="placeHoldNo">No</label>
	</div>
	<div id="pickupLocationField" style="display:none;">
		<label for="pickupLocation">Pickup Location: </label>
		<select name="holdPickupLocation" id="pickupLocation" onchange="updateHoldOptions();">
			{foreach from=$pickupLocations item=location}
				<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
			{/foreach}
		</select>
	</div>
	<div id="bookmobileStopField" style="display:none;">
		<label for="bookmobileStop">Bookmobile Stop: </label>
		<input name="bookmobileStop" id="bookmobileStop" size="50" maxlength="50"/>
	</div>
</fieldset>
{/if}
{if !$materialsRequest}
<div>
	<label for="about">How/where did you hear about this title:</label>
	<textarea name="about" id="about" rows="3" cols="80" class="required">{$materialsRequest->about}</textarea>
</div>
{/if}
<div>
	<label for="comments">Comments:</label>
	<textarea name="comments" id="comments" rows="3" cols="80">{$materialsRequest->comments}</textarea>
</div>