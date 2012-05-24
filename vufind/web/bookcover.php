<?php

switch ($_GET['size']) {
  case 'small':
    $dims = '72/92';
    break;
  case 'large':
    $dims = '265/400';
    break;
}

if (!empty($dims)) {
  header('Location: http://placekitten.com/' . $dims);
}
