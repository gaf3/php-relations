<?php

// +----------------------------------------------------------------------+
// | Relations-Admin v0.93                                                |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002, GAF-3 Industries, Inc. All rights reserved.      |
// +----------------------------------------------------------------------+
// | This program is free software, you can redistribute it and/or modify |
// | it under the same terms as PHP istelf                                |
// +----------------------------------------------------------------------+
// | Authors: George A. Fitch III (aka Gaffer) <gaf3@gaf3.com>            |
// +----------------------------------------------------------------------+

require_once('Relations/Admin.php');
require_once('Relations/Admin/TextArea.php');

class Relations_Admin_Addresses extends Relations_Admin_TextArea {

  //// Returns HTML for viewing

  function viewHTML($record) {

    // Just the value

    // Get the look

    $look = Relations_Admin_LookHTML($this,'value','input');

    // Return the value if set

    if (strlen($this->values[$record]))
      return "<span $look>" . nl2br(htmlentities($this->values[$record])) . "</span>\n";
    else
      return '';


  }

}

?>