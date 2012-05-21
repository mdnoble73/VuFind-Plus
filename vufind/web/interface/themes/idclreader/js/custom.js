$(document).bind("mobileinit", function()
{
  $.mobile.ajaxEnabled = false;
});

function returnEpubIdclReader(ePubId)
{
	if (confirm('Are you sure you want to return this title?'))
	{
		var returnUrl= '/EcontentRecord/' + ePubId + '/ReturnTitle';
		$.getJSON(returnUrl, function (data)
		{
			if (data.success == false)
		    {
		      alert("Error returning eContent\r\n" + data.message);
		      window.location.reload();
		    }
		    else
		    {
		      alert("The eContent was returned successfully.");
		      window.location.reload();
		    }
		});
	}
}