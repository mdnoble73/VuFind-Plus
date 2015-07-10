{strip}
<form{* name="placeHoldForm"*} id="bookMaterialForm" {*action="{$path}/Record/{$id|escape:"url"}/Booking" method="post"*}>
	{* TODO: the fallback POST action of form is not implemented *}
	<input type="hidden" name="id" value="{$id}">
	<fieldset>
		<div class="row">
			<div class="form-group col-sm-5">
				<label for="startDate" class="control-label">Start Date</label>
				<div class="input-group">
					<input id="startDate" name="startDate" type="text" class="form-control required" data-provide="datepicker" data-date-format="mm/dd/yyyy" data-date-start-date="0d" data-date-end-date="+2y">
					<span class="input-group-addon"><span class="glyphicon glyphicon-calendar" onclick="$('#startDate').datepicker('show')" aria-hidden="true"></span></span>
				</div>
			</div>
			<div class="form-group col-sm-5 ui-front">
				<label for="startTime" class="control-label">Start Time</label>
				<input id="startTime" name="startTime" type="text" class="form-control bookingTime required">
				{* the class ui-front ensures the jquery autocomplete attaches to the input's parent, thus ensuring displayed within/on top of the modal box*}
			</div>
		</div>
		<hr>
		<div class="row">
			<div class="form-group col-sm-5">
				<label for="endDate" class="control-label" >End Date</label>
				<div class="input-group">
					<input id="endDate" name="endDate" type="text" class="form-control required" data-provide="datepicker" data-date-format="mm/dd/yyyy" data-date-start-date="0d" data-date-end-date="+2y">
					<span class="input-group-addon"><span class="glyphicon glyphicon-calendar" onclick="$('#endDate').focus().datepicker('show')" aria-hidden="true"></span></span>
				</div>
			</div>
			<div class="form-group col-sm-5 ui-front">
				<label for="endTime" class="control-label">End Time</label>
				<input id="endTime" name="endTime" type="text" class="form-control bookingTime required">
			</div>
		</div>
	</fieldset>
</form>
	<script type="text/javascript">
		{literal}
		var time = [], hours = [1,2,3,4,5,6,7,8,9,10,11,12], mins = ['00',10,20,30,40,50], meridian = ['pm','am'];
		meridian.forEach(function(ampm){hours.forEach(function(hour){mins.forEach(function(min){time[time.length] = hour + ':' + min + ampm})})});

		jQuery.validator.addMethod("bookingTime", function(value, element) {
							return this.optional(element) || /^([0-9]|1[0-2])\:[0-5]0[a,p]m$/.test(value);
						}, "Please enter a valid time"
		);

		$(function(){
			$('#bookMaterialForm').validate({
				submitHandler: function(){
					VuFind.Record.submitBookMaterialForm()
				},
				highlight: function(e){
					$(e).closest('.form-group').addClass('has-error')
				},
				unhighlight: function(e){
					$(e).closest('.form-group').removeClass('has-error')
				}
 			});

//			$('#endTime, #startTime').autocomplete({source:time})
			$('#endTime, #startTime').autocomplete({source:function(req, response){
				response(time.filter(function(t){
					return new RegExp('^'+req.term, 'i').test(t)
				}))
			}})
		});

		$('#startDate').on('changeDate', function(e){
			if (!$('#endDate').datepicker('getDate')) $('#endDate').datepicker('setStartDate', $(this).datepicker('getDate'))
		});
		$('#endDate').on('changeDate', function(e){
			if (!$('#startDate').datepicker('getDate')) $('#startDate').datepicker('setEndDate', $(this).datepicker('getDate'))
		})
		{/literal}
		{* time is an array of valid times to chose from, by 10 minutes intervals.

		   the validator test is specific to booking times

		  the autocomplete source uses a custom searching function that matches the term against the start of the valid times.
		  so typing 3, will return all the times with an hour of 3
		 *}

	</script>
{/strip}
{*
<script type="text/javascript">
	{literal}
	var hours = [1,2,3,4,5,6,7,8,9,10,11,12],
					mins = [10,20,30,40,50],
					meridian = ['am','pm'],
					time = [];

	meridian.forEach(function(ampm){
		hours.forEach(function(hour){
			mins.forEach(function(min){
				time[time.length] = hour + ':' + min + ampm
			})
		})
	});

	$(function(){
		$('#bookMaterialForm').validate();
		var hours
	})
	{/literal}
</script> *}