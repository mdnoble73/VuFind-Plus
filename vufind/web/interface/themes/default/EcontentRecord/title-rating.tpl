<div id ="searchStars{$shortId|escape}{$starPostFixId}" class="{$ratingClass}">
  <div id="rateEContent{$shortId|escape}{$starPostFixId}" class="rateEContent{$shortId|escape} stat">
    <div class="statVal">
      <span class="ui-rater">
        <span class="ui-rater-starsOff" style="width:90px;"><span class="ui-rater-starsOn" style="width:0px">&nbsp;</span></span><br/>
        <span class="ui-rater-rating-{$shortId|escape} ui-rater-rating">0</span>&#160;(<span class="ui-rater-rateCount-{$shortId|escape} ui-rater-rateCount">0</span>)
      </span>
    </div>
    {if $showFavorites == 1} 
    <div id="saveLink{$recordId|escape}">
      <a href="{$path}/EcontentRecord/{$recordId|escape:"url"}/Save" style="padding-left:8px;" onclick="getLightbox('Record', 'Save', '{$recordId|escape}', '', '{translate text='Add to favorites'}', 'EcontentRecord', 'Save', '{$recordId|escape}'); return false;">{translate text='Add to favorites'}</a>
      {if $user}
      <script type="text/javascript">
        getSaveStatuses('{$recordId|escape:"javascript"}');
      </script>
      {/if}
    </div>
    {/if}
  </div>
  <script type="text/javascript">
    $(
       function() {literal} { {/literal}
           addRatingId('{$recordId|escape}', 'eContent');
           $('#econtentRate{$shortId|escape}{$starPostFixId}').rater({literal}{ {/literal}module: 'EcontentRecord', recordId: '{$shortId}',  rating:'0.0', postHref: '{$path}/EcontentRecord/{$recordId|escape}/AJAX?method=RateTitle'{literal} } {/literal});
       {literal} } {/literal}
    );
  </script>    
</div>