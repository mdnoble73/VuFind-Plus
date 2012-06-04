<h3>
	How long would you like to checkout this title
</h3>


<form method='POST' action='/Mobile/ODCheckOutItem' data-ajax="false">
	<fieldset data-role="controlgroup">
		<select name="loanPeriod" id="loanPeriod">
			<option value="7">7 days</option>
			<option value="14">14 days</option>
			<option value="21">21 days</option>
		</select>
	</fieldset>
	<input type='hidden' name='overDriveId' value='{$overDriveId}' />
	<input type='hidden' name='overDriveFormatId' value='{$overDriveFormatId}' />
	<br/>
	<button type="submit" data-theme="b" name="submit" value="submit-value" data-ajax="false">
		Check Out
	</button>
</form>