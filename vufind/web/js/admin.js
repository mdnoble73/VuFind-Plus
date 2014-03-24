function showReindexNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexNotes&id=" + id, true);
	return false;
}
function showReindexProcessNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id, true);
	return false;
}
function toggleReindexProcessInfo(id){
	$("#reindexEntry" + id).toggleClass("expanded collapsed");
	$("#processInfo" + id).toggle();
}
function showReindexProcessNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getReindexProcessNotes&id=" + id, true);
	return false;
}
function showCronNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronNotes&id=" + id, true);
	return false;
}
function showCronProcessNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getCronProcessNotes&id=" + id, true);
	return false;
}
function toggleCronProcessInfo(id){
	$("#cronEntry" + id).toggleClass("expanded collapsed");
	$("#processInfo" + id).toggle();
}
function showOverDriveExtractNotes(id){
	VuFind.Account.ajaxLightbox("/Admin/AJAX?method=getOverDriveExtractNotes&id=" + id, true);
	return false;
}