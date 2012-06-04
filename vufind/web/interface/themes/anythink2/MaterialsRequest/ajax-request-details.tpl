<div class='header' id="popupboxHeader" >
  <a href="#" onclick='hideLightbox();return false;'>close</a>
  Materials Request Details
  <!-- <a href="#" onclick='printRequestBody();return false;' class="closeIcon"><img src="{$path}/images/silk/printer.png" alt="print" />&nbsp;</a> -->
</div>
<div id="popupboxContent" class="content">
  {if $error}
    <div class="error">{$error}</div>
  {else}
    <div>
      {if $showUserInformation}
        <fieldset>
          <legend>User Information</legend>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Username: </div>
            <div class="request_detail_field_value">{$requestUser->firstname} {$requestUser->lastname}</div>
          </div>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Barcode: </div>
            <div class="request_detail_field_value">{$requestUser->cat_username}</div>
          </div>
          {if $materialsRequest->phone}
          <div class="request_detail_field">
            <div class="request_detail_field_label">Phone Number: </div>
            <div class="request_detail_field_value">{$materialsRequest->phone}</div>
          </div>
          {/if}
          <div class="request_detail_field">
            <div class="request_detail_field_label">Email: </div>
            <div class="request_detail_field_value">{$materialsRequest->email}</div>
          </div>
          {if $materialsRequest->illItem == 1}
            <div class="request_detail_field">
              <div class="request_detail_field_label">ILL if not purchased: </div>
              <div class="request_detail_field_value">Yes</div>
            </div>
          {/if}
          {if $materialsRequest->placeHoldWhenAvailable == 1}
            <div class="request_detail_field">
              <div class="request_detail_field_label">Place Hold for User: </div>
              <div class="request_detail_field_value">Yes ({$materialsRequest->location}{if $materialsRequest->bookmobileStop}{$materialsRequest->bookmobileStop}{/if})</div>
            </div>
          {/if}
        </fieldset>
      {/if}
      <fieldset>
        <legend>Basic Information</legend>
        <div class="request_detail_field">
          <div class="request_detail_field_label">Format: </div>
          <div class="request_detail_field_value">{$materialsRequest->format}</div>
        </div>
        <div class="request_detail_field">
          <div class="request_detail_field_label">Title: </div>
          <div class="request_detail_field_value">{$materialsRequest->title}</div>
        </div>
        {if $materialsRequest->format == 'dvd' || $materialsRequest->format == 'vhs'}
          <div class="request_detail_field">
            <div class="request_detail_field_label">Season: </div>
            <div class="request_detail_field_value">{$materialsRequest->season}</div>
          </div>
        {/if}
        <div class="request_detail_field">
          {if $materialsRequest->format == 'dvd' || $materialsRequest->format == 'vhs'}
            <div class="request_detail_field_label">Actor / Director: </div>
          {elseif $materialsRequest->format == 'cdMusic'}
            <div class="request_detail_field_label">Artist / Composer: </div>
          {else}
            <div class="request_detail_field_label">Author: </div>
          {/if}
          <div class="request_detail_field_value">{$materialsRequest->author}</div>
        </div>
        {if $materialsRequest->format == 'article'}
          <div class="request_detail_field">
            <div class="request_detail_field_label">Magazine/Journal Title: </div>
            <div class="request_detail_field_value">{$materialsRequest->magazineTitle}</div>
          </div>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Date: </div>
            <div class="request_detail_field_value">{$materialsRequest->magazineDate}</div>
          </div>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Volume: </div>
            <div class="request_detail_field_value">{$materialsRequest->magazineVolume}</div>
          </div>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Number: </div>
            <div class="request_detail_field_value">{$materialsRequest->magazineNumber}</div>
          </div>
          <div class="request_detail_field">
            <div class="request_detail_field_label">Page Numbers: </div>
            <div class="request_detail_field_value">{$materialsRequest->magazinePageNumbers}</div>
          </div>
        {/if}
        {if $materialsRequest->format == 'ebook'}
          <div class="request_detail_field">
            <div class="request_detail_field_label">E-book format: </div>
            <div class="request_detail_field_value">{$materialsRequest->ebookFormat|translate}</div>
          </div>
        {/if}
        {if $materialsRequest->format == 'eaudio'}
          <div class="request_detail_field">
            <div class="request_detail_field_label">E-audio format: </div>
            <div class="request_detail_field_value">{$materialsRequest->eaudioFormat|translate}</div>
          </div>
        {/if}
      </fieldset>
      <fieldset>
        <legend>Identifiers</legend>
        {if $materialsRequest->isbn}
        <div class="request_detail_field">
          <div class="request_detail_field_label">ISBN: </div>
          <div class="request_detail_field_value">{$materialsRequest->isbn}</div>
        </div>
        {/if}
        {if $materialsRequest->upc}
        <div class="request_detail_field">
          <div class="request_detail_field_label">UPC: </div>
          <div class="request_detail_field_value">{$materialsRequest->upc}</div>
        </div>
        {/if}
        {if $materialsRequest->issn}
        <div class="request_detail_field">
          <div class="request_detail_field_label">ISSN: </div>
          <div class="request_detail_field_value">{$materialsRequest->issn}</div>
        </div>
        {/if}
        {if $materialsRequest->oclcNumber}
        <div class="request_detail_field">
          <div class="request_detail_field_label">OCLC Number: </div>
          <div class="request_detail_field_value">{$materialsRequest->oclcNumber}</div>
        </div>
        {/if}
      </fieldset>
      <fieldset>
        <legend>Supplemental Details</legend>
        {if $materialsRequest->ageLevel}
        <div class="request_detail_field">
          <div class="request_detail_field_label">Age Level: </div>
          <div class="request_detail_field_value">{$materialsRequest->ageLevel}</div>
        </div>
        {/if}
        {if $materialsRequest->abridged != 2}
        <div class="request_detail_field">
          <div class="request_detail_field_label">Abridged: </div>
          <div class="request_detail_field_value">{if $materialsRequest->abridged == 1}Abridged Version{elseif $materialsRequest->abridged == 0}Unabridged Version{/if}</div>
        </div>
        {/if}
        {if $materialsRequest->bookType}
        <div class="request_detail_field">
          <div class="request_detail_field_label">Type: </div>
          <div class="request_detail_field_value">{$materialsRequest->bookType|translate|ucfirst}</div>
        </div>
        {/if}
        
        {if $materialsRequest->publisher}
        <div class="request_detail_field">
          <div class="request_detail_field_label">Publisher: </div>
          <div class="request_detail_field_value">{$materialsRequest->publisher}</div>
        </div>
        {/if}
        {if $materialsRequest->publicationYear}
        <div class="request_detail_field">
          <div class="request_detail_field_label">Publication Year: </div>
          <div class="request_detail_field_value">{$materialsRequest->publicationYear}</div>
        </div>
        {/if}
      </fieldset>
      <div class="request_detail_field">
        <div class="request_detail_field_label">Where did you hear about this title? </div>
        <div class="request_detail_field_value_long">{$materialsRequest->about}</div>
      </div>
      {if $materialsRequest->comments}
      <div class="request_detail_field">
        <div class="request_detail_field_label">Comments: </div>
        <div class="request_detail_field_value_long">{$materialsRequest->comments}</div>
      </div>
      {/if}
      <div class="request_detail_field">
        <div class="request_detail_field_label">Status: </div>
        <div class="request_detail_field_value">{$materialsRequest->statusLabel}</div>
      </div>
      <div class="request_detail_field">
        <div class="request_detail_field_label">Requested: </div>
        <div class="request_detail_field_value">{$materialsRequest->dateCreated|date_format}</div>
      </div>
      
    </div>
  {/if}
</div>
