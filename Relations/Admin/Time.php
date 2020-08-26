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
require_once('Relations/Admin/Input.php');

class Relations_Admin_Time extends Relations_Admin_Input {



  /*** Input ***/



  //// Grabs input from html

  function entered($records) {

    // $records - The number of records to go through

    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $records; $record++) {

      $hour = Relations_Admin_grab($this->prefix . $this->name . '_' . $record . '_hour',substr($this->values[$record],0,2),'VPG');
      $minute = Relations_Admin_grab($this->prefix . $this->name . '_' . $record . '_minute',substr($this->values[$record],3,2),'VPG');

      if (!strlen($hour) && !strlen($minute))
        list($hour,$minute) = explode(':',Relations_Admin_grab($this->prefix . $this->name . '_' . $record,$this->values[$record],'VPG'));

      if (!$minute)
        $minute = '00';

      $this->values[$record] = "$hour:$minute";

    }

  }



  //// Grabs input from html for mass set

  function massed($records) {

    // $records - The number of records to go through

    // Check to see if we're going to use this
    // mass value. If so, go through the records
    // and check to see if each record is to use
    // the mass set value. If so, set accordingly

    if (Relations_Admin_grab($this->prefix . $this->name . '_mass',false,'VPG')) {

      $hour = Relations_Admin_grab($this->prefix . $this->name . '_hour','','VPG');
      $minute = Relations_Admin_grab($this->prefix . $this->name . '_minute','','VPG');

      if (!strlen($hour) && !strlen($minute))
        list($hour,$minute) = explode(':',Relations_Admin_grab($this->prefix . $this->name,'','VPG'));

      if (!$minute)
        $minute = '00';

      for ($record = 0; $record < $records; $record++)       
        $this->values[$record] = "$hour:$minute";

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Check to see if you're searching, get the value

    $this->sought = Relations_Admin_grab($this->prefix . $this->name . '_search',false,'VPG');

    $hour = Relations_Admin_grab($this->prefix . $this->name . '_hour','','VPG');
    $minute = Relations_Admin_grab($this->prefix . $this->name . '_minute','','VPG');

    if (!strlen($hour) && !strlen($minute))
      list($hour,$minute) = explode(':',Relations_Admin_grab($this->prefix . $this->name,'','VPG'));

    if (!$minute)
      $minute = '00';

    $this->values[0] = "$hour:$minute";

  }



  /*** HTML ***/



  //// Returns HTML for entering

  function enterHTML($record,$alive) {
    
    // Set the name

    $name = $this->prefix . $this->name . '_' . $record;

    // If there's errors

    if (is_array($this->errors[$record]) && (count($this->errors[$record]) > 0))
      $errors = "Errors: " . implode(', ',$this->errors[$record]);
    else
      $errors = '';

    // Return the HTML

    return Relations_Admin_MessageHTML($this,$errors,'error') . 
           Relations_Admin_HelpHTML($this) .
           Relations_Admin_TimeHTML($this,$name,$this->values[$record],false);

  }



  //// Returns HTML for mass set

  function massHTML() {
    
    // Set the name and changed code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "mass(document.relations_admin_form.$name" . "_mass)";

    // Return the HTML

    return Relations_Admin_CheckboxHTML($this,$name . '_mass',1,'Use for Mass',false,'mass_') . "<br>\n" .
           Relations_Admin_TimeHTML($this,$name,$this->values[0],false,'',$changed);

  }



  //// Returns HTML for searching

  function searchHTML() {
    
    // Set the name and changed code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$name" . "_search)";

    // Return the HTML

    return Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',$this->sought,'search_') . "<br>\n" .
           Relations_Admin_TimeHTML($this,$name,$this->values[0],true,'',$changed);

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'Time';

    return $data;

  }

}

?>