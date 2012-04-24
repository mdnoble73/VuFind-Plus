 <table border="0" width ="100%" class="holdingsTable">
 <thead>
 <tr>
 <th>Location</th>
 <th>Collection</th>
 <th>Copy</th>
 <th>Call#</th>
 <th>Status</th>
 <th>Due</th>
 </tr>
 </thead>
 <tbody>
{foreach from=$holdings item=holding1}
 {foreach from=$holding1 item=holding}
  <tr >
  	{* Location *}
  	<td style = "padding-bottom:5px;"><span><strong>
  	{$holding.location|escape}
    {if $holding.locationLink} (<a href='{$holding.locationLink}' target='_blank'>Map</a>){/if}
  	</strong></span></td>
  	
  	{* Collection *}
  	<td style = "padding-bottom:5px;">{$holding.collection|escape}</td>
  	
  	{* Copy *}
  	<td style = "padding-bottom:5px;">{$holding.copy|escape}</td>
  	
  	{* Call# *}
  	<td style = "padding-bottom:5px;">
  	{$holding.callnumber|escape}
  	</td>
  	
  	{* Status *}
  	<td style = "padding-bottom:5px;">
  	  {if $holding.reserve == "Y"}
        {$holding.statusfull}
      {else}
        {if $holding.availability}
            <span class="available">{$holding.statusfull}{if $holding.holdable == 0 && $showHoldButton} <label class='notHoldable' title='{$holding.nonHoldableReason}'>(Not Holdable)</label>{/if}</span>
        {else}
            <span class="checkedout">{$holding.statusfull}{if $holding.holdable == 0 && $showHoldButton} <label class='notHoldable' title='{$holding.nonHoldableReason}'>(Not Holdable)</label>{/if}</span>
        {/if}
      {/if}
    </td>
    
    {* Due *}
    <td style = "padding-bottom:5px;">
  	  {if $holding.duedate}{$holding.duedate}{/if}
    </td>
    
  </tr>

   {/foreach}
 {foreachelse}
   No Copies Found
 {/foreach}
 </tbody>
 </table>
