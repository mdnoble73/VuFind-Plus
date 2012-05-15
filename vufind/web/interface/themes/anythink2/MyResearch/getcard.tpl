<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="main-content">
  <h1>{translate text='Get a Library Card'}</h1>
  <div class="page">
    <p>Please enter the following information.</p>
    <form id="getacard" method="POST" action="{$path}/MyResearch/GetCard">
      <div class="clearfix">
        <div class="split-form">
          <div class="form-item">
            <label>First Name<span class="required">*</span></label>
            <div><input name="firstName" type="text" size="50" maxlength="50" /></div>
          </div>
          <div class="form-item">
            <label>Last Name<span class="required">*</span></label>
            <div><input name="lastName" type="text" size="50" maxlength="50" /></div>
          </div>
          <div class="form-item">
            <label>Preferred Language</span></label>
            <div>
              <select name="language" type="text">
                {foreach from=$selfRegLanguages key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>Birthdate<span class="required">*</span></label>
            <div><input name="borrowerNote" type="text" size="80" maxlength="80" /></div>
          </div>
          <div class="form-item">
            <label>Phone Number</label>
            <div><input name="phone" type="text" size="20" maxlength="20" /></div>
          </div>
          <div class="form-item">
            <label>Phone Type<span class="required">*</span></label>
            <div>
              <select name="phoneType" type="text">
                {foreach from=$selfRegPhoneType key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>Address<span class="required">*</span></label>
            <div><input name="address1" type="text" size="80" maxlength="80" /></div>
          </div>
          <div class="form-item">
            <label>Address 2</label>
            <div><input name="address2" type="text" size="80" maxlength="80"/></div>
          </div>
          <div class="form-item">
            <label>City, ST<span class="required">*</span></label>
            <div>
              <select name="citySt" type="text">
                {foreach from=$selfRegCityStates key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>Zip Code<span class="required">*</span></label>
            <div><input name="zip" type="text" size="10" maxlength="10" /></div>
          </div>
        </div>
        <div class="spilt-form">
          <div class="form-item">
            <label>Which anythink library will you visit the most?<span class="required">*</span></label>
            <div>
              <select name="location" type="text">
                {foreach from=$selfRegLocations key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>Choose PIN#<span class="required">*</span></label>
            <div><input name="pin" type="text" size="10" maxlength="10" /></div>
          </div>
          <div class="form-item">
            <label>Confirm PIN#<span class="required">*</span></label>
            <div><input name="confirmPin" type="text" size="10" maxlength="10" /></div>
          </div>
          <div class="form-item">
            <label>Notice By<span class="required">*</span></label>
            <div><select name="sendNoticeBy"><option value="1">E-mail</option><option value="0">Phone</option></select></div>
          </div>
          <div class="form-item">
            <label>Email address<span class="required">*</span></label>
            <div><input name="email" type="text" size="80" maxlength="80" /></div>
          </div>
          <h3>Terms</h3>
          <p>I agree to be responsible for material borrowed with this card, and to pay any fees incurred on material charged on this card.</p>
          <p>I am aware that there are no age restrictions on borrowing any library materials, and I accept the responsibility for my child's selection of materials and use of the Internet.</p>
          <input id="getacardSubmit" name="submit" type="submit" onclick="return checkNewCard()" value="I agree" />
        </div>
      </div>
    </form>
  </div>
</div>
<script type="text/javascript">
{literal}
	$(document).ready(function(){
		$("#getacard").validate();
	});
{/literal}
</script>
