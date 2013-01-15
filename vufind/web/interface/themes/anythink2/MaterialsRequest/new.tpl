<script type="text/javascript" src="{$path}/js/validate/jquery.validate.js" ></script>
<script type="text/javascript" src="{$path}/services/MaterialsRequest/ajax.js" ></script>
  <div id="main-content">
    <h1>{translate text='Request it!'}<img src="http://www.oclc.org/developer/sites/default/files/badges/wc_badge1.png" width="88" height="31" alt="Some library data on this site is provided by WorldCat, the world's largest library catalog [WorldCat.org]" /></h1>
    <div id="materialsRequest">
      <div class="materialsRequestExplanation">
        {if !$user}
        <p>If you can't find what you're looking for in our catalog, let us do the work for you. We'll try requesting it from another library or consider purchasing it for Anythink's collection. </p>
        <p>Please login below to get started.</p>
        {else}
          <p>Can't find what you're looking for in our catalog? Fill out the form below to submit a request, and our team will help get the material it in your hands.</p>
          <h3><span class="step">1</span> Choose your format</h3>
          <p class="step-desc">Choose your desired format to ensure we get you the right item. Select book, DVD, music CD, etc.</p>
          <h3><span class="step">2</span> Tell us what you're looking for</h3>
          <p class="step-desc">Tell us about the item you're looking for.  Enter title and author; next click the button to find an exact match or enter more info under "Tell Us More." The more information we get, the more likely we'll find what you're looking for right away.</p>
          <h3><span class="step">3</span> Confirm your contact info</h3>
          <p class="step-desc">Want email updates on the status of your request? Confirm your email address under "Contact info" before clicking the "Submit Request" button.</p>
          <h3><span class="step">4</span> We'll borrow or buy</h3>
          <p class="step-desc">We may borrow the item for you from another library or purchase a copy for the Anythink collection.  Either way, it can take two to eight weeks for your item to arrive. We'll notify you when it's in and ready for pickup. </p>
        {/if}
      </div>
      <form id="materialsRequestForm" action="{$path}/MaterialsRequest/Submit" method="post">
        {include file="MaterialsRequest/request-form-fields.tpl"}
        <div class="materialsRequestLoggedInFields" {if !$user}style="display:none"{/if}>
          <div id="copyright">
            <p class="fine-print">WARNING CONCERNING COPYRIGHT RESTRICTIONS The copyright law of the United States (Title 17, United States Code) governs the making of photocopies or other reproductions of copyrighted material. Under certain conditions specified in the law, libraries and archives are authorized to furnish a photocopy or other reproduction. One of these specified conditions is that the photocopy or reproduction is not to be used for any purpose other than private study, scholarship, or research. If a user makes a request for, or later uses, a photocopy or reproduction for purposes in excess of fair use, that user may be liable for copyright infringement. This institution reserves the right to refuse to accept a copying order if, in its judgment, fulfillment of the order would involve violation of copyright law.</p>
            <div id="copyrightAgreement" class="formatSpecificField articleField">
            <input type="radio" name="acceptCopyright" class="required" id="acceptCopyrightYes" value="1"/><label for="acceptCopyrightYes">Accept</label>
            <input type="radio" name="acceptCopyright" id="acceptCopyrightNo" value="1"/><label for="acceptCopyrightNo">Decline</label>
            </div>
          </div>
          <div>
            <input type="submit" value="Submit Request" />
          </div>
        </div>
      </form>
    </div>
  </div>
<script type="text/javascript">{literal}
  setFieldVisibility();
  $("#materialsRequestForm").validate();
  if ($("#title").val() != "" || $("#author").val() != ""){
  	getWorldCatIdentifiersAnythink();
  }
{/literal}</script>
