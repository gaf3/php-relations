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
require_once('Relations/Admin/List.php');

class Relations_Admin_Group extends Relations_Admin_List {



  /*** Input ***/



  //// Grabs defaults from html/sessions

  function defaults($records,$depth) {

    // $records - The number of records to go through

    // Create an empty array of messages

    $this->errors = array();

    // Now go through all the records, grabbing
    // the value, using the default as the...um
    // default

    for ($record = 0; $record < $records; $record++) {

      // Grab the default value

      $default = Relations_Admin_default($depth,$this->prefix . $this->name,$this->values[$record]);
      $this->values[$record] = array_unique(Relations_toArray(Relations_Admin_default($depth,$this->prefix . $this->name . '_' . $record,$default)));

    }
    
    // Get the replacements

    $replacements = $this->replaces($depth);

    // Go through all the replacments and do so

    foreach ($replacements as $replacer=>$replacees)
      for ($record = 0; $record < $records; $record++)
        $this->values[$record] = array_unique(preg_replace($replacees,$replacer,$this->values[$record]));

  }



  //// Grabs input from html

  function entered($records) {

    // $records - The number of records to go through

    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $records; $record++)
      $this->values[$record] = array_unique(Relations_toArray(Relations_Admin_grab($this->prefix . $this->name . '_' . $record,$this->values[$record],'VPG')));

  }



  //// Grabs input from html for mass set

  function massed($records) {

    // $records - The number of records to go through

    // Check to see if we're going to use this
    // mass value. If so, go through the records
    // and check to see if each record is to use
    // the mass set value. If so, set accordingly

    if (Relations_Admin_grab($this->prefix . $this->name . '_mass',false,'VPG')) {

      $value = Relations_Admin_grab($this->prefix . $this->name,'','VPG');

      for ($record = 0; $record < $records; $record++)       
        $this->values[$record] = Relations_toArray($value);

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Check to see if you're searching, get the value

    $this->sought = Relations_Admin_grab($this->prefix . $this->name . '_search',false,'VPG');

    $this->values[0] = Relations_toArray(Relations_Admin_grab($this->prefix . $this->name));

  }



  /*** Validate ***/



  //// Empties data for a record

  function wipe($record) {

    // Wipe our stuff

    $this->values[$record] = array();

  }



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records) {

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (in_array($intentions[$record],array('ignore','delete')))
        continue;

      // Check to see all values exist

      foreach ($this->values[$record] as $value) {

        if (!in_array($value,$this->ids)) {

          $errors[$this->prefix . $this->name . '_' . $record][] = "Value does not exist";
          break;

        }

      }

    }

  }



  /*** Database ***/



  //// Sets from SQL data from the database

  function setSQL($sql,$record) {

    // $sql - The row of data returned from the DB 
    // $record - The record at which to set this data
    
    // Get the value returned 

    $this->values[$record] = Relations_toArray(Relations_Admin_cleanSQL($sql[$this->field]));

  }



  //// Builds SQL data to send to the database

  function toSQL(&$sql,$intention,$record) {

    // Check the reason we're getting data

    switch ($intention) {

      case 'data':

        $sql[$this->name] = $this->values[$record];
        break;

      case 'insert':
      case 'copy':
      case 'update':
      case 'replace':

        $sql = Relations_assignClauseAdd($sql,$this->field . "='" . mysql_escape_string(implode(',',$this->values[$record])) . "'");
        break;

      case 'delete':
      case 'ignore':
        break;

    }

  }



  //// Builds SQL data to receive from the database

  function fromSQL(&$sql,$intention,$ambition,$table,$record) {

    // Check the reason we're getting data

    switch ($intention) {

      case 'select':

        $sql->add(array(_where => "$table.$this->field='" . mysql_escape_string(implode(',',$this->values[$record])) . "'"));
        break;

      case 'search':

        if ($this->sought) {

          // Create a values array to hold 
          // the escaped values

          $values = array();

          // Escape all values

          foreach ($this->values[$record] as $value)
            $values[] = mysql_escape_string($value);

          $sql->add(array(_where => "find_in_set('" . implode("','",$values) . "',$table.$this->field) > 0"));

        }

        break;

    }

  }



  /*** HTML ***/



  //// Returns the script code

  function scriptJS(&$functions) {

    // Add the select, deselect, and implode scripts

    Relations_Admin_SelectGroupJS($functions);
    Relations_Admin_DeselectGroupJS($functions);
    Relations_Admin_ImplodeGroupJS($functions);

  }



  //// Returns HTML for viewing

  function viewHTML($record) {

    // Send back the values

    return Relations_Admin_ValuesHTML($this,$this->values[$record],$this->labels);

  }



  //// Returns HTML for entering

  function enterHTML($record,$alive) {

    // Set the name

    $name = $this->prefix . $this->name . '_' . $record;

    // If there's errors

    if (is_array($this->errors[$record]) && (count($this->errors[$record]) > 0))
      $errors = "Errors: " . implode(', ',$this->errors[$record]);
    else
      $errors = '';

    // Get the html

    $html = Relations_Admin_MessageHTML($this,$errors,'error');
    $html .= Relations_Admin_HelpHTML($this);

    // See what we're displaying as

    switch ($this->display) {

      case 'multiselect':
        $html .= Relations_Admin_MultiSelectHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[$record],$this->size);
        break;

      case 'checkboxes':
        $html .= Relations_Admin_CheckboxesHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[$record],$this->size,'');
        break;

      case 'dualselect':
        $html .= Relations_Admin_DualSelectHTML($this,$name,$this->ids,$this->labels,$this->values[$record],$this->size);
        break;

    }
    
    // Send back the html

    return $html;

  }



  //// Returns HTML for mass set

  function massHTML() {

    // Set the name and change code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "mass(document.relations_admin_form.$this->prefix$this->name" . "_mass)";

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_mass',1,'Use for Mass',false,'mass_') . "<br>\n";

    // See what we're displaying as

    switch ($this->display) {

      case 'multiselect':
        $html .= Relations_Admin_MultiSelectHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'checkboxes':
        $html .= Relations_Admin_CheckboxesHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'dualselect':
        $html .= Relations_Admin_DualSelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

    }
    
    // Send back the html

    return $html;

  }



  //// Returns HTML for searching

  function searchHTML() {

    // Set the name and change code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$this->prefix$this->name" . "_search)";

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',$this->sought,'search_') . "<br>\n";

    // See what we're displaying as

    switch ($this->display) {

      case 'multiselect':
        $html .= Relations_Admin_MultiSelectHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'checkboxes':
        $html .= Relations_Admin_CheckboxesHTML($this,$name . '[]',$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'dualselect':
        $html .= Relations_Admin_DualSelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

    }
    
    // Send back the html

    return $html;

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'Group';
    $data['settings']['structure'] = 'Array';

    return $data;

  }

}

?>