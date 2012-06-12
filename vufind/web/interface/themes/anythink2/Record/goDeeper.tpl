<div onmouseup="this.style.cursor='default';" id="popupboxHeader" class="header">
  <a onclick="hideLightbox(); return false;" href="">close</a>
  {$title}
</div>
<div id="popupboxContent" class="content">
  {* Generate links for each go deeper option *}
  <div id="goDeeperContent" class="clearfix">
    <div id="goDeeperLinks">
      <ul>
        {foreach from=$options item=option key=dataAction}
        <li class='goDeeperLink'><a href='#' onclick="getGoDeeperData('{$dataAction}', '{$id}', '{$isbn}', '{$upc}');return false;">{$option}</a></li>
        {/foreach}
      </ul>
    </div>
    <div id="goDeeperOutput">{$defaultGoDeeperData}</div>
  </div>
  <div id="goDeeperEnd">&nbsp;</div>
</div>
<script type="text/javascript" charset="utf-8">
  {literal}
  // @todo Refactor this to be in the initial AJAX callback.
  (function ($) {
    var image_link = $('<a href="#" />');
    image_link.text('Larger image');
    image_link.bind('click', function() {
      $('#goDeeperOutput').html($('<img />').attr('src', $('img.recordcover').attr('src')));
      return false;
    });
    $('#goDeeperLinks').append(image_link);
    image_link.wrap('<li class="goDeeperLink">');
  })(jQuery);
  {/literal}
</script>
