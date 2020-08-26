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

// This is the base class for all inputs

class Relations_Admin_Input {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Input() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
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
      'VALIDS',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

  }



  //// Initializes all the main arrays

  function initialize() {

    // Convert forbids to a hash

    $this->forbids = array_change_key_case(Relations_toHash($this->forbids));

    // Create the array of valids if it
    // wasn't set

    if (!is_array($this->valids))
      $this->valids = array();

    // Create the array of helps if it
    // wasn't set

    if (!is_array($this->helps))
      $this->helps = array();

    // Create the array of styles if it
    // wasn't set

    if (!is_array($this->styles))
      $this->styles = array();

    // Create the array of classes if it
    // wasn't set

    if (!is_array($this->classes))
      $this->classes = array();

    // Create the array of elements if it
    // wasn't set

    if (!is_array($this->elements))
      $this->elements = array();

    // Initialize focus

    $this->focus = array();

    // Create a list of default tips if
    // not set

    $defaults = array(
      'insert_button'   => "Inserts a new $this->label",
      'choose_button'   => "Selects an existing $this->label",
      'select_button'   => "Selects the current $this->label",
      'deselect_button' => "Removes the selected $this->label"
    );

    // Set them if not already set

    foreach ($defaults as $control=>$tip)
      if (!isset($this->helps['tip'][$control]))
        $this->helps['tip'][$control] = $tip;

  }



  /*** Input ***/



  //// Changes the intention of a record to be 
  //// different from its ambition

  function kill($record) {

    // $record - The record to kill

    // De nada

  }



  //// Changes the intention of a record to be 
  //// the same as its ambition

  function raise($record) {

    // $record - The record to raise

    // De nada

  }



  //// Grabs settings from html/sessions

  function settings($depth) {

    // De nada

  }



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
      $this->values[$record] = Relations_Admin_default($depth,$this->prefix . $this->name . '_' . $record,$default);

    }
    
    // Get the replacements

    $replacements = $this->replaces($depth);

    // Go through all the replacments and do so

    foreach ($replacements as $replacer=>$replacees)
      $this->values = preg_replace($replacees,$replacer,$this->values);
            
  }



  //// Grabs replaces from html/sessions

  function replaces($depth) {

    // First check to see if there's a replacements array

    if (!($replacements = Relations_Admin_default($depth,$this->prefix . $this->name . '_replacements'))) {

      // If not, create one

      $replacements = array();

      // Grab the replacers and replacees.
      // Replacers will be and array, and 
      // replacees will be arrays for each
      // of the replacers, with a the array
      // place as a numerical prefix

      $replacers = Relations_toArray(Relations_Admin_default($depth,$this->prefix . $this->name . '_replacers'));

      // Go through all the replacers

      for ($replacer = 0; $replacer < count($replacers); $replacer++) {

        // Create a replacements array for preg

        $replacements[$replacers[$replacer]] = array();

        // Get the replacees

        $replacees = Relations_toArray(Relations_Admin_default($depth,$this->prefix . $this->name . '_' . $replacer . '_replacees'));

        // Go through all the replacees

        foreach ($replacees as $replacee)
          $replacements[$replacers[$replacer]][] = '/^' . preg_quote($replacee,'/') . '$/';

      }

    }

    // Return them

    return $replacements;

  }



  //// Grabs input from html

  function entered($records) {

    // $records - The number of records to go through

    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $records; $record++)
      $this->values[$record] = Relations_Admin_grab($this->prefix . $this->name . '_' . $record,$this->values[$record],'VPG');

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
        $this->values[$record] = $value;

    }

  }



  //// Grabs input from html for searching

  function sought() {

    // Check to see if you're searching, get the value

    $this->sought = Relations_Admin_grab($this->prefix . $this->name . '_search',false,'VPG');

    $this->values[0] = Relations_Admin_grab($this->prefix . $this->name,'','VPG');

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set($depth) {

    // $depth - The depth at which the form was called

    // De nada

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // $depth - The depth at which the form was called

    // De nada

  }



  //// Stores info entered by the user

  function save($depth) {

    // $depth - The depth at which the form was called

    // Save everything

    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_values',$this->values);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_sought',$this->sought);

  }



  //// Retrieves info entered by the user

  function load($depth) {

    // $depth - The depth at which the form was called

    // Load everything

    $this->values = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_values');
    $this->sought = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_sought');

  }



  /*** Validate ***/



  //// Empties data for a record

  function wipe($record) {

    // Add our stuff

    $this->values[$record] = '';

  }



  //// Sets the intention for all records

  function oblige($intention) {

    // $intention - The intention; insert, delete, etc.

    // De nada

  }



  //// Takes the error messages and creates an
  //// array for adding onto the labels.

  function heed($numbers,$records) {

    // $numbers - Array of numbered errors 
    // $records - The number of records tro go through
    
    // Go through all the records. Use the value
    // in the HTML input fields. 

    for ($record = 0; $record < $records; $record++)
      $this->errors[$record] = $numbers[$this->prefix . $this->name . '_' . $record];

  }



  //// Checks values against valids

  function validsValidate(&$errors,$intentions,$records) {

    // Return just the empty array if there's
    // nothing to check out

    if (count($this->valids) == 0)
      return;

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring or deleting

      if (in_array($intentions[$record],array('ignore','delete')))
        continue;

      // Go through all the valids

      foreach ($this->valids as $message=>$valid) {

        // If this valid exists as a function,
        // check only this record and if the
        // function doesn't return true, add this
        // valids message to the errors array.

        if (function_exists($valid)) {

          if (!$valid($this->values[$record]))
            $errors[$this->prefix . $this->name . '_' . $record][] = $message;

        // Else it's a Perl regex pattern, so  
        // check only this record and if the
        // pattern doesn't match, add this
        // valids message to the errors array.

        } else {

          if (!preg_match($valid,$this->values[$record]))
            $errors[$this->prefix . $this->name . '_' . $record][] = $message;

        }

      }

    }

  }



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check valids

    $this->validsValidate($errors,$intentions,$records);


  }




  //// Makes sure everything is valid for fromDB
  
  function fromValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // De nada

  }



  /*** Database ***/



  //// Sets from SQL data from the database

  function setSQL($sql,$record) {

    // $sql - The row of data returned from the DB 
    // $record - The record at which to set this data
    
    // Get the value returned 

    $this->values[$record] = Relations_Admin_cleanSQL($sql[$this->field]);

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

        $sql = Relations_assignClauseAdd($sql,$this->field . "='" . mysql_escape_string($this->values[$record]) . "'");
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

        $sql->add(array(_where => "$table.$this->field='" . mysql_escape_string($this->values[$record]) . "'"));
        break;

      case 'search':

        if ($this->sought)
          $sql->add(array(_where => "$table.$this->field like '" . mysql_escape_string($this->values[$record]) . "'"));
        break;

    }

  }



  //// Merges the relations from all to first

  function merge(&$totals,$records) {

    // De nada

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals,$intentions,$records) {

    // De nada

  }



  //// Receives data from the database
  
  function fromDB(&$errors,&$totals,$intention,$ambition,$records) {

    // De nada

  }



  /*** Process ***/



  //// Process the input's redirecting

  function redirectProcess($record,$task) {

    // $record - The the record that wants to redirect
    // $task - The redirect task

    // Don't do anything

  }



  //// Processes the input's returning

  function returnProcess($totals) {

    // $totals - What happened

    // Set the focus 

    $this->focus[$this->redirected] = true;

  }



  //// Adds our info to the children update infos

  function updateInfosProcess(&$infos,$intentions,$records) {

    // Don't do anything

  }



  //// Adds our info to the children copy infos

  function copyInfosProcess(&$infos,$intentions,$records) {

    // Don't do anything

  }



  //// Adds our info to the children update infos

  function replaceInfosProcess(&$infos,$intentions,$records) {

    // Don't do anything

  }



  /*** HTML ***/



  //// Gets whether we need a mass area

  function needMass() {

    return false;

  }



  //// Prepares for display

  function prepare(&$errors,&$totals,$task,$state,$records) {

    // Do nothing

  }



  //// Returns the start of a form html

  function formStartHTML($records) {

    // Return nothing

    return '';

  }



  //// Returns the end of a form html

  function formEndHTML($records) {

    // Return nothing

    return '';

  }



  //// Returns the script code

  function scriptJS(&$functions) {

    // Do nothing

  }



  //// Returns whether there is a need for
  //// focusing onto one object

  function needFocus(&$focusing) {

    // Return whether there is 
    // a need for focusing

    if (count($this->focus))
      $focusing = true;

  }



  //// Retutns the input HTML

  function inputHTML($state,$record,$extra=false) {

    // Figure our what to display

    switch ($state) {

      case 'search':

        return $this->searchHTML();
        break;

      case 'list':

        break;

      case 'view':
      case 'browse':

        return $this->viewHTML($record);
        break;

      case 'link':

        return $this->linkHTML($record,$extra);
        break;

      case 'enter':

        return $this->enterHTML($record,$extra);
        break;

      case 'preview':

        return $this->previewHTML($record);
        break;

      case 'mass':

        return $this->massHTML();
        break;
      
    }

  }



  //// Returns HTML for viewing

  function viewHTML($record) {

    // Just the value

    return Relations_Admin_ValueHTML($this,$this->values[$record]);

  }



  //// Returns HTML for previewing

  function previewHTML($record) {

    // Use the value

    return $this->viewHTML($record);

  }



  //// Returns HTML for URLs

  function linkHTML($record,$suffix_url) {

    return $this->viewHTML($record);

  }



  //// Returns HTML for entering

  function enterHTML($record) {

    // Use the value

    return $this->viewHTML($record);

  }



  //// Returns HTML for mass set

  function massHTML() {

    // De nada

  }



  //// Returns HTML for searching

  function searchHTML() {

    // De nada

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Figure our what to set

    $data = array();
    $data['type'] = 'Input';
    $data['name'] = $this->name;
    $data['settings'] = array();
    $data['settings']['label'] = $this->label;
    $data['settings']['editable'] = 1;
    $data['settings']['searchable'] = 1;
    $data['settings']['structure'] = 'Scalar';
    $data['help'] = array();
    $data['errors'] = array();
    $data['options'] = array();
    $data['controls'] = array();
    $data['values'] = array();

    foreach ($records as $record)
      $data['values'][$record] = $this->values[$record];

    switch ($state) {

      case 'search':

        $data['sought'] = $this->sought;
        break;

      case 'list':

        break;

      case 'view':
      case 'browse':

        break;

      case 'link':

        break;

      case 'enter':

        $data['help'] = $this->help;
        $data['errors'] = $this->errors;
        break;

      case 'preview':

        break;

    }

    return $data;

  }

}

?>