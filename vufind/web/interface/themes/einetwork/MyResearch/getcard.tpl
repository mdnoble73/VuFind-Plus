<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="page-content" class="content">
	
	<div id="main-content">
		<div class="resulthead"><h3>{translate text='Get a Library Card'}</h3></div>
		<div class="page">
			Please enter the following information:
			<form id="getacard" method="POST" action="{$path}/MyResearch/GetCard">
				<div class="getacardRow">
					<div class="getacardLabel">First Name<span class="required">*</span></div><div class="getacardInput"><input name="firstName" type="text" size="50" maxlength="50" class="required"/></div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Middle Initial</div><div class="getacardInput"><input name="middleInitial" type="text" size="50" maxlength="50"  /></div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Last Name<span class="required">*</span></div><div class="getacardInput"><input name="lastName" type="text" size="50" maxlength="50" class="required" /></div>
				</div>
				<hr />
				<div class="section">
					Mailing Address
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Street / P.O. Box<span class="required">*</span></div><div class="getacardInput"><input name="address1" type="text" size="80" maxlength="80" class="required"/></div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">City, State & ZIPCODE</div><div class="getacardInput"><input name="address2" type="text" size="40" maxlength="80"/>(CITY, ST 12345)</div>
				</div>
				<hr />
				<div class="section">
					Secondary Address
				</div>
				<div class="sectionNote">
					(If using a P.O. Box number as your mailing address, supply a home address;  If a student, supply a non-local address, if applicable. )
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Street / P.O. Box<span class="required">*</span></div><div class="getacardInput"><input name="address3" type="text" size="80" maxlength="80"/></div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">City, State & ZIPCODE</div><div class="getacardInput"><input name="address4" type="text" size="40" maxlength="80"/>(CITY, ST 12345)</div>
				</div>
				<hr />
				<div class="getacardRow">
					<div class="getacardLabel">Gender</div>
					<div class="getacardInput">
						<select name="gender">
							<option value="m">Male</option>
							<option value="f">Female</option>
						</select>
					</div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Birthdate<span class="required">*</span></div><div class="getacardInput"><input name="birthDate" type="text" size="80" maxlength="6" class="required"/>(MMDDYY)</div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Phone Number</div><div class="getacardInput"><input name="phone" type="text" size="20" maxlength="20" /> (xxx-xxx-xxxx)</div>
				</div>
				<div class="getacardRow">
					<div class="getacardLabel">Email address<span class="required">*</span></div><div class="getacardInput"><input name="email" type="text" size="80" maxlength="80" class="required email" /></div>
				</div>
				<p class="selfRegTerms">By clicking on the Submit button, I agree to be responsible for all library materials borrowed on my card. I will pay any late fees or charges for all delinquent, lost or damaged materials.</p>
				<p class="selfRegTerms">We will not release email addresses or any other information in your library record to third parties without an appropriate legal court document.</p>
				<input id="getacardSubmit" name="submit" class="button" type="submit" onclick="return checkNewCard()" value="I agree" />
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
{literal}
	$(document).ready(function(){
		$("#getacard").validate();
	});
{/literal}
</script>
