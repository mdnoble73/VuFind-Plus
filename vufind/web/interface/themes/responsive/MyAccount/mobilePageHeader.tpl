{strip}
	{* taken from MyAccount/menu.tpl*}
	{* id attributes have prefix 'mobileHeader-' added *}
	<div class="row visible-xs">
		<div id="mobileHeader" class="col-tn-12 col-xs-12">

			<div id="mobileHeader-myAccountFines">
				{assign var="totalFines" value=$user->getTotalFines()}
				{if $totalFines > 0 || ($showExpirationWarnings && $user->expireClose)}
					{* $totalFines is calculated in menu.tpl *}
					{if $showEcommerceLink && $totalFines > $minimumFineAmount}
						<div class="myAccountLink" style="color:red; font-weight:bold;">
							Your account{if count($user->getLinkedUsers())>0}s have{else} has{/if} ${$totalFines|number_format:2} in fines.
						</div>
						<div class="myAccountLink">
							<a href="{$ecommerceLink}" target="_blank"{if $showRefreshAccountButton} onclick="VuFind.Account.ajaxLightbox('{$path}/AJAX/JSON?method=getPayFinesAfterAction')"{/if}>
								{if $payFinesLinkText}{$payFinesLinkText}{else}Pay Fines Online{/if}
							</a>
						</div>
					{else}
						<div class="myAccountLink" title="Please contact your local library to pay fines or charges." style="color:red; font-weight:bold;" onclick="alert('Please contact your local library to pay fines or charges.')">
							Your account{if count($user->getLinkedUsers())>0}s have{else} has{/if} ${$totalFines|number_format:2} in fines.
						</div>
					{/if}
				{/if}

				{if $showExpirationWarnings && $user->expireClose}
					<div class="myAccountLink">
						<a class="alignright" title="Please contact your local library to have your library card renewed." style="color:red; font-weight:bold;" onclick="alert('Please Contact your local library to have your library card renewed.')" href="#">
							{if $user->expired}
								Your library card expired on {$user->expires}.
							{else}
								Your library card will expire on {$user->expires}.
							{/if}
						</a>
					</div>
				{/if}
			</div>

			{* taken from MyAccount/menu.tpl*}
			<div class="myAccountLink{if $action=="CheckedOut"} active{/if}">
				<a href="{$path}/MyAccount/CheckedOut" id="mobileHeader-checkedOut">
					Checked Out Titles {if !$offline}<span class="badge">{$user->getNumCheckedOutTotal()}</span>{/if}
				</a>
			</div>
			<div class="myAccountLink{if $action=="Holds"} active{/if}">
				<a href="{$path}/MyAccount/Holds" id="mobileHeader-holds">
					Titles On Hold {if !$offline}<span class="badge">{$user->getNumHoldsTotal()}</span>
					{if $user->getNumHoldsAvailableTotal() && $user->getNumHoldsAvailableTotal() > 0}
						&nbsp;<span class="label label-success">{$user->getNumHoldsAvailableTotal()} ready for pick up</span>
					{/if}{/if}
				</a>
			</div>

			{if $enableMaterialsBooking}
				<div class="myAccountLink{if $action=="Bookings"} active{/if}">
					<a href="{$path}/MyAccount/Bookings" id="mobileHeader-bookings">
						Scheduled Items  {if !$offline}<span class="badge">{$user->getNumBookingsTotal()}</span>{/if}
					</a>
				</div>
			{/if}
			<div class="myAccountLink{if $action=="ReadingHistory"} active{/if}">
				<a href="{$path}/MyAccount/ReadingHistory">
					Reading History {if !$offline}{if $user->readingHistorySize}<span class="badge">{$user->readingHistorySize}</span>{/if}{/if}
				</a>
			</div>

			<hr>
		</div>
	</div>

{/strip}