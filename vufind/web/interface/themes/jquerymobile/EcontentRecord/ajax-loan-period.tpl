<div data-role="dialog">
	<div data-role="header" data-theme="d" data-position="inline">
		<h1>
			{translate text="Loan Period"}
		</h1>
	</div>
	<div data-role="content">
		<form method="post" action=""> 
			<div>
				<input type="hidden" name="overdriveId" value="{$overDriveId}"/>
				<input type="hidden" name="formatId" value="{$formatId}"/>
				<label for="loanPeriod">{translate text="How long would you like to checkout this title?"}</label>
				<select name="loanPeriod" id="loanPeriod">
					{foreach from=$loanPeriods item=loanPeriod}
						<option value="{$loanPeriod}">{$loanPeriod} days</option>
					{/foreach}
				</select> 
				<input type="submit" name="submit" value="Check Out" onclick="return checkoutOverDriveItemStep2('{$overDriveId}', '{$formatId}')"/>
				<a href="#" data-rel="back" class="closeIcon" data-role="button">Cancel</a>
				
			</div>
		</form>
	</div>
</div>