<div class="materialsRequestLoggedInFields" {if !$user}style="display:none"{/if}>
  <fieldset>
    <legend>Basic Information</legend>
    <div>
      <label for="format">Format <span class="requiredIndicator">*</span>:</label>
      <select name="format" class="required" id="format" onchange="setFieldVisibility();">
      {foreach from=$availableFormats item=label key=formatKey}
        <option value="{$formatKey}"{if $materialsRequest->format==$formatKey}selected='selected'{/if}>{$label}</option>
      {/foreach}
      </select>
    </div>
    <div class="formatSpecificField articleField">
      <label for="magazineTitle">Magazine/Journal Title <span class="requiredIndicator">*</span>:</label>
      <input name="magazineTitle" id="magazineTitle" maxlength="255" class="required" value="{$materialsRequest->magazineTitle}"/>
    </div>
    <div>
      <label for="title" id="titleLabel">Title <span class="requiredIndicator">*</span>:</label>
      <input name="title" id="title" maxlength="255" class="required" value="{$materialsRequest->title}"/>
    </div>
    <div class="formatSpecificField dvdField blurayField vhsField">
      <label for="season">Season:</label>
      <input name="season" id="season" maxlength="80" value="{$materialsRequest->season}"/>
    </div>
    <div>
      <label for="author" id="authorFieldLabel">Author <span class="requiredIndicator">*</span>:</label>
      <input name="author" id="author" maxlength="255" class="required" value="{$materialsRequest->author}"/>
    </div>
    <div class="formatSpecificField articleField">
      <label for="magazineDate">Date <span class="requiredIndicator">*</span>:</label>
      <input name="magazineDate" id="magazineDate" size="10" maxlength="20" class="required" value="{$materialsRequest->magazineDate}"/>
    </div>
    <div class="formatSpecificField articleField">
      <label for="magazineVolume">Volume <span class="requiredIndicator">*</span>:</label>
      <input name="magazineVolume" id="magazineVolume" size="10" maxlength="20" class="required" value="{$materialsRequest->magazineVolume}"/>
    </div>
    <div class="formatSpecificField articleField">
      <label for="magazineNumber">Number <span class="requiredIndicator">*</span>:</label>
      <input name="magazineNumber" id="magazineNumber" size="10" maxlength="20" class="required" value="{$materialsRequest->magazineNumber}"/>
    </div>
    <div class="formatSpecificField articleField">
      <label for="magazinePageNumbers">Page Numbers <span class="requiredIndicator">*</span>:</label>
      <input name="magazinePageNumbers" id="magazinePageNumbers" size="10" maxlength="20" class="required" value="{$materialsRequest->magazinePageNumbers}"/>
    </div>
    {if $showEbookFormatField}
    <div class="formatSpecificField ebookField">
      <label for="ebookFormat">E-book format:</label>
      <select name="ebookFormat" id="ebookFormat">
        <option value="epub"{if $materialsRequest->ebookFormat=='epub'}selected='selected'{/if}>EPUB</option>
        <option value="kindle"{if $materialsRequest->ebookFormat=='kindle'}selected='selected'{/if}>Kindle</option>
        <option value="pdf"{if $materialsRequest->ebookFormat=='pdf'}selected='selected'{/if}>PDF</option>
        <option value="other"{if $materialsRequest->ebookFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
      </select>
    </div>
    {/if}
    {if $showEaudioFormatField}
    <div class="formatSpecificField eaudioField">
      <label for="eaudioFormat">E-audio format:</label>
      <select name="eaudioFormat" id="eaudioFormat">
        <option value="wma"{if $materialsRequest->eudioFormat=='wma'}selected='selected'{/if}>WMA</option>
        <option value="mp3"{if $materialsRequest->eudioFormat=='mp3'}selected='selected'{/if}>MP3</option>
        <option value="other"{if $materialsRequest->eudioFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
      </select>
    </div>
    {/if}
    {if $useWorldCat}
    <div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eAudioField playawayField cassetteField vhsField">
      <input type="button" id="suggestIdentifiers" value="Lookup Details" onclick="return getWorldCatIdentifiersAnythink();"/>
    </div>
    {/if}
    <div id="suggestedIdentifiers" style="display:none"></div>
    {if !$materialsRequest || $new}
      {if $showPlaceHoldField || $showIllField}
      <fieldset>
        <legend>Access</legend>
        {if $showPlaceHoldField}
        <div>
          Place a hold for me when the item is available:
          <input type="radio" name="placeHoldWhenAvailable" value="1" id="placeHoldYes" checked="checked" onclick="updateHoldOptions();"/><label for="placeHoldYes">Yes</label>
          <input type="radio" name="placeHoldWhenAvailable" value="0" id="placeHoldNo" onclick="updateHoldOptions();"/><label for="placeHoldNo">No</label>
        </div>
        <div id="pickupLocationField">
          <label for="pickupLocation">Pickup Location: </label>
          <select name="holdPickupLocation" id="pickupLocation" onchange="updateHoldOptions();">
            {foreach from=$pickupLocations item=location}
              <option value="{$location->locationId}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
            {/foreach}
          </select>
        </div>
        <div id="bookmobileStopField" style="display:none;">
          <label for="bookmobileStop">Bookmobile Stop: </label>
          <input name="bookmobileStop" id="bookmobileStop" size="50" maxlength="50"/>
        </div>
        {/if}
        {if $showIllField}
        <div>
          Do you want us to borrow from another library if not purchased?:
          <input type="radio" name="illItem" value="1" id="illItemYes" /><label for="illItemYes">Yes</label>
          <input type="radio" name="illItem" value="0" id="illItemNo" checked="checked" /><label for="illItemNo">No</label>
        </div>
        {/if}
      </fieldset>
      {/if}
    {/if}
  </fieldset>
  <fieldset class="anythink-collapsible">
    <legend>Tell us more</legend>
    {* The following is set with JS, so we should probably leave for now.*}
    <!-- <fieldset> -->
      <!-- <legend>Identifiers</legend> -->
      <!-- <div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField"> -->
        <!-- <label for="isbn">ISBN:</label> -->
        <input type="hidden" name="isbn" id="isbn" value="{$materialsRequest->isbn}"/>
      <!-- </div> -->
      <!-- <div class="formatSpecificField dvdField blurayField cdMusicField vhsField otherField" > -->
        <!-- <label for="upc">UPC:</label> -->
        <input type="hidden" name="upc" id="upc" value="{$materialsRequest->upc}"/>
      <!-- </div> -->
      <!-- <div class="formatSpecificField articleField"> -->
        <!-- <label for="issn">ISSN:</label> -->
        <input type="hidden" name="issn" id="issn" value="{$materialsRequest->issn}"/>
      <!-- </div> -->
      <!-- <div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField"> -->
        <!-- <label for="oclcNumber">OCLC Number</label> -->
        <input type="hidden" name="oclcNumber" id="oclcNumber" value="{$materialsRequest->oclcNumber}"/>
      <!-- </div> -->
    <!-- </fieldset> -->
    <fieldset id="supplementalDetails">
      <legend>Supplemental Details</legend>
      {if $showAgeField}
      <div class="formatSpecificField bookField largePrintField cdAudioField ebookField eaudioField playawayField cassetteField">
        <label for="ageLevel">Age Level:</label>
        <select name="ageLevel">
          <option value="adult"{if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
          <option value="teen"{if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
          <option value="children"{if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
          <option value="unknown"{if $materialsRequest->ageLevel=='unknown'}selected='selected'{/if}>Don't Know</option>
        </select>
      </div>
      {/if}
      <div class="formatSpecificField cdAudioField eaudioField playawayField cassetteField">
        <input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}/><label for="unabridged">Unabridged</label>
        <input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}/><label for="abridged">Abridged</label>
        <input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}/><label for="na">Not Applicable</label>
      </div>
      {if $showBookTypeField}
      <div class="formatSpecificField bookField largePrintField ebookField">
        <label for="bookType">Type:</label>
        <select name="bookType">
          <option value="fiction"{if $materialsRequest->bookType=='fiction'}selected='selected'{/if}>Fiction</option>
          <option value="nonfiction"{if $materialsRequest->bookType=='nonfiction'}selected='selected'{/if}>Non-Fiction</option>
          <option value="graphicNovel"{if $materialsRequest->bookType=='graphicNovel'}selected='selected'{/if}>Graphic Novel</option>
          <option value="unknown"{if $materialsRequest->bookType=='unknown'}selected='selected'{/if}>Don't Know</option>
        </select>
      </div>
      {/if}

      <div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField">
        <label for="publisher">Publisher:</label>
        <input name="publisher" id="publisher" maxlength="255" value="{$materialsRequest->publisher}"/>
      </div>
      <div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField">
        <label for="publicationYear">Publication Year:</label>
        <input name="publicationYear" id="publicationYear" size="4" maxlength="4" value="{$materialsRequest->publicationYear}"/>
      </div>
    </fieldset>

    {if !$materialsRequest || $new}
    <div>
      <label for="about">How / where did you hear about this title{if $requireAboutField} <span class="requiredIndicator">*</span>{/if}:</label>
      <textarea name="about" id="about" rows="3" cols="80" {if $requireAboutField}class="required"{/if}>{$materialsRequest->about}</textarea>
    </div>
    {/if}
    <div>
      <label for="comments">Comments:</label>
      <textarea name="comments" id="comments" rows="3" cols="80">{$materialsRequest->comments}</textarea>
    </div>
  </fieldset>
</div>
{if $materialsRequest && !$new}
<input type="hidden" name="id" value="{$materialsRequest->id}" />
{else}

  {if !$user}
    <div id="materialsRequestLogin">
      <fieldset>
      <legend>{translate text="Login to your account"}</legend>
        <div class="form-item">
          <label for="username">{translate text='Username'} <span class="requiredIndicator">*</span>: </label>
          <input type="text" name="username" id="username" value="{$username|escape}" size="15" class="required"/>
        </div>
        <div class="form-item">
          <label for="password">{translate text='Password'} <span class="requiredIndicator">*</span>: </label>
          <input type="password" name="password" id="password" size="15" class="required"/>
        </div>
        <div>
          <input type="submit" name="login" value="Login" onclick="return materialsRequestLogin();"/>
        </div>
      </fieldset>
    </div>
  {/if}
  <div class="materialsRequestLoggedInFields" {if !$user}style="display:none"{/if}>
    <fieldset class="anythink-collapsible">
      <legend>Contact info</legend>
        <div id="materialRequestContactInfo">
          Please enter your contact information so we can contact you if we have questions about your request.
          {if $showPhoneField}
          <div>
            <label for="phone">{translate text='Phone'}: </label>
            <input type="text" name="phone" id="phone" size="15" class="tel" value="{$defaultPhone}"/>
          </div>
          {/if}
          <div>
            <label for="email">{translate text='Email'}: </label>
            <input type="text" name="email" id="email" maxlength="80" value="{$defaultEmail}"/>
          </div>
        </div>
    </fieldset>
  </div>

{/if}