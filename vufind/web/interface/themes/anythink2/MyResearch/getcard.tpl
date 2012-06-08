<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js"></script>
<div id="main-content">
  <h1>{translate text='Get a Library Card'}</h1>
  <div class="page">
    <p>{translate text="Get an Anythink card by filling out the form below and bringing a photo ID to the service desk at your local Anythink or Anythink in Motion where you can pick from several fun designs and colors. Anythink cards are valid at all libraries and the bookmobile, and can also be used to obtain library cards at most other Colorado public, academic and school libraries."}</p>
    <form id="getacard" method="POST" action="{$path}/MyResearch/GetCard">
      <div class="clearfix">
        <div class="split-form">
          <div class="form-item">
            <label>{translate text="First name"}<span class="required">*</span></label>
            <div><input name="firstName" type="text" size="50" maxlength="50" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Last name"}<span class="required">*</span></label>
            <div><input name="lastName" type="text" size="50" maxlength="50" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Preferred Language"}</span></label>
            <div>
              <select name="language" type="text">
                {foreach from=$selfRegLanguages key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>{translate text="Birthday (MM/DD/YYYY)"}<span class="required">*</span></label>
            <div><input name="borrowerNote" type="text" size="80" maxlength="80" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Phone number (###-###-####)"}</label>
            <div><input name="phone" type="text" size="20" maxlength="20" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Phone type"}<span class="required">*</span></label>
            <div>
              <select name="phoneType" type="text">
                {foreach from=$selfRegPhoneType key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>{translate text="Address"}<span class="required">*</span></label>
            <div><input name="address1" type="text" size="80" maxlength="80" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Address 2"}</label>
            <div><input name="address2" type="text" size="80" maxlength="80"/></div>
          </div>
          <div class="form-item">
            <label>{translate text="City, ST"}<span class="required">*</span></label>
            <div>
              <select name="citySt" type="text">
                {foreach from=$selfRegCityStates key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>{translate text="Zip code"}<span class="required">*</span></label>
            <div><input name="zip" type="text" size="10" maxlength="10" /></div>
          </div>
        </div>
        <div class="spilt-form">
          <div class="form-item">
            <label>{translate text="Preferred location"}<span class="required">*</span></label>
            <div>
              <select name="location" type="text">
                {foreach from=$selfRegLocations key=value item=label}
                  <option value={$value}>{$label}</option>
                {/foreach}
              </select>
            </div>
          </div>
          <div class="form-item">
            <label>{translate text="Choose your 4-digit PIN"}<span class="required">*</span></label>
            <div><input name="pin" type="text" size="10" maxlength="10" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="Confirm your PIN"}<span class="required">*</span></label>
            <div><input name="confirmPin" type="text" size="10" maxlength="10" /></div>
          </div>
          <div class="form-item">
            <label>{translate text="How would you like to receive notices from Anythink regarding your account?"}<span class="required">*</span></label>
            <div><select name="sendNoticeBy"><option value="1">E-mail</option><option value="0">Phone</option></select></div>
          </div>
          <div class="form-item">
            <label>{translate text="Email address"}<span class="required">*</span></label>
            <div><input name="email" type="text" size="80" maxlength="80" /></div>
          </div>
          <h3>{translate text="Terms"}</h3>
          {translate text="By submitting my information, I accept responsibility for all materials borrowed on this card, and I will abide by all Anythink policies. I agree to use and enjoy Anythink libraries, be responsible for materials I borrow, ask questions, and tell my friends and family about the library."}
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
