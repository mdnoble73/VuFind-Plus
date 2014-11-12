{strip}
	<form class="form" role="form">
		<input type="hidden" name="holdId" value="{$holdId}" id="holdId"/>
		<div class="form-group">
			<label for="reactivationDate">Select the date when you want the hold reactivated.</label>
			<input type="text" name="reactivationDate" id="reactivationDate" required="true" class="form-control input-sm"/>
		</div>
	</form>
	<script	type="text/javascript">
		{literal}
		$(function() {
			$( "#reactivationDate" ).datepicker();
		});
		{/literal}
	</script>
{/strip}