<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
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
					{include file="MaterialsRequest/request-form-fields.tpl"}
					<div>
						<input type="submit" name="submit" value="Update Request"/>
					</div>
				</form>
			</div>
		{/if}
	</div>
</div>
<script type="text/javascript">
setFieldVisibility();
$("#materialsRequestForm").validate();
</script>