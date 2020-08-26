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

class Relations_Admin_TextArea extends Relations_Admin_Input {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_TextArea() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
      $rows - The rows of the textarea in HTML
      $cols - The cols of the textarea in HTML
      $wrap - The wrap of the textarea in HTML
      $fulltext - Whether this field has a fulltext index
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
      $this->rows,
      $this->cols,
      $this->wrap,
      $this->fulltext,
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
      'ROWS',
      'COLS',
      'WRAP',
      'FULLTEXT',
      'VALIDS',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

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
           Relations_Admin_TextAreaHTML($this,$name,$this->values[$record],$this->rows,$this->cols,$this->wrap);

  }



  //// Returns HTML for mass set

  function massHTML() {
    
    // Set the name and changed code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "mass(document.relations_admin_form.$name" . "_mass)";

    // Return the HTML

    return Relations_Admin_CheckboxHTML($this,$name . '_mass',1,'Use for Mass',false,'mass_') . "<br>\n" .
           Relations_Admin_TextAreaHTML($this,$name,$this->values[0],$this->rows,$this->cols,$this->wrap,'',$changed);

  }



  //// Returns HTML for searching

  function searchHTML() {
    
    // Set the name and changed code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$name" . "_search)";

    // Return the HTML

    return Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',$this->sought,'search_') . "<br>\n" .
           Relations_Admin_TextAreaHTML($this,$name,$this->values[0],$this->rows,$this->cols,$this->wrap,'',$changed);

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'Text';

    switch ($state) {

      case 'search':
      case 'enter':

        $data['settings']['rows'] = $this->rows;
        $data['settings']['cols'] = $this->cols;
        $data['settings']['wrap'] = $this->wrap;
        break;

    }

    return $data;

  }

}

?>