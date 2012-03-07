<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js" ></script>
<div id="page-content" class="content">
	<div id="main-content">
		<h2>{translate text='Materials Request'}</h2>
		<div id="materialsRequest">
			<div class="materialsRequestExplanation">
				If you cannot find a title in our catalog, you can request the title via this form.
				Please enter as much information as possible so we can find the exact title you are looking for. 
				For example, if you are looking for a specific season of a TV show, please include that information.
			</div>
			<form id="materialsRequestForm" action="{$path}/MaterialsRequest/Submit" method="post">
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
						Please enter your contact information so we can concact you if we have questions about your request.
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
				<fieldset>
					<legend>Basic Information</legend>
					<div>
						<label for="format">Format:</label>
						<select name="format" class="required" id="format" onchange="setFieldVisibility();">
							<option value="book">Book</option>
							<option value="dvd">DVD</option>
							<option value="cdAudio">CD Audiobook</option>
							<option value="cdMusic">Music CD</option>
							<option value="ebook">e-Book</option>
							<option value="eaudio">e-Audio</option>
							<option value="playaway">Playaway</option>
							<option value="article">Magazine/Journal Article</option>
							<option value="cassette">Cassette</option>
							<option value="vhs">VHS</option>
						</select>
					</div>
					<div>
						<label for="title">Title:</label>
						<input name="title" id="title" size="80" maxlength="255" class="required"/>
					</div>
					<div class="formatSpecificField dvdField vhsField">
						<label for="season">Season:</label>
						<input name="season" id="season" size="80" maxlength="80"/>
					</div>
					<div>
						<label for="author" id="authorFieldLabel">Author:</label>
						<input name="author" id="author" size="80" maxlength="255" class="required"/>
					</div>
					<div class="formatSpecificField articleField">
						<label for="magazineTitle">Magazine/Journal Title:</label>
						<input name="magazineTitle" id="magazineTitle" size="80" maxlength="255" class="required"/>
					</div>
					<div class="formatSpecificField articleField">
						<label for="magazineDate">Date:</label>
						<input name="magazineDate" id="magazineDate" size="10" maxlength="20" class="required"/>
					</div>
					<div class="formatSpecificField articleField">
						<label for="magazineVolume">Volume:</label>
						<input name="magazineVolume" id="magazineVolume" size="10" maxlength="20" class="required"/>
					</div>
					<div class="formatSpecificField articleField">
						<label for="magazinePageNumbers">Page Numbers:</label>
						<input name="magazinePageNumbers" id="magazinePageNumbers" size="10" maxlength="20" class="required"/>
					</div>
					<div class="formatSpecificField ebookField">
						<label for="ebookFormat">E-book format:</label>
						<select name="ebookFormat">
							<option value="epub">EPUB</option>
							<option value="kindle">Kindle</option>
							<option value="pdf">PDF</option>
							<option value="other">Other - please specify in comments</option>
						</select>
					</div>
					<div class="formatSpecificField eaudioField">
						<label for="eudioFormat">E-audio format:</label>
						<select name="eudioFormat">
							<option value="wma">WMA</option>
							<option value="mp3">MP3</option>
							<option value="other">Other - please specify in comments</option>
						</select>
					</div>
				</fieldset>
				<fieldset>
					<legend>Identifiers</legend>
					<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eAudioField">
						<input type="button" id="suggestIdentifiers" value="Lookup ISBN &amp; OCLC Number" onclick="return getWorldCatIdentifiers();"/>
					</div>
					<div id="suggestedIdentifiers" style="display:none"></div>
					<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
						<label for="isbn_upc">ISBN:</label>
						<input name="isbn_upc" id="isbn_upc" size="15" maxlength="15"/>
					</div>
					<div class="formatSpecificField dvdField cdMusicField vhsField" >
						<label for="upc">UPC:</label>
						<input name="upc" id="upc" size="15" maxlength="15"/>
					</div>
					<div class="formatSpecificField articleField">
						<label for="issn">ISSN:</label>
						<input name="issn" id="issn" size="8" maxlength="8"/>
					</div>
					<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
						<label for="oclcNumber">OCLC Number</label>
						<input name="oclcNumber" id="oclcNumber" size="15" maxlength="30"/>
					</div>
				</fieldset>
				<fieldset>
					<legend>Supplemental Details</legend>
					<div class="formatSpecificField bookField cdAudioField ebookField eaudioField playawayField cassetteField">
						<label for="ageLevel">Age Level:</label>
						<select name="ageLevel">
							<option value="adult">Adult</option>
							<option value="teen">Teen</option>
							<option value="children">Children</option>
							<option value="unknown">Don't Know</option>
						</select>
					</div>
					<div class="formatSpecificField cdAudioField eaudioField playawayField cassetteField">
						<input type="radio" name="abridged" value="unabridged" id="unabridged" checked="checked"/><label for="unabridged">Unabridged</label> <input type="radio" name="abridged" value="abridged" id="abridged"/><label for="abridged">Abridged</label>  <input type="radio" name="abridged" value="na" id="na"/><label for="na">Not Applicable</label>
					</div>
					
					<div class="formatSpecificField bookField ebookField">
						<label for="bookType">Type:</label>
						<select name="bookType">
							<option value="fiction">Fiction</option>
							<option value="nonfiction">Non-Fiction</option>
							<option value="graphicNovel">Graphic Novel</option>
							<option value="unknown">Don't Know</option>
						</select>
					</div>
					
					
					
					<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
						<label for="publisher">Publisher:</label>
						<input name="publisher" id="publisher" size="80" maxlength="255"/>
					</div>
					<div class="formatSpecificField bookField dvdField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField">
						<label for="publicationYear">Publication Year:</label>
						<input name="publicationYear" id="publicationYear" size="4" maxlength="4"/>
					</div>
				</fieldset>
				<fieldset>
					<legend>Holds</legend>
					<div>
						Place a hold for me when the item is available: 
						<input type="radio" name="placeHoldYes" value="0" id="placeHoldYes" checked="checked"/><label for="placeHoldYes">Yes</label> <input type="radio" name="placeHold" value="0" id="placeHoldNo"/><label for="placeHoldNo">No</label>
					</div>
					<div>
						<label for="pickupLocation">Pickup Location: </label>
						<select name="pickupLocation" id="pickupLocation">
							{foreach from=$pickupLocations item=location}
								<option value="{$location->code}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
							{/foreach}
						</select>
					</div>
					<div>
						<label for="bookmobileStop">Bookmobile Stop: </label>
						<input name="bookmobileStop" id="bookmobileStop" size="50" maxlength="50"/>
					</div>
				</fieldset>
				
				<div>
					<label for="about">How/where did you hear about this title:</label>
					<textarea name="about" id="about" rows="3" cols="80" class="required"></textarea>
				</div>
				<div>
					<label for="comments">Comments:</label>
					<textarea name="comments" id="comments" rows="3" cols="80"></textarea>
				</div>
				
				<div id="copyright">
					WARNING CONCERNING COPYRIGHT RESTRICTIONS The copyright law of the United States (Title 17, United States Code) governs the making of photocopies or other reproductions of copyrighted material. Under certain conditions specified in the law, libraries and archives are authorized to furnish a photocopy or other reproduction. One of these specified conditions is that the photocopy or reproduction is not to be used for any purpose other than private study, scholarship, or research. If a user makes a request for, or later uses, a photocopy or reproduction for purposes in excess of fair use, that user may be liable for copyright infringement. This institution reserves the right to refuse to accept a copying order if, in its judgment, fulfillment of the order would involve violation of copyright law.
				</div>
				<div>
					<input type="submit" value="Submit Materials Request" />
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	setFieldVisibility();
	$("#materialsRequestForm").validate();
</script>