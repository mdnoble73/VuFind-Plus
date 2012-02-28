<div class="clearer" ></div>
  
<div id="library-footer">
  <div id="library-footer-main">
  	<div class="footer-column" id="footerSystemLists">
			<h2 class="footer-menu-title">{translate text='New Titles you may be interested in'}:</h2>
			{foreach from=$footerLists key=listTitle item=listUrl}
				<div class="footer-list-link"><a href="{$listUrl|escape:'html'}">{$listTitle}</a></div>
			{/foreach}
		</div>
    <div class='footer-column' id='library-footer-stay-connected'>
      <h2 class='footer-menu-title'>{translate text='Stay Connected'}:</h2>
      <div id='stay-connected-row1'>
        <div class='stay-connected-link'>
          <a href='http://www.facebook.com/wcplonline'>
            <img src='{$path}/interface/themes/wcpl/images/facebook-icon.png' width='26px' height='27px' alt='Facebook' />
            <span class='stay-connected-link-text'>{translate text='Find us on Facebook'}</span>
          </a>
        </div>
        <div class='stay-connected-link'>
          <a href='http://www.youtube.com/wcplonline'>
            <img src='{$path}/interface/themes/wcpl/images/youtube-icon.png' width='26px' height='27px' alt='YouTube' />
            <span class='stay-connected-link-text'>{translate text='Find us on YouTube'}</span>
          </a>
        </div>
      </div>
      <div id='stay-connected-row2'>
        <div class='stay-connected-link'>
          <a href='http://www.twitter.com/wcplonline'>
            <img src='{$path}/interface/themes/wcpl/images/twitter-icon.png' width='26px' height='27px' alt='Twitter' />
            <span class='stay-connected-link-text'>{translate text='Find us on Twitter'}</span>
          </a>
        </div>
        <div class='stay-connected-link'>
          <a href='http://www.wakegov.com/libraries/howto/wcplapps.htm'>
            <img src='{$path}/interface/themes/wcpl/images/mobile-app-icon.png' width='26px' height='27px' alt='Mobile App' />
            <span class='stay-connected-link-text'>{translate text='Try our mobile app'}</span>
          </a>
        </div>
      </div>
    </div>
    
    <div class="footer-column" id="library-footer-services">
  	  <h2 class="footer-menu-title">{translate text='Services'}:</h2>
      <div id='services-row1' class='services-row'>
        <div class="footer-services-item footer-services-item-important"><a href="http://www.wakegov.com/libraries/locations/default.htm">Locations</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/about/default.htm">About</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/images/Library/calendar/default.htm">Events</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/reading/default.htm">Reading</a></div>
      </div>  
 	    <div id='services-row2' class='services-row'>
        <div class="footer-services-item footer-services-item-important"><a href="http://askwcpl.wakegov.com">Ask WCPL</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/howto/default.htm">How do I</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/kids/default.htm">Kids</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/images/Library/download_library/dl.html">Digital Media</a></div>
      </div>  
      <div id='services-row3' class='services-row'>
        <div class="footer-services-item footer-services-item-important"><a href="{$path}/MyResearch/Home">My Account</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/research/default.htm">Research</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/teens/default.htm">Teens</a></div>
        <div class="footer-services-item"><a href="http://www.wakegov.com/libraries/reading/blogs/default.htm">Blogs</a></div>
      </div>  
  	</div>
  </div>
</div>

