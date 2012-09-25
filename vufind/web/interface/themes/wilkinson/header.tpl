<div class="searchheader">
	<div id="headerwrap">
		<div class="container center">
			<div class="row">
				<div class="inner">
					<div class="grid_six zenleft" id="logo">
						<div id="logoinner">
							<a href="{if $homeLink}{$homeLink}{else}{$path}{/if}"> <img alt="Wilkinson Public Library Logo" src="{img filename="header_image.png"}" /> </a>
						</div>
					</div>
					<div class="grid_four zenlast" id="header4">
						<div class="moduletable_menu">
							<div class="jbmoduleBody">
								<ul class="header_menu">
									<li class="item-248 current active"><a href="{$homeLink}">Home</a></li>
									<li class="item-109" id="loginOptions" {if $user} style="display: none;"{/if}><a href="/MyResearch/Home">Login</a></li>
									<li class="item-110"><a href="{$homeLink}/contact-us">Contact Us</a></li>
									<li class="item-204"><a href="{$homeLink}/aboutus">About Us</a></li>
								</ul>
								<div class="clearer"></div>
								<ul class="header_menu" id="logoutOptions" {if !$user} style="display: none;"{/if}>
									<li><a href="{$path}/MyResearch/Logout">{translate text="Log Out"}</a></li>
									<li><a href="{$path}/MyResearch/Home">{translate text="Your Account"}</a></li>
									<li><a href="{$path}/MyResearch/Home" id="myAccountNameLink">Logged in as {$user->firstname|capitalize} {$user->lastname|capitalize}</a></li>
								</ul>
								<div class="clearer"></div>
								<ul class="header_menu">
									{foreach from=$allLangs key=langCode item=langName}
									<li><a class='languageLink {if $userLang == $langCode} selected{/if}' href="{$fullPath}{if $requestHasParams}&amp;{else}?{/if}mylang={$langCode}">{translate text=$langName}</a></li>
									{/foreach}
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="clearer"></div>
	</div>
	
	<div id="navwrap" style="position: relative; top: 0px;" class="">
		<div class="container center">
			<div class="row">
				<div class="inner">
					<div id="navWrapper">
						<div class="grid_twelve sans-serif left" id="nav">
							<div class="moduletable_menu">
								<div class="jbmoduleBody">
									<ul class="nav_menu sf-js-enabled sf-shadow">
										<li class="item-138 deeper parent"><a href="/MyResearch/Home" class="sf-with-ul"><span class="sf-sub-indicator"></span>My Account</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-163" style="white-space: normal; float: left; width: 100%;"><a href="/MyResearch/Home" style="float: none; width: auto;">Login to Account</a></li>
												<li class="item-140" style="white-space: normal; float: left; width: 100%;"><a href="/Search/Home" style="float: none; width: auto;">Browse Catalog</a></li>
												<li class="item-283" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/my-account/downloads" style="float: none; width: auto;">Downloads</a></li>
											</ul> *}
										</li>
										<li class="item-242 deeper parent"><a href="{$homeLink}/library-services" class="sf-with-ul"><span class="sf-sub-indicator"></span>Services</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-282" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/getcard" style="float: none; width: auto;">Get a Library Card </a></li>
												<li class="item-356" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/borrowingmaterials" style="float: none; width: auto;">Borrowing Materials</a></li>
												<li class="item-330" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/checkitout" style="float: none; width: auto;">Check It Out</a></li>
												<li class="item-333" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/book-a-librarian" style="float: none; width: auto;">Book a Librarian</a></li>
												<li class="item-243" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/technology" style="float: none; width: auto;">Technology</a></li>
												<li class="item-350" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/databases" style="float: none; width: auto;">Research</a></li>
												<li class="item-201" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/library-services/meetingrooms" style="float: none; width: auto;">Meeting Rooms</a></li>
											</ul> *}
										</li>
										<li class="item-170 deeper parent"><a href="{$homeLink}/events" class="sf-with-ul"><span class="sf-sub-indicator"></span>Events</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-198" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/kids" style="float: none; width: auto;">Kids Programs</a></li>
												<li class="item-199" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/teens" style="float: none; width: auto;">Teen Programs</a></li>
												<li class="item-385" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/adults" style="float: none; width: auto;">Adult Programs</a></li>
												<li class="item-200" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/wpltv" style="float: none; width: auto;">WPL TV</a></li>
											</ul> *}
										</li>
										<li class="item-116 deeper parent"><a href="{$homeLink}/wplkids" class="sf-with-ul"><span class="sf-sub-indicator"></span>Kids</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-117" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplkids/find" style="float: none; width: auto;">Find Books &amp; Movies</a></li>
												<li class="item-120" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/kids" style="float: none; width: auto;">Upcoming Programs</a></li>
												<li class="item-220" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplkids/homeworkhelp" style="float: none; width: auto;">Homework Help</a></li>
												<li class="item-118" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplkids/fungames" style="float: none; width: auto;">Fun &amp; Games</a></li>
												<li class="item-119" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplkids/parentingresources" style="float: none; width: auto;">Parenting Resources</a></li>
												<li class="item-357" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplkids/staff-thoughts" style="float: none; width: auto;">Staff Thoughts</a></li>
											</ul> *}
										</li>
										<li class="item-106 deeper parent"><a href="{$homeLink}/wplteens" class="sf-with-ul"><span class="sf-sub-indicator"></span>Teens</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-231" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplteens/2012-06-21-21-42-32" style="float: none; width: auto;">Find Books &amp; Movies</a></li>
												<li class="item-241" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplteens/homework-help" style="float: none; width: auto;">Homework Help</a></li>
												<li class="item-442" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/events/teens" style="float: none; width: auto;">Upcoming Programs</a></li>
												<li class="item-353" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplteens/downloadteenebooks" style="float: none; width: auto;">Download</a></li>
												<li class="item-280" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/wplteens/teenthoughts" style="float: none; width: auto;">Teen Thoughts</a></li>
												<li class="item-226" style="white-space: normal; float: left; width: 100%;"><a target="_blank" href="http://www.facebook.com/wpl.teens#!/pages/Wilkinson-Public-Library-Teens/10150113667705541" style="float: none; width: auto;">Facebook</a></li>
											</ul> *}
										</li>
										<li class="item-157 deeper parent"><a href="{$homeLink}/community" class="sf-with-ul"><span class="sf-sub-indicator"></span>Community</a>
											{* <ul style="float: none; width: 14em; display: none; visibility: hidden;" class="sf-js-enabled sf-shadow">
												<li class="item-327" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/fol" style="float: none; width: auto;">Friends of the Library</a></li>
												<li class="item-329" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/discover-telluride" style="float: none; width: auto;">Discover Telluride</a></li>
												<li class="item-314" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/library-corner" style="float: none; width: auto;">Library Corner</a></li>
												<li class="item-433" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/your-corner" style="float: none; width: auto;">Patron Corner</a></li>
												<li class="item-328" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/partnerships" style="float: none; width: auto;">Partnerships</a></li>
												<li class="item-240" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/webcam" style="float: none; width: auto;">Webcam</a></li>
												<li class="item-436" style="white-space: normal; float: left; width: 100%;"><a href="{$homeLink}/community/donate" style="float: none; width: auto;">Donate</a></li>
											</ul> *}
										</li>
									</ul>
								</div>
	
	
							</div>

						</div>
						<div class="grid_three right" id="search"></div>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
</div>