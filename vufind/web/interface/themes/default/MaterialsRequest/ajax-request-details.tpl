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
				
				<div class="request_detail_field">
					<div class="request_detail_field_label">Title: </div>
					<div class="request_detail_field_value">{$materialsRequest->title}</div>
				</div>
				<div class="request_detail_field">
					<div class="request_detail_field_label">Author: </div>
					<div class="request_detail_field_value">{$materialsRequest->author}</div>
				</div>
				<div class="request_detail_field">
					<div class="request_detail_field_label">Format: </div>
					<div class="request_detail_field_value">{$materialsRequest->format}</div>
				</div>
				{if $materialsRequest->ageLevel}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Age Level: </div>
					<div class="request_detail_field_value">{$materialsRequest->ageLevel}</div>
				</div>
				{/if}
				{if $materialsRequest->isbn_upc}
				<div class="request_detail_field">
					<div class="request_detail_field_label">ISBN/UPC: </div>
					<div class="request_detail_field_value">{$materialsRequest->isbn_upc}</div>
				</div>
				{/if}
				{if $materialsRequest->oclcNumber}
				<div class="request_detail_field">
					<div class="request_detail_field_label">OCLC Number: </div>
					<div class="request_detail_field_value">{$materialsRequest->oclcNumber}</div>
				</div>
				{/if}
				{if $materialsRequest->publisher}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Publisher: </div>
					<div class="request_detail_field_value">{$materialsRequest->publisher}</div>
				</div>
				{/if}
				{if $materialsRequest->publicationYear}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Publication Year: </div>
					<div class="request_detail_field_value">{$materialsRequest->publicationYear}</div>
				</div>
				{/if}
				{if $materialsRequest->articleInfo}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Article Information: </div>
					<div class="request_detail_field_value">{$materialsRequest->articleInfo}</div>
				</div>
				{/if}
				{if $materialsRequest->abridged != 2}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Abridged: </div>
					<div class="request_detail_field_value">{if $materialsRequest->abridged == 1}Abridged Version{elseif $materialsRequest->abridged == 0}Unabridged Version{/if}</div>
				</div>
				{/if}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Where did you here about this title? </div>
					<div class="request_detail_field_value_long">{$materialsRequest->about}</div>
				</div>
				{if $materialsRequest->comments}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Comments: </div>
					<div class="request_detail_field_value_long">{$materialsRequest->comments}</div>
				</div>
				{/if}
				<div class="request_detail_field">
					<div class="request_detail_field_label">Status: </div>
					<div class="request_detail_field_value">{$materialsRequest->status}</div>
				</div>
				<div class="request_detail_field">
					<div class="request_detail_field_label">Requested: </div>
					<div class="request_detail_field_value">{$materialsRequest->dateCreated|date_format}</div>
				</div>
				
			</div>
		{/if}
	</div>
</div>