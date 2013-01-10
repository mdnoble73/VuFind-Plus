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
        <p>Can't find what you're looking for in our catalog? Fill out the form below with as much information as possible so we can find the exact title you need. For example, if you're looking for a specific season of a TV show, please include that info. We may buy the item for Anythink's collection or borrow it from another library; it could take up to two to eight weeks to receive. {/if}</p>
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
