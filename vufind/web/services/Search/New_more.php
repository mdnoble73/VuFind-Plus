<?php

$interface->assign('popupTitle', 'New Books');
//$popupContent = $interface->fetch('Search/new_more.tpl');
$interface->assign('popupContent', $popupContent);
$interface->display('popup-wrapper.tpl');