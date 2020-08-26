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

class Relations_Admin_Auto extends Relations_Admin_Input {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Auto() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
      $forbids - What to forbid 
      $helps - Help info in URL, text, and popups
      $styles - The styles to use in HTML
      $classes - The classes to use in HTML
      $elements - The extra element to use in HTML

    */

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed

    list(
      $this->name,
      $this->label,
      $this->field,
      $this->forbids,
      $this->helps,
      $this->styles,
      $this->classes,
      $this->elements
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'FIELD',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

    // Let everything know this is an auto input

    $this->auto = true;

  }



  /*** Input ***/



  //// Grabs defaults from html/sessions

  function defaults($records) {

    // De nada

  }



  //// Grabs input from html

  function entered($records) {

    // De nada

  }



  //// Grabs input from html for mass set

  function massed($records) {

    // De nada

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set($depth) {

    // Save everything

    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_values',$this->values);

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // Load everything

    $this->values = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_values');

  }



  /*** Database ***/



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
      case 'delete':
      case 'ignore':
        break;

    }

  }



  /*** HTML ***/



  //// Returns html for viewing

  function viewHTML($record) {

    // Return the value, if set

    if (strlen($this->values[$record])) 
      return Relations_Admin_ValueHTML($this,$this->values[$record]);
    else
      return '';

  }



  //// Returns html for entering

  function enterHTML($record,$alive) {
    
    // If there's errors

    if (is_array($this->errors[$record]) && (count($this->errors[$record]) > 0))
      $errors = "Errors: " . implode(', ',$this->errors[$record]);
    else
      $errors = '';

    // Return the value, with message and help

    return Relations_Admin_MessageHTML($this,$errors,'error'). 
           Relations_Admin_HelpHTML($this) . 
           $this->viewHTML($record);

  }



  //// Returns html for mass set

  function massHTML() {

    // Just return auto

    return '';

  }



  //// Returns html for searching

  function searchHTML() {

    // Create the name

    $name = $this->prefix . $this->name;

    // Construct the change function

    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$this->prefix$this->name" . "_search)";

    // Return a checkbox and text field

    return Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',$this->sought,'search_') . "<br>\n" .
           Relations_Admin_TextHTML($this,$name,$this->values[0],8,'','text_search',$changed);

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'Auto';
    $data['settings']['editable'] = 0;

    // Return

    return $data;

  }

}

?>