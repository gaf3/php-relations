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

class Relations_Admin_List extends Relations_Admin_Input {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_List() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
      $ids - The values of the select HTML
      $labels - The labels of the select HTML
      $display - The type of HTML display
      $size - The length of the select HTML
      $valids - Patterns or functions that validate data
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
      $this->ids,
      $this->labels,
      $this->display,
      $this->size,
      $this->valids,
      $this->forbids,
      $this->helps,
      $this->styles,
      $this->classes,
      $this->elements
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'FIELD',
      'IDS',
      'LABELS',
      'DISPLAY',
      'SIZE',
      'VALIDS',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

    // If the label's not an array
    // and ids are, set everything

    if (!is_array($this->labels) && is_array($this->ids)) {

      // Create the empty labels array

      $this->labels = array();

      // Go through all the ids, assigning
      // the ids as if they were labels.

      foreach ($this->ids as $id)
        $this->labels[$id] = $id;

    }

  }



  /*** Validate ***/



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records) {

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (in_array($intentions[$record],array('ignore','delete')))
        continue;

      // Check to see if value exists

      if (!in_array($this->values[$record],$this->ids))
        $errors[$this->prefix . $this->name . '_' . $record][] = "Value does not exist";

    }

  }



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check parent and exists

    parent::toValidate($errors,$intentions,$records);
    $this->existsValidate($errors,$intentions,$records);

  }



  /*** HTML ***/



  //// Returns HTML for viewing

  function viewHTML($record) {

    // Return the label of this value

    return Relations_Admin_ValueHTML($this,$this->labels[$this->values[$record]]);

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

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[$record],$this->size);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[$record],$this->size);
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

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
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

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
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

    $data['type'] = 'List';

    switch ($state) {

      case 'search':
      case 'enter':

        $data['settings']['display'] = $this->display;
        $data['settings']['size'] = $this->size;

      case 'view':
      case 'browse':
      case 'link':
      case 'preview':
        $data['options'] = $this->labels;

    }

    return $data;

  }

}

?>