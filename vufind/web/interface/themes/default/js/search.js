var GetStatusList = new Array();
var GetSaveStatusList = new Array();
var GetExtIdsList = new Array();
var GetHTIdsList = new Array();

function getStatuses(id)
{
    GetStatusList[GetStatusList.length] = id;
}

function doGetStatuses(strings)
{
    // Do nothing if no statuses were requested:
    if (GetStatusList.length < 1) {
        return;
    }

    var now = new Date();
    var ts = Date.UTC(now.getFullYear(),now.getMonth(),now.getDay(),now.getHours(),now.getMinutes(),now.getSeconds(),now.getMilliseconds());

    var url = path + "/AJAX/JSON?method=getItemStatuses";
    for (var i=0; i<GetStatusList.length; i++) {
       url += "&id[]=" + encodeURIComponent(GetStatusList[i]);
    }
    url += "&time="+ts;

    var callback =
    {
        success: function(http) {
            var response = eval('(' + http.responseText + ')');
            var items = (response && response.data) ? response.data : [];

            for (i=0; i<items.length; i++) {
                var statusDiv = getElem('status' + items[i].id);
                if (statusDiv) {
                    if (items[i].reserves == 'true') {
                        statusDiv.innerHTML = '';
                    } else if (items[i].availability_message) {
                        statusDiv.innerHTML = items[i].availability_message;
                    } else {
                        statusDiv.innerHTML = strings.unknown;
                    }
                }

                var locationDiv = getElem('location' + items[i].id);
                var callnumberDiv = getElem('callnumber' + items[i].id);
                var locationListDiv = getElem('locationDetails' + items[i].id);
                
                if (items[i].locationList && locationListDiv) {
                    // Hide Call Number and Location Holders
                    if (callnumberDiv) {
                        callnumberDiv.parentNode.style.display = "none";
                    }
                    var locationListHTML = "";
                    for (x=0; x<items[i].locationList.length; x++) {
                        locationListHTML += '<div class="groupLocation">';
                        if (items[i].locationList[x].availability) {
                            locationListHTML += '<span class="availableLoc">' 
                                + items[i].locationList[x].location + '</span> ';
                        } else {
                            locationListHTML += '<span class="checkedoutLoc">'  
                                + items[i].locationList[x].location + '</span> ';
                        }
                        locationListHTML += '</div>';
                        locationListHTML += '<div class="groupCallnumber">';
                        locationListHTML += (items[i].locationList[x].callnumbers) 
                             ?  items[i].locationList[x].callnumbers : '';
                        locationListHTML += '</div>';
                    }
                    locationListDiv.innerHTML = locationListHTML;
                    locationListDiv.style.display = "block";
                } else {
                    if (locationDiv) {
                        locationDiv.innerHTML = (items[i].reserves == 'true')
                            ? items[i].reserve_message : items[i].location;
                    }
                    if (callnumberDiv) {
                        callnumberDiv.innerHTML = (items[i].callnumber)
                            ? items[i].callnumber : '';
                    }
                }
            }
        }
    };
    YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

function saveRecord(id, formElem, strings)
{
    successCallback = function() {
        // Redraw the statuses to reflect the change:
        doGetSaveStatuses();
    };
    performSaveRecord(id, formElem, strings, 'VuFind', successCallback);
}

function getSaveStatuses(id)
{
    GetSaveStatusList[GetSaveStatusList.length] = id;
}

function doGetSaveStatuses()
{
    if (GetSaveStatusList.length < 1) {
        return;
    }

    var now = new Date();
    var ts = Date.UTC(
        now.getFullYear(), now.getMonth(), now.getDay(), now.getHours(),
        now.getMinutes(), now.getSeconds(), now.getMilliseconds()
    );

    var url = path + "/AJAX/JSON?method=getSaveStatuses";
    for (var i=0; i<GetSaveStatusList.length; i++) {
        url += "&id[]" + "=" + encodeURIComponent(GetSaveStatusList[i]);
    }
    url += "&time="+ts;

    var callback =
    {
        success: function(http) {
            var response = eval('(' + http.responseText + ')');
            if (response && response.status == 'OK') {
                // Collect lists together by ID:
                var lists = [];
                for (var i = 0; i < response.data.length; i++) {
                    var current = response.data[i];
                    if (lists[current.record_id] == null) {
                        lists[current.record_id] = '';
                    }
                    lists[current.record_id] += '<li><a href="' + path +
                        '/MyResearch/MyList/' + current.list_id + '">' +
                        jsEntityEncode(current.list_title) + '</a></li>';
                }

                // Render all the grouped lists to the page:
                for (var i in lists) {
                    YAHOO.util.Dom.addClass(
                        document.getElementById('saveLink' + i), 'savedFavorite'
                    );
                    getElem('lists' + i).innerHTML = lists[i];
                }
            }
        }
    };
    YAHOO.util.Connect.asyncRequest('GET', url, callback, null);
}

function getExtIds(extId)
{
    GetExtIdsList[GetExtIdsList.length] = extId;
}

function doGetExtIds()
{
    var extIdsParams = "";
    for (var i=0; i<GetExtIdsList.length; i++) {
        if (GetExtIdsList[i].length > 0) {
            extIdsParams += encodeURIComponent(GetExtIdsList[i]) + ",";
        }
    }
    return extIdsParams;
}

function getHTIds(htConcat)
{
    GetHTIdsList[GetHTIdsList.length] = htConcat;
}

function doGetHTIds()
{
    var extHTParams = "";
    for (var i=0; i<GetHTIdsList.length - 1; i++) {
        extHTParams += encodeURIComponent(GetHTIdsList[i]) + "|";
    }
    extHTParams += encodeURIComponent(GetHTIdsList[GetHTIdsList.length - 1]);
    var retval = extHTParams;
    return retval;
}
