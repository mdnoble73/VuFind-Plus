{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId">
		<input type="hidden" name="patronId" value="{$patronId}" id="patronId">
		<input type="hidden" name="recordId" value="{$recordId}" id="recordId">
		<div class="form-group">
			<label for="reactivationDate">Select the date when you want the hold {translate text="thawed"}.</label>
			<input type="text" name="reactivationDate" id="reactivationDate" class="required form-control input-sm">
		</div>
	</form>
	<script	type="text/javascript">
		{literal}
		$(function(){
			$(".form").validate({
				submitHandler: function(){
					VuFind.Account.doFreezeHoldWithReactivationDate('#doFreezeHoldWithReactivationDate');
				}
			});
			$( "#reactivationDate" ).datepicker({
				startDate: Date(),
				orientation:"top"
			});
		});
		{/literal}
	</script>
{/strip}