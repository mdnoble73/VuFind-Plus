{if $error}
	<div class="error">{$error}</div>
{else}
	{if $showUserInformation}
		<h3>Submitted By</h3>
		<div class="request_detail_field row">
			<div class="request_detail_field_label col-sm-3">Username: </div>
			<div class="request_detail_field_value col-sm-9">{$requestUser->firstname} {$requestUser->lastname}</div>
		</div>
		<div class="request_detail_field row">
			<div class="request_detail_field_label col-sm-3">Barcode: </div>
			<div class="request_detail_field_value col-sm-9">{$requestUser->cat_username}</div>
		</div>
		<div class="request_detail_field row">
			<div class="request_detail_field_label col-sm-3">Phone Number: </div>
			<div class="request_detail_field_value col-sm-9">{$materialsRequest->phone}</div>
		</div>
		<div class="request_detail_field row">
			<div class="request_detail_field_label col-sm-3">Email: </div>
			<div class="request_detail_field_value col-sm-9">{$materialsRequest->email}</div>
		</div>
		<h3>Request Details</h3>
	{/if}
	<form id="materialsRequestUpdateForm" action="/MaterialsRequest/Update" method="post" class="form form-horizontal">
		{include file="MaterialsRequest/request-form-fields.tpl"}
	</form>
{/if}
<script type="text/javascript">
VuFind.MaterialsRequest.setFieldVisibility();
$("#materialsRequestForm").validate();
</script>