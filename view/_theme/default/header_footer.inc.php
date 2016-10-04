<?php

require_once(Config::get('approot').'/core/utility.inc.php');

/* If the view has a style.css or script-header.js file, it will be included between parts 1 and 2 */
function theme_header($part=0){
  switch($part){
    case 1:
      // include first part of library CLF header, before close of head tag
      _readfile('https://clf.library.ubc.ca/7.0.2/library-header-part1.php');
      break;
    case 2:
      echo '</head>
<body>';
      break;
    default:
      echo '<p class="alert alert-error">There is no header part '.$part.'</p>';
  }
}

function theme_footer($part=0){
  switch($part){
    case 1:
      break;
    case 2:
      echo '</body>
</html>';
      break;
    default:
      echo '<p class="alert alert-error">There is no footer part '.$part.'</p>';
  }
}
