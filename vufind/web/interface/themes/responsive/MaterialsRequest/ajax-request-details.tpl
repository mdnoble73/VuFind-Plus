{if $error}
	<div class="error">{$error}</div>
{else}
	<div>
		{if $showUserInformation}
			<fieldset>
				<legend>User Information</legend>
				<div class="request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Username: </label>
					<div class="request_detail_field_value col-sm-9">{$requestUser->firstname} {$requestUser->lastname}</div>
				</div>
				<div class="request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Barcode: </label>
					<div class="request_detail_field_value col-sm-9">{$requestUser->cat_username}</div>
				</div>
				{if $materialsRequest->phone}
					<div class="request_detail_field row">
						<label class="request_detail_field_label col-sm-3">Phone Number: </label>
						<div class="request_detail_field_value col-sm-9">{$materialsRequest->phone}</div>
					</div>
				{/if}
				<div class="request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Email: </label>
					<div class="request_detail_field_value col-sm-9">{$materialsRequest->email}</div>
				</div>
				{if $materialsRequest->illItem == 1}
					<div class="request_detail_field row">
						<label class="request_detail_field_label col-sm-3">ILL if not purchased: </label>
						<div class="request_detail_field_value col-sm-9">Yes</div>
					</div>
				{/if}
				{if $materialsRequest->placeHoldWhenAvailable == 1}
					<div class="request_detail_field row">
						<label class="request_detail_field_label col-sm-3">Place Hold for User: </label>
						<div class="request_detail_field_value col-sm-9">Yes ({$materialsRequest->location}{if $materialsRequest->bookmobileStop}{$materialsRequest->bookmobileStop}{/if})</div>
					</div>
				{/if}
			</fieldset>
		{/if}
		<fieldset>
			<legend>Basic Information</legend>
			<div class="request_detail_field row">
				<label class="request_detail_field_label col-sm-3">Format: </label>
				<div class=" request_detail_field_value col-sm-9">{$materialsRequest->format}</div>
			</div>
			<div class=" request_detail_field row">
				<label class="request_detail_field_label col-sm-3">Title: </label>
				<div class=" request_detail_field_value col-sm-9">{$materialsRequest->title}</div>
			</div>
			{if $materialsRequest->format == 'dvd' || $materialsRequest->format == 'vhs'}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Season: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->season}</div>
				</div>
			{/if}
			<div class=" request_detail_field row">
				{if $materialsRequest->format == 'dvd' || $materialsRequest->format == 'vhs'}
					<label class="request_detail_field_label col-sm-3">Actor / Director: </label>
				{elseif $materialsRequest->format == 'cdMusic'}
					<label class="request_detail_field_label col-sm-3">Artist / Composer: </label>
				{else}
					<label class="request_detail_field_label col-sm-3">Author: </label>
				{/if}
				<div class=" request_detail_field_value col-sm-9">{$materialsRequest->author}</div>
			</div>
			{if $materialsRequest->format == 'article'}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Magazine/Journal Title: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineTitle}</div>
				</div>
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Date: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineDate}</div>
				</div>
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Volume: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineVolume}</div>
				</div>
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Number: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazineNumber}</div>
				</div>
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Page Numbers: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->magazinePageNumbers}</div>
				</div>
			{/if}
			{if $materialsRequest->format == 'ebook'}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">E-book format: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->ebookFormat|translate}</div>
				</div>
			{/if}
			{if $materialsRequest->format == 'eaudio'}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">E-audio format: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->eaudioFormat|translate}</div>
				</div>
			{/if}
		</fieldset>
		<fieldset>
			<legend>Identifiers</legend>
			{if $materialsRequest->isbn}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">ISBN: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->isbn}</div>
				</div>
			{/if}
			{if $materialsRequest->upc}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">UPC: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->upc}</div>
				</div>
			{/if}
			{if $materialsRequest->issn}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">ISSN: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->issn}</div>
				</div>
			{/if}
			{if $materialsRequest->oclcNumber}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">OCLC Number: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->oclcNumber}</div>
				</div>
			{/if}
		</fieldset>
		<fieldset>
			<legend>Supplemental Details</legend>
			{if $materialsRequest->ageLevel}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Age Level: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->ageLevel}</div>
				</div>
			{/if}
			{if $materialsRequest->abridged != 2}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Abridged: </label>
					<div class=" request_detail_field_value col-sm-9">{if $materialsRequest->abridged == 1}Abridged Version{elseif $materialsRequest->abridged == 0}Unabridged Version{/if}</div>
				</div>
			{/if}
			{if $materialsRequest->bookType}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Type: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->bookType|translate|ucfirst}</div>
				</div>
			{/if}
			{if $materialsRequest->publisher}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Publisher: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->publisher}</div>
				</div>
			{/if}
			{if $materialsRequest->publicationYear}
				<div class=" request_detail_field row">
					<label class="request_detail_field_label col-sm-3">Publication Year: </label>
					<div class=" request_detail_field_value col-sm-9">{$materialsRequest->publicationYear}</div>
				</div>
			{/if}
		</fieldset>
		<div class=" request_detail_field row">
			<label class="request_detail_field_label col-sm-3">Where did you hear about this title? </label>
			<div class="request_detail_field_value_long col-sm-9">{$materialsRequest->about}</div>
		</div>
		{if $materialsRequest->comments}
			<div class=" request_detail_field row">
				<label class="request_detail_field_label col-sm-3">Comments: </label>
				<div class="request_detail_field_value_long col-sm-9">{$materialsRequest->comments}</div>
			</div>
		{/if}
		<div class=" request_detail_field row">
			<label class="request_detail_field_label col-sm-3">Status: </label>
			<div class=" request_detail_field_value col-sm-9">{$materialsRequest->statusLabel}</div>
		</div>
		<div class=" request_detail_field row">
			<label class="request_detail_field_label col-sm-3">Requested: </label>
			<div class=" request_detail_field_value col-sm-9">{$materialsRequest->dateCreated|date_format}</div>
		</div>
	</div>
{/if}
