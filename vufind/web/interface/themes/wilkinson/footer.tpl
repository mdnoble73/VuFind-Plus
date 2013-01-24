{strip}
<div id="bottomrow">
	<div class="container center">
		<div class="row">
			<div class="inner">
				<div id="bottom">
					<div class="grid_four" id="bottom1">
						<div class="moduletable">

							<div class="moduleTitle">
								<h3>
									Library Information
								</h3>
							</div>
							<div class="jbmoduleBody">
								<div class="custom">
									<p>
										<img width="114" height="114" alt="wpl" style="display: inline;" src="{img filename="wpl.png"}" />
									</p>
									<blockquote>
										<p>
											<span style="color: #acacac;">Wilkinson Public Library<br/>
											</span>
										</p>
									</blockquote>
									<div class="grid_six">
										<p>
											<span style="color: #acacac;"><strong>Call:</strong> 970-728-4519</span>
										</p>
										<p>
											<span style="color: #acacac;"><strong>Visit us:<br/>
											</strong>100 W. PacificTelluride, Colorado 81435</span>
										</p>
									</div>
									<div class="grid_six zenlast">
										<p>
											<span style="color: #acacac;"><strong>Mailing Address:<br/>
											</strong>PO Box 2189</span><br/>
											<span style="color: #acacac;">Telluride, Colorado 81435</span>
										</p>
										<p>
											<span style="color: #acacac;"><strong>Email: </strong>
												<a id="mailLink" href="#" style="color: #acacac;">This email address is being protected from spambots. You need JavaScript enabled to view it.</a>
											</span>
										</p>
										<p>
											<br />
											<br />
										</p>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="grid_four " id="bottom2">
						<div class="moduletable">
							<div class="moduleTitle">
								<h3>
									Hours of Operation
								</h3>
							</div>
							<div class="jbmoduleBody">
								<div class="custom">
									<p>
										<span style="color: #acacac;">Monday: 9:00am &ndash; 8:00pm</span><br/>
										<span style="color: #acacac;">Tuesday:&nbsp;9:00am &ndash; 8:00pm</span><br/>
										<span style="color: #acacac;">Wednesday:&nbsp;9:00am &ndash; 8:00pm</span><br/>
										<span style="color: #acacac;">Thursday:&nbsp;9:00am &ndash; 8:00pm</span><br/>
										<span style="color: #acacac;">Friday:&nbsp;9:00am &ndash; 6:00pm</span><br/>
										<span style="color: #acacac;">Saturday:&nbsp;10:00am &ndash; 6:00pm</span><br/>
										<span style="color: #acacac;">Sunday: Closed</span>
									</p>
								</div>
							</div>
						</div>
					</div>
					<div class="grid_four zenlast" id="bottom6">
						<div class="moduletable_menu">
							<div class="moduleTitle">
								<h3>
									Useful Links
								</h3>
							</div>
							<div class="jbmoduleBody">
								<ul class="footer_menu">
									<li class="item-430"><a href="{$homeLink}/documents">Documents</a>
									</li>
									<li class="item-145"><a href="{$homeLink}/library-services/meetingrooms">Meeting Rooms</a>
									</li>
									<li class="item-172"><a href="{$homeLink}/community/fol">Friends of the Library</a>
									</li>
									<li class="item-150"><a href="{$homeLink}/employment">Employment</a>
									</li>
									<li class="item-151"><a href="{$homeLink}/bod">Board of Directors</a>
									</li>
									<li class="item-146"><a href="{$homeLink}/my-wpl/findsecondtry/books">Browse Catalog</a>
									</li>
									<li class="item-147"><a href="{$homeLink}/events/wpltv">WPLTV</a>
									</li>
									<li class="item-148"><a href="{$homeLink}/community/library-corner">Library Corner</a>
									</li>
									<li class="item-203"><a href="{$homeLink}/contact7">Contact</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="footerwrap">
	<div class="container center">
		<div class="row">
			<div class="inner">
				<div id="footer">
					<div id="footerouter">
						<div id="footerinner">
							<div class="grid_twelve" id="footerLeft">
								<div class="moduletable">
									<div class="jbmoduleBody">
										<div class="custom">
											<p>Copyright &copy; 2012 Lifestyle. All Rights Reserved.</p>
										</div>
									</div>
								</div>
							</div>
							<!-- Copyright -->
							<div id="footerRight">Copyright 2012 - Wilkinson Public Library</div>
						</div>
					</div>
				</div>
			</div>
			<!-- innerContainer -->
		</div>
		<!-- containerBG -->
	</div>
	<!-- Container -->
</div>
<div class="clearer">&nbsp;</div>
{if !$productionServer}
<div class='location_info'>{$physicalLocation}</div>
{/if}
{/strip}
<script type='text/javascript'>
	var prefix = 'ma' + 'il' + 'to';
	var mail_path = 'hr' + 'ef' + '=';
	var addy22065 = 'askus' + '@';
	addy22065 = addy22065 + 'telluridelibrary' + '.' + 'org';
	$("#mailLink").attr('href', prefix + ':' + addy22065);
	$("#mailLink").html(addy22065);
</script>