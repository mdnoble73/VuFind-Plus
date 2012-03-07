<div id='materials_request_details'>
	<div class='header'>
		Materials Request Details
		<a href="#" onclick='hideLightbox();return false;' class="closeIcon">Close <img src="{$path}/images/silk/cancel.png" alt="close" /></a>
	</div>
	<div class = "content">
		{if $error}
			<div class="error">{$error}</div>
		{else}
			<div>
				{if $showUserInformation}
					<h2>Submitted By</h2>
						<div class="request_detail_field">
						<div class="request_detail_field_label">Username: </div>
						<div class="request_detail_field_value">{$requestUser->firstname} {$requestUser->lastname}</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Barcode: </div>
						<div class="request_detail_field_value">{$requestUser->cat_username}</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Phone Number: </div>
						<div class="request_detail_field_value">{$materialsRequest->phone}</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Email: </div>
						<div class="request_detail_field_value">{$materialsRequest->email}</div>
					</div>
					<h2>Request Details</h2>
				{/if}
				<form action="/MaterialsRequest/Update" method="post">
					<input type="hidden" name="id" value="{$materialsRequest->id}" />
					<div class="request_detail_field">
						<div class="request_detail_field_label">Title: </div>
						<div class="request_detail_field_value"><input name="title" id="title" size="80" maxlength="255" class="required" value="{$materialsRequest->title}"/></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Author: </div>
						<div class="request_detail_field_value"><input name="author" id="author" size="80" maxlength="255" class="required" value="{$materialsRequest->author}"/></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Format: </div>
						<div class="request_detail_field_value"><label for="format">Format:</label>
							<select name="format" class="required">
								<option value="book" {if $materialsRequest->format=='book'}selected='selected'{/if}>Book</option>
								<option value="cd" {if $materialsRequest->format=='cd'}selected='selected'{/if}>CD</option>
								<option value="dvd" {if $materialsRequest->format=='dvd'}selected='selected'{/if}>DVD</option>
								<option value="ebook" {if $materialsRequest->format=='ebook'}selected='selected'{/if}>e-Book</option>
								<option value="eaudio" {if $materialsRequest->format=='eaudio'}selected='selected'{/if}>e-Audio</option>
								<option value="article" {if $materialsRequest->format=='article'}selected='selected'{/if}>Article</option>
								<option value="cassette"> {if $materialsRequest->format=='cassette'}selected='selected'{/if}Cassette</option>
								<option value="other" {if $materialsRequest->format=='other'}selected='selected'{/if}>Other</option>
							</select>
						</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Age Level: </div>
						<div class="request_detail_field_value">
							<select name="ageLevel">
								<option value="adult"{if $materialsRequest->ageLevel=='adult'}selected='selected'{/if}>Adult</option>
								<option value="teen"{if $materialsRequest->ageLevel=='teen'}selected='selected'{/if}>Teen</option>
								<option value="children"{if $materialsRequest->ageLevel=='children'}selected='selected'{/if}>Children</option>
							</select>
						</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">ISBN/UPC: </div>
						<div class="request_detail_field_value"><input name="isbn_upc" id="isbn_upc" size="15" maxlength="15" value="{$materialsRequest->isbn_upc}" /></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">OCLC Number: </div>
						<div class="request_detail_field_value"><input name="oclcNumber" id="oclcNumber" size="15" maxlength="30" value="{$materialsRequest->oclcNumber}" /></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Publisher: </div>
						<div class="request_detail_field_value"><input name="publisher" id="publisher" size="80" maxlength="255" value="{$materialsRequest->publisher}" /></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Publication Year: </div>
						<div class="request_detail_field_value"><input name="publicationYear" id="publicationYear" size="4" maxlength="4" value="{$materialsRequest->publicationYear}" /></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Article Information: </div>
						<div class="request_detail_field_value"><input name="articleInfo" id="articleInfo" size="80" maxlength="255" value="{$materialsRequest->articleInfo}" /></div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Abridged: </div>
						<div class="request_detail_field_value">
							<input type="radio" name="abridged" value="unabridged" id="unabridged" {if $materialsRequest->abridged == 0}checked='checked'{/if}/><label for="unabridged">Unabridged</label> <input type="radio" name="abridged" value="abridged" id="abridged" {if $materialsRequest->abridged == 1}checked='checked'{/if}/><label for="abridged">Abridged</label>  <input type="radio" name="abridged" value="na" id="na" {if $materialsRequest->abridged == 2}checked='checked'{/if}/><label for="na">Not Applicable</label>
						</div>
					</div>
					<div class="request_detail_field">
						<div class="request_detail_field_label">Comments: </div>
						<div class="request_detail_field_value_long">
							<textarea name="comments" id="comments" rows="3" cols="80">{$materialsRequest->comments}</textarea>
						</div>
					</div>
					<div>
						<input type="submit" name="submit" value="Update Request"/>
					</div>
				</form>
			</div>
		{/if}
	</div>
</div>