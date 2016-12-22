{strip}
{foreach from=$requestFormFields key=category item=formFields}
	<fieldset>
		<legend>{$category}</legend>
		{foreach from=$formFields item=formField}

			{if $formField->fieldType == 'format'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3" for="format">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">

						<select name="format" class="required form-control" id="format" onchange="VuFind.MaterialsRequest.setFieldVisibility();">
							{foreach from=$availableFormats item=label key=formatKey}
								<option value="{$formatKey}"{if $materialsRequest->format==$formatKey} selected='selected'{/if}>{$label}</option>
							{/foreach}
						</select>

					</div>
				</div>

				{* Special Fields *}

				{* Article Fields *}
				<div class="form-group specialFormatField articleField">
					<label for="magazineTitle" class="col-sm-3 control-label">Magazine/Journal Title: <span class="requiredIndicator">*</span> </label>
					<div class="col-sm-9">
						<input name="magazineTitle" id="magazineTitle" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->magazineTitle}">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineDate" class="col-sm-3 control-label">Magazine Date: </label>
					<div class="col-sm-9">
						<input name="magazineDate" id="magazineDate" size="20" maxlength="20" value="{$materialsRequest->magazineDate}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineVolume" class="col-sm-3 control-label">Magazine Volume: </label>
					<div class="col-sm-9">
						<input name="magazineVolume" id="magazineVolume" size="20" maxlength="20" value="{$materialsRequest->magazineVolume}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazineNumber" class="col-sm-3 control-label">Magazine Number: </label>
					<div class="col-sm-9">
						<input name="magazineNumber" id="magazineNumber" size="20" maxlength="20" value="{$materialsRequest->magazineNumber}" class="form-control">
					</div>
				</div>
				<div class="form-group specialFormatField articleField">
					<label for="magazinePageNumbers" class="col-sm-3 control-label">Magazine Page Numbers: </label>
					<div class="col-sm-9">
						<input name="magazinePageNumbers" id="magazinePageNumbers" size="20" maxlength="20" value="{$materialsRequest->magazinePageNumbers}" class="form-control">
					</div>
				</div>

				{* Season Fields *}
				<div class="form-group seasonField specialFormatField">
					<label for="season" class="col-sm-3 control-label">Season: </label>
					<div class="col-sm-9">
						<input name="season" id="season" size="90" maxlength="80" value="{$materialsRequest->season}" class="form-control">
					</div>
				</div>

				{* Ebook Format Fields *}
				{if $showEbookFormatField}
					<div class="form-group ebookField specialFormatField">
						<label for="ebookFormat" class="col-sm-3 control-label">E-book format: </label>
						<div class="col-sm-9">
							<select name="ebookFormat" id="ebookFormat" class="form-control">
								<option value="epub" {if $materialsRequest->subFormat=='epub'}selected='selected'{/if}>EPUB</option>
								<option value="kindle" {if $materialsRequest->subFormat=='kindle'}selected='selected'{/if}>Kindle</option>
								<option value="pdf" {if $materialsRequest->subFormat=='pdf'}selected='selected'{/if}>PDF</option>
								<option value="other" {if $materialsRequest->subFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
							</select>
						</div>
					</div>
				{/if}

				{*E-audio Format Fields *}
				{if $showEaudioFormatField}
					<div class="form-group eaudioField specialFormatField">{* eaudioField class used for both special field handling and the older format controlling *}
						<label for="eaudioFormat" class="col-sm-3 control-label">E-audio format: </label>
						<div class="col-sm-9">
							<select name="eaudioFormat" id="eaudioFormat" class="form-control">
								<option value="wma" {if $materialsRequest->subFormat=='wma'}selected='selected'{/if}>WMA</option>
								<option value="mp3" {if $materialsRequest->subFormat=='mp3'}selected='selected'{/if}>MP3</option>
								<option value="other" {if $materialsRequest->subFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
							</select>
						</div>
					</div>
				{/if}

				{* Abridged Fields *}
				<div class="form-group abridgedField specialFormatField">
					<label class="control-label col-sm-3">Abridged: </label>
					<div class="col-sm-9">
						<label for="unabridged" class="radio-inline"><input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}>Unabridged</label>
						<label for="abridged" class="radio-inline"><input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}>Abridged</label>
						<label for="na" class="radio-inline"><input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}>Not Applicable</label>
					</div>
				</div>

				{*TODO: Make Book Type Special Format Field *}
				{* Book Type Input Fields *}
			{elseif $formField->fieldType == 'bookType'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				{if $showBookTypeField}
					<div class="form-group{* specialFormatField*}">
						<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="col-sm-9">
							<select name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" class="form-control">
								<option value="fiction" {if $materialsRequest->bookType=='fiction'}selected='selected'{/if}>Fiction</option>
								<option value="nonfiction" {if $materialsRequest->bookType=='nonfiction'}selected='selected'{/if}>Non-Fiction</option>
								<option value="graphicNovel" {if $materialsRequest->bookType=='graphicNovel'}selected='selected'{/if}>Graphic Novel</option>
								<option value="unknown" {if (!isset($materialsRequest->bookType) || $materialsRequest->bookType=='unknown')}selected='selected'{/if}>Don't Know</option>
							</select>
						</div>
					</div>
				{/if}


				{if !$new && $useWorldCat}
					<div class="form-group">
						<label for="suggestIdentifiers" class="control-label col-sm-3">WorldCat Look up:</label>
						<div class="col-sm-9">
							<input type="button" id="suggestIdentifiers" value="Find exact match" onclick="return VuFind.MaterialsRequest.getWorldCatIdentifiers();" class="btn btn-primary">
						</div>
					</div>
				{/if}

				{* The other Fields to Display (Not special format fields) *}


				{* Readonly Fields *}
			{elseif $formField->fieldType == 'id'}
				{if $isAdminUser}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="request_detail_field row">
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							{$materialsRequest->$materialRequestTableColumnName}
							<input type="hidden" name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" value="{$materialsRequest->$materialRequestTableColumnName}">
						</div>
					</div>
				{/if}

				{* Author Field *}
			{elseif $formField->fieldType == 'author'}
				<div class="row form-group">
					<label id="authorFieldLabel" class="control-label col-sm-3" for="author">{$materialsRequest->authorLabel}: <span class="requiredIndicator">*</span></label>
					<div class="request_detail_field_value col-sm-9">
						<input name="author" id="author" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->author}">
					</div>
				</div>

				{* Publisher Input Fields *}
			{elseif
			$formField->fieldType == 'publisher' ||
			$formField->fieldType == 'publicationYear'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3" for="{$materialRequestTableColumnName}">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="40" maxlength="255" class="form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>


				{* Required Regular Input Field *}
			{elseif
			$formField->fieldType == 'title'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="90" maxlength="255"
										class="required form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>

				{* Regular Input Field *}
			{elseif
			$formField->fieldType == 'isbn'||
			$formField->fieldType == 'oclcNumber' ||
			$formField->fieldType == 'articleInfo' ||
			$formField->fieldType == 'upc' ||
			$formField->fieldType == 'issn' ||
			$formField->fieldType == 'season'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
										size="90" maxlength="255" class="form-control"
										value="{$materialsRequest->$materialRequestTableColumnName}">
					</div>
				</div>

				{* Text Area Fields*}
			{elseif
			$formField->fieldType == 'comments' ||
			$formField->fieldType == 'about'}

				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
							<textarea name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" rows="3" cols="80"
							          class="form-control {if $formField->fieldType == 'about' && $requireAboutField} required{/if}">
								{$materialsRequest->$materialRequestTableColumnName}
							</textarea>
					</div>
				</div>

			{elseif $formField->fieldType == 'status'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="request_detail_field row">
					<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class=" request_detail_field_value col-sm-9">
						{if $isAdminUser}
							<select name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}" class="form-control">
								{foreach from=$availableStatuses item=statusLabel key=status}
									<option value="{$status}"{if $materialsRequest->status == status} selected="selected"{/if}>{$statusLabel}</option>
								{/foreach}
							</select>
						{else}
							{$materialsRequest->statusLabel}
						{/if}
					</div>
				</div>

			{elseif
			$formField->fieldType == 'dateCreated'||
			$formField->fieldType == 'dateUpdated'}
				{* Date Fields *}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="request_detail_field row">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						{$materialsRequest->$materialRequestTableColumnName|date_format}
					</div>
				</div>

			{elseif $formField->fieldType == 'emailSent' ||
			$formField->fieldType == 'holdsCreated'}
				{* Yes / No Fields *}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">

						<label for="{$materialRequestTableColumnName}Yes" class="radio-inline">
							<input type="radio" name="{$materialRequestTableColumnName}" value="1" id="{$materialRequestTableColumnName}Yes"{if $materialsRequest->$materialRequestTableColumnName == 1} checked="checked"{/if}>Yes
						</label>
						&nbsp;&nbsp;
						<label for="{$materialRequestTableColumnName}No" class="radio-inline">
							<input type="radio" name="{$materialRequestTableColumnName}" value="0" id="{$materialRequestTableColumnName}No"{if $materialsRequest->$materialRequestTableColumnName == 0} checked="checked"{/if}>No
						</label>

					</div>
				</div>

				{* USER INFORMATION FIELDS  *}

			{elseif $formField->fieldType == 'createdBy'}
				{if $showUserInformation}
					<div class="request_detail_field row">
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							{$requestUser->firstname} {$requestUser->lastname}
						</div>
					</div>
				{/if}


				{* Regular User Input Field *}
			{elseif
			$formField->fieldType == 'phone' ||
			$formField->fieldType == 'email'}
				{if $showUserInformation || $new}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="row form-group">
						<label for="{$materialRequestTableColumnName}" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">
							<input name="{$materialRequestTableColumnName}" id="{$materialRequestTableColumnName}"
											size="90" maxlength="255" class="form-control"
											value="{$materialsRequest->$materialRequestTableColumnName}">
						</div>
					</div>
				{/if}

			{elseif
			$formField->fieldType == 'illItem' ||
			$formField->fieldType == 'placeHoldWhenAvailable'}
				{* Yes / No  User Information Fields *}
				{if $showUserInformation || $new}
					{assign var="materialRequestTableColumnName" value=$formField->fieldType}
					<div class="row form-group ebookHideField eaudioHideField specialFormatHideField"{if $formField->fieldType == 'illItem'} id="illInfo"{/if}>
						<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class="request_detail_field_value col-sm-9">

							<label for="{$materialRequestTableColumnName}Yes" class="radio-inline">
								<input type="radio" name="{$materialRequestTableColumnName}" value="1" id="{$materialRequestTableColumnName}Yes"{if $materialsRequest->$materialRequestTableColumnName == 1} checked="checked"{/if}>Yes
							</label>
							&nbsp;&nbsp;
							<label for="{$materialRequestTableColumnName}No" class="radio-inline">
								<input type="radio" name="{$materialRequestTableColumnName}" value="0" id="{$materialRequestTableColumnName}No"{if $materialsRequest->$materialRequestTableColumnName == 0} checked="checked"{/if}>No
							</label>

						</div>
					</div>
				{/if}

			{elseif $formField->fieldType == 'holdPickupLocation'}
				{if $showUserInformation || $new} {*TODO: Should patron see ShowUser*}
					<div class="row form-group ebookHideField eaudioHideField" id="pickupLocationField">
						<label for="pickupLocation" class="control-label col-sm-3">{$formField->fieldLabel}: </label>
						<div class=" request_detail_field_value col-sm-9">
							<select name="holdPickupLocation" id="pickupLocation" onchange="VuFind.MaterialsRequest.updateHoldOptions();" class="form-control">
								{foreach from=$pickupLocations item=location}
									<option value="{$location.id}" {if $location.selected}selected="selected"{/if}>{$location.displayName}</option>
								{/foreach}
							</select>
						</div>
					</div>
					<div id="bookmobileStopField" class="form-group ebookHideField eaudioHideField ">
						<label for="bookmobileStop" class="control-label col-sm-3">Bookmobile Stop: </label>
						<div class="col-sm-9">
							<input name="bookmobileStop" id="bookmobileStop" size="50" maxlength="50" class="form-control" value="{$materialsRequest->bookmobileStop}">
						</div>
					</div>

				{/if}
			{elseif $formField->fieldType == 'libraryCardNumber'}
				{if $showUserInformation}
					{if $barCodeColumn}
						<div class="row form-group">
							<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
							<div class="request_detail_field_value col-sm-9">
								{$requestUser->$barCodeColumn}
							</div>
						</div>
					{/if}
				{/if}

				{* End of User Information Fields *}

			{elseif $formField->fieldType == 'ageLevel'}
				{assign var="materialRequestTableColumnName" value=$formField->fieldType}
				<div class="row form-group">
					<label class="control-label col-sm-3">{$formField->fieldLabel}: </label>
					<div class="request_detail_field_value col-sm-9">
						<select name="ageLevel" id="ageLevel" class="form-control">
							<option value="adult" {if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
							<option value="teen" {if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
							<option value="children" {if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
							<option value="unknown" {if !isset($materialsRequest->ageLevel) || $materialsRequest->ageLevel=='unknown'}selected='selected'{/if}>Don't Know</option>
						</select>
					</div>
				</div>

			{/if}

		{/foreach}
	</fieldset>
{/foreach}
{/strip}
{*


{strip}
<div class="materialsRequestLoggedInFields" {if !$user}style="display:none"{/if}>
	<fieldset>
		<legend>Basic Information</legend>
		<div class="form-group">
			<label for="format" class="col-sm-3 control-label">Format <span class="requiredIndicator">*</span></label>
			<div class="col-sm-9">
				<select name="format" class="required form-control" id="format" onchange="VuFind.MaterialsRequest.setFieldVisibility();">
					{foreach from=$availableFormats item=label key=formatKey}
						<option value="{$formatKey}" {if $materialsRequest->format==$formatKey}selected='selected'{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="form-group formatSpecificField articleField">
			<label for="magazineTitle" class="col-sm-3 control-label">Magazine/Journal Title <span class="requiredIndicator">*</span> </label>
			<div class="col-sm-9">
				<input name="magazineTitle" id="magazineTitle" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->magazineTitle}"/>
			</div>
		</div>
		<div class="form-group ">
			<label for="title" id="titleLabel" class="col-sm-3 control-label">Title <span class="requiredIndicator">*</span></label>
			<div class="col-sm-9">
				<input name="title" id="title" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->title}"/>
			</div>
		</div>
		<div class="form-group formatSpecificField dvdField blurayField vhsField">
			<label for="season" class="col-sm-3 control-label">Season</label>
			<div class="col-sm-9">
				<input name="season" id="season" size="90" maxlength="80" value="{$materialsRequest->season}" class="form-control"/>
			</div>
		</div>
		<div class="form-group">
			<label for="author" id="authorFieldLabel" class="col-sm-3 control-label">Author <span class="requiredIndicator">*</span></label>
			<div class="col-sm-9">
				<input name="author" id="author" size="90" maxlength="255" class="required form-control" value="{$materialsRequest->author}"/>
			</div>
		</div>
		<div class="form-group formatSpecificField articleField">
			<label for="magazineDate" class="col-sm-3 control-label">Date</label>
			<div class="col-sm-9">
				<input name="magazineDate" id="magazineDate" size="20" maxlength="20" value="{$materialsRequest->magazineDate}" class="form-control"/>
			</div>
		</div>
		<div class="form-group formatSpecificField articleField">
			<label for="magazineVolume" class="col-sm-3 control-label">Volume</label>
			<div class="col-sm-9">
				<input name="magazineVolume" id="magazineVolume" size="20" maxlength="20" value="{$materialsRequest->magazineVolume}" class="form-control"/>
			</div>
		</div>
		<div class="form-group formatSpecificField articleField">
			<label for="magazineNumber" class="col-sm-3 control-label">Number</label>
			<div class="col-sm-9">
				<input name="magazineNumber" id="magazineNumber" size="20" maxlength="20" value="{$materialsRequest->magazineNumber}" class="form-control"/>
			</div>
		</div>
		<div class="form-group formatSpecificField articleField">
			<label for="magazinePageNumbers" class="col-sm-3 control-label">Page Numbers</label>
			<div class="col-sm-9">
				<input name="magazinePageNumbers" id="magazinePageNumbers" size="20" maxlength="20" value="{$materialsRequest->magazinePageNumbers}" class="form-control"/>
			</div>
		</div>
		{if $showEbookFormatField}
			<div class="form-group formatSpecificField ebookField">
				<label for="ebookFormat" class="col-sm-3 control-label">E-book format</label>
				<div class="col-sm-9">
					<select name="ebookFormat" id="ebookFormat" class="form-control">
						<option value="epub" {if $materialsRequest->ebookFormat=='epub'}selected='selected'{/if}>EPUB</option>
						<option value="kindle" {if $materialsRequest->ebookFormat=='kindle'}selected='selected'{/if}>Kindle</option>
						<option value="pdf" {if $materialsRequest->ebookFormat=='pdf'}selected='selected'{/if}>PDF</option>
						<option value="other" {if $materialsRequest->ebookFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
					</select>
				</div>
			</div>
		{/if}
		{if $showEaudioFormatField}
			<div class="form-group formatSpecificField eaudioField">
				<label for="eaudioFormat" class="col-sm-3 control-label">E-audio format</label>
				<div class="col-sm-9">
					<select name="eaudioFormat" id="eaudioFormat" class="form-control">
						<option value="wma" {if $materialsRequest->eudioFormat=='wma'}selected='selected'{/if}>WMA</option>
						<option value="mp3" {if $materialsRequest->eudioFormat=='mp3'}selected='selected'{/if}>MP3</option>
						<option value="other" {if $materialsRequest->eudioFormat=='other'}selected='selected'{/if}>Other - please specify in comments</option>
					</select>
				</div>
			</div>
		{/if}

		{if !$new && $useWorldCat}
			<div class="form-group formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eAudioField playawayField cassetteField vhsField">
				<input type="button" id="suggestIdentifiers" value="Find exact match" onclick="return VuFind.MaterialsRequest.getWorldCatIdentifiers();" class="btn btn-primary"/>
				*}
{*<img width="88" height="31" alt="Some library data on this site is provided by WorldCat, the world's largest library catalog [WorldCat.org]" src="http://www.oclc.org/developer/sites/default/files/badges/wc_badge1.png">*}{*

				*}
{* Image link no longer valid *}{*

			</div>
		{/if}

		<div id="suggestedIdentifiers"></div>
		{if !$materialsRequest || $new}
			{if $showPlaceHoldField || $showIllField}
				<fieldset>
					<legend>Place a hold</legend>
					{if $showPlaceHoldField}
						<div class="form-group">
							<label class="control-label col-sm-3">Place a hold for me when the item is available</label>
							<div class="col-sm-9">
								<label for="placeHoldYes" class="radio-inline"><input type="radio" name="placeHoldWhenAvailable" value="1" id="placeHoldYes" checked="checked" onclick="VuFind.MaterialsRequest.updateHoldOptions();"/> Yes</label>
								&nbsp;&nbsp;
								<label for="placeHoldNo" class="radio-inline"><input type="radio" name="placeHoldWhenAvailable" value="0" id="placeHoldNo" onclick="VuFind.MaterialsRequest.updateHoldOptions();"/> No</label>
							</div>
						</div>
						<div id="pickupLocationField" class="form-group">
							<label for="pickupLocation" class="control-label col-sm-3">Pickup Location </label>
							<div class="col-sm-9">
								<select name="holdPickupLocation" id="pickupLocation" onchange="VuFind.MaterialsRequest.updateHoldOptions();" class="form-control">
									{foreach from=$pickupLocations item=location}
										<option value="{$location->locationId}" {if $location->selected == "selected"}selected="selected"{/if}>{$location->displayName}</option>
									{/foreach}
								</select>
							</div>
						</div>
						*}
{* TODO: never shown *}{*

						<div id="bookmobileStopField" style="display:none;" class="form-group">
							<label for="bookmobileStop" class="control-label col-sm-3">Bookmobile Stop </label>
							<div class="col-sm-9">
								<input name="bookmobileStop" id="bookmobileStop" size="50" maxlength="50" class="form-control">
							</div>
						</div>
					{/if}
					{if $showIllField}
						<div id="illInfo">
							<label class="control-label col-sm-3">Do you want us to borrow from another library if not purchased?</label>
							<div class="col-sm-9">
								<label for="illItemYes" class="radio-inline"><input type="radio" name="illItem" value="1" id="illItemYes" checked="checked" />Yes</label>
								&nbsp;&nbsp;
								<label for="illItemNo" class="radio-inline"><input type="radio" name="illItem" value="0" id="illItemNo" />No</label>
							</div>
						</div>
					{/if}
				</fieldset>
			{/if}
		{/if}
	</fieldset>
	<div>
		*}
{* The following is set with JS, so we should probably leave for now.*}{*

		{if !$new}
			<p class="alert alert-info">Tell us more about the item you’re looking for. The more information you provide, the easier for us to find exactly what you need.</p>
			<fieldset>
				<legend>Identifiers</legend>
				<div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField row">
					<label for="isbn" class="control-label col-sm-3">ISBN</label>
					<div class="col-sm-9"><input type="text" name="isbn" id="isbn" value="{$materialsRequest->isbn}" class="form-control"/></div>
				</div>
				<div class="formatSpecificField dvdField blurayField cdMusicField vhsField otherField row" >
					<label for="upc" class="control-label col-sm-3">UPC</label>
					<div class="col-sm-9"><input type="text" name="upc" id="upc" value="{$materialsRequest->upc}" class="form-control"/></div>
				</div>
				<div class="formatSpecificField articleField row">
					<label for="issn" class="control-label col-sm-3">ISSN</label>
					<div class="col-sm-9"><input type="text" name="issn" id="issn" value="{$materialsRequest->issn}" class="form-control"/></div>
				</div>
				<div class="formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField row">
					<label for="oclcNumber" class="control-label col-sm-3">OCLC Number</label>
					<div class="col-sm-9"><input type="text" name="oclcNumber" id="oclcNumber" value="{$materialsRequest->oclcNumber}" class="form-control"/></div>
				</div>
			</fieldset>
		{/if}
		<fieldset id="supplementalDetails">
			<legend>Supplemental Details (optional)</legend>
			{if $showAgeField}
				<div class="form-group formatSpecificField bookField largePrintField cdAudioField ebookField eaudioField playawayField cassetteField">
					<label for="ageLevel" class="control-label col-sm-3">Age Level</label>
					<div class="col-sm-9">
						<select name="ageLevel" id="ageLevel" class="form-control">
							<option value="adult" {if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
							<option value="teen" {if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
							<option value="children" {if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
							<option value="unknown" {if !isset($materialsRequest->ageLevel) || $materialsRequest->ageLevel=='unknown'}selected='selected'{/if}>Don't Know</option>
						</select>
					</div>
				</div>
			{/if}
			<div class="form-group formatSpecificField cdAudioField eaudioField playawayField cassetteField">
				<div class="col-sm-9 col-sm-offset-3">
					<label for="unabridged" class="radio-inline"><input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}/>Unabridged</label>
					<label for="abridged" class="radio-inline"><input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}/>Abridged</label>
					<label for="na" class="radio-inline"><input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}/>Not Applicable</label>
				</div>
			</div>
			{if $showBookTypeField}
				<div class="form-group formatSpecificField bookField largePrintField ebookField">
					<label for="bookType" class="control-label col-sm-3">Type</label>
					<div class="col-sm-9">
						<select name="bookType" id="bookType" class="form-control">
							<option value="fiction" {if $materialsRequest->bookType=='fiction'}selected='selected'{/if}>Fiction</option>
							<option value="nonfiction" {if $materialsRequest->bookType=='nonfiction'}selected='selected'{/if}>Non-Fiction</option>
							<option value="graphicNovel" {if $materialsRequest->bookType=='graphicNovel'}selected='selected'{/if}>Graphic Novel</option>
							<option value="unknown" {if (!isset($materialsRequest->bookType) || $materialsRequest->bookType=='unknown')}selected='selected'{/if}>Don't Know</option>
						</select>
					</div>
				</div>
			{/if}

			<div class="form-group formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField">
				<label for="publisher" class="control-label col-sm-3">Publisher</label>
				<div class="col-sm-9">
					<input name="publisher" id="publisher" size="40" maxlength="255" value="{$materialsRequest->publisher}" class="form-control"/>
				</div>
			</div>
			<div class="form-group formatSpecificField bookField largePrintField dvdField blurayField cdAudioField cdMusicField ebookField eaudioField playawayField cassetteField vhsField otherField">
				<label for="publicationYear" class="control-label col-sm-3">Publication Year</label>
				<div class="col-sm-9">
					<input name="publicationYear" id="publicationYear" size="6" maxlength="4" value="{$materialsRequest->publicationYear}" class="form-control"/>
				</div>
			</div>
		</fieldset>
	</div>

	{if !$materialsRequest || $new}
		<div class="form-group">
			<label for="about" class="control-label col-sm-3">How / where did you hear about this title{if $requireAboutField} <span class="requiredIndicator">*</span>{/if}</label>
			<div class="col-sm-9">
				<textarea name="about" id="about" rows="3" cols="80" class="form-control {if $requireAboutField} required{/if}">{$materialsRequest->about}</textarea>
			</div>
		</div>
	{/if}
	<div class="form-group">
		<label for="comments" class="control-label col-sm-3">Comments</label>
		<div class="col-sm-9">
			<textarea name="comments" id="comments" rows="3" cols="80" class="form-control">{$materialsRequest->comments}</textarea>
		</div>
	</div>
</div>
{if $materialsRequest && !$new}
	<input type="hidden" name="id" value="{$materialsRequest->id}" />
{else}

	{if !$user}
		<div id="materialsRequestLogin">
			<fieldset>
				<legend>{translate text="Login to your account"}</legend>
				<div class="form-group">
					<label for="username" class="control-label col-sm-3">{$usernameLabel} <span class="requiredIndicator">*</span> </label>
					<div class="col-sm-9">
						<input type="text" name="username" id="username" value="{$username|escape}" size="15" class="required form-control"/>
					</div>
				</div>
				<div class="form-group">
					<label for="password" class="control-label col-sm-3">{$passwordLabel} <span class="requiredIndicator">*</span> </label>
					<div class="col-sm-9">
						<input type="password" name="password" id="password" size="15" class="required form-control"/>
					</div>
				</div>
				<div class="col-sm-9 col-sm-offset-3">
					<input type="submit" name="login" value="Login" onclick="return VuFind.MaterialsRequest.materialsRequestLogin();" class="btn btn-sm btn-primary"/>
				</div>
			</fieldset>
		</div>
	{/if}
	<div class="materialsRequestLoggedInFields" {if !$user}style="display:none"{/if}>
		<fieldset>
			<legend>Contact info</legend>
			<div id="materialRequestContactInfo">
				<p class="alert alert-info">Review the contact details below to confirm we have your latest info on file in case we need to contact you about your request.</p>
				<div class="form-group">
					<label for="email" class="control-label col-sm-3">{translate text='Email'} </label>
					<div class="col-sm-9">
						<input type="text" name="email" id="email" size="40" maxlength="80" value="{$defaultEmail}" class="form-control"/>
					</div>
				</div>
				{if $showPhoneField}
					<div class="form-group">
						<label for="phone" class="control-label col-sm-3">{translate text='Phone'} </label>
						<div class="col-sm-9">
							<input type="text" name="phone" id="phone" size="15" class="tel form-control" value="{$defaultPhone}"/>
						</div>
					</div>
				{/if}
			</div>
		</fieldset>
	</div>
{/if}
{/strip}*}
