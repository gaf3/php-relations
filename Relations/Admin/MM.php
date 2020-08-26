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
require_once('Relations/Admin/Group.php');

class Relations_Admin_MM extends Relations_Admin_Group {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_MM() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $local_field - The field in this table
      $mm_database - The many to many database
      $mm_table - The many to many table
      $mm_local_field - The many to many local field
      $mm_foreign_field - The many to many foreign
      $form - The form of the lookup
      $display - The type of HTML display
      $size - The size of the select HTML
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
      $this->local_field,
      $this->mm_database,
      $this->mm_table,
      $this->mm_local_field,
      $this->mm_foreign_field,
      $this->form,
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
      'LOCAL_FIELD',
      'MM_DATABASE',
      'MM_TABLE',
      'MM_LOCAL_FIELD',
      'MM_FOREIGN_FIELD',
      'FORM',
      'DISPLAY',
      'SIZE',
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

    // Create the array of helps if it
    // wasn't set

    if (!is_array($this->helps))
      $this->helps = array();

    // Create a list of default tips if
    // not set

    $defaults = array(
      'insert_button'   => "Inserts a new " . $this->form->label,
      'choose_button'   => "Selects an existing " . $this->form->label,
      'select_button'   => "Selects the current " . $this->form->label,
      'deselect_button' => "Removes the selected " . $this->form->label,
    );

    // Set them if not already set

    foreach ($defaults as $control=>$tip)
      if (!isset($this->helps['tip'][$control]))
        $this->helps['tip'][$control] = $tip;

    // Call parent

    parent::initialize();

    // Call form's

    $this->form->initialize();

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set($depth) {

    // Save everything

    parent::set($depth);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_originals',$this->originals);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_local_values',$this->local_values);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_local_originals',$this->local_originals);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_redirected',$this->redirected);

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // Load everything

    parent::get($depth);
    $this->originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_originals');
    $this->local_values = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_local_values');
    $this->local_originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_local_originals');
    $this->redirected = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_redirected');

  }



  /*** Validate ***/



  //// Checks values for existance

  function existsValidate(&$errors,$intentions,$records) {

    // Use the form's own existence check

    $this->form->existsValidate($errors,$intentions,$records,$this);

  }



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check parent and relate

    parent::toValidate($errors,$intentions,$records);
    $this->form->relateValidate($errors,$intentions,$records,$this);

  }



  /*** Database ***/



  //// Sets from SQL data from the database

  function setSQL($sql,$record) {

    // $sql - The row of data returned from the DB 
    // $record - The record at which to set this data
    
    // Get the value returned 

    $this->local_values[$record] = Relations_Admin_cleanSQL($sql[$this->local_field]);

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

        break;

      case 'search':

        if ($this->sought && count($this->values[$record])) {

          // Create a values array to hold 
          // the escaped values

          $values = array();

          // Escape all values

          foreach ($this->values[$record] as $value)
            $values[] = "'" . mysql_escape_string($value) . "'";

          $mm = $this->parent_prefix . $this->name . '_mm';

          $sql->add(array(
            _from  => array($mm => "$this->mm_database.$this->mm_table"),
            _where => array(
              "$table.$this->local_field=$mm.$this->mm_local_field",
              "$mm.$this->mm_foreign_field in (" . implode(',',$values) . ")"
            )
          ));

        }

        break;

    }

  }



  //// Merges the relations from all to first

  function merge(&$totals,$records) {

    // Go through all the records

    for ($record = 1; $record < $records; $record++) {

      if (is_array($this->values[0]) && $this->values[$record])
        $this->values[0] = array_merge($this->values[0],$this->values[$record]);

      $this->values[$record] = array();

    }

    // We only want unqiue

    if (is_array($this->values[0]))
      $this->values[0] = array_unique($this->values[0]);

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals,$intentions,$records) {

    // Keep track of what was related

    $relateds = array();

    // Go through all the records 

    for ($record = 0; $record < $records; $record++) {

      // Add what we're changing

      if (is_array($relateds) && is_array($this->originals[$record]))
        $relateds = array_merge($relateds,$this->originals[$record]);

      if (is_array($relateds) && is_array($this->values[$record]))
        $relateds = array_merge($relateds,$this->values[$record]);

      // If we're updating or deleting, remove the old

      if (in_array($intentions[$record],array('update','replace','delete'))) {

        $this->form->abstract->deleteRows(
          "$this->mm_database.$this->mm_table",
          array(
            $this->mm_local_field => "'" . mysql_escape_string($this->local_originals[$record]) . "'"
          )
        );

      }

      // If there was any errors, log and return

      if (mysql_error())
        $errors[$this->prefix . $this->name . '_' . $record][] = mysql_error();

      // If we're inesrting or updating, add the new

      if (in_array($intentions[$record],array('insert','copy','update','replace'))) {

        // If there's no values to inesrt, skip

        if (!count($this->values[$record]))
          continue;

        // Create a values clause

        $values = array();

        foreach ($this->values[$record] as $value)
          $values[] = array(
            $this->mm_local_field => "'" . mysql_escape_string($this->local_values[$record]) . "'",
            $this->mm_foreign_field => "'" . mysql_escape_string($value) . "'"
          );

        // Insert the rows into the MM table

        $this->form->abstract->insertRows("$this->mm_database.$this->mm_table",$values);

        // If there was any errors, log and return

        if (mysql_error())
          $errors[$this->prefix . $this->name . '_' . $record][] = mysql_error();

      }

    }

    // Grab only uniques

    $relateds = array_unique($relateds);

    // Add them all

    foreach ($relateds as $related)
      $totals[$this->form->label]['relate'][] = $related;

    // Log everything

    $this->form->event($totals);

    // Return true since nothing went wrong

    return true;

  }



  //// Gets the labels from the database if needed
  
  function labelDB(&$errors,&$totals,$intention,$ambition,$records) {

    // Set ambition

    if ($ambition == 'select')
      $ambition = 'list';
    else
      $ambition = 'relate';

    // Check display. If we're choosing, only get
    // lablels for what we don't already have, else
    // get them all

    if (($this->display == 'choose') || ($this->display == 'file') || ($this->display == 'image')) {

      // We're choosing

      $intention = 'choose';
      $this->form->chosen = array();

      // Find all the values we don't have
      // labels for

      for ($record = 0; $record < $records; $record++)
        foreach ($this->values[$record] as $value)
          if (!isset($this->labels[$value]))
            $this->form->chosen[] = $value;

      // If nothing chosen, we're all set

      if (!count($this->form->chosen))
        return;

      // Call fromDB

      $this->form->fromDB($errors,$totals,'list','choose',$ambition);

      // Add the new labels

      foreach ($this->form->labels as $id=>$label)
        $this->labels[$id] = $label;

    } else {

      // Call fromDB

      $this->form->fromDB($errors,$totals,'list','all',$ambition);

      // Set the our labels and ids. 

      $this->labels = $this->form->labels;
      $this->ids = $this->form->ids;

    }

  }
  


  //// Receives data from the database
  
  function fromDB(&$errors,&$totals,$intention,$ambition,$records) {

    // Set originals from current

    $this->local_originals = $this->local_values;

    // Set ambition

    if ($ambition == 'select')
      $ambition = 'list';
    else
      $ambition = 'relate';

    // Go through all the local records to
    // reverse lookup local values to 
    // local records, also initialize the
    // values array.

    $local_records = array();
    for ($record = 0; $record < $records; $record++) {

      $local_records[$this->local_values[$record]] = $record;
      $this->values[$record] = array();

    }

    // Go through all records to tally all local values

    $ins = array();

    for ($record = 0; $record < $records; $record++)
      $ins[] = "'" . mysql_escape_string($this->local_values[$record]) . "'";

    // Get only uniques

    $ins = array_unique($ins);

    // Return if there's nothing to check

    if (!count($ins))
      return;

    // Configure extras

    $extras = array('local_values' => 'local_value');
      
    // Configure add 
    
    $add = array(
      _select  => array('local_value' => "$this->mm_table.$this->mm_local_field"),
      _from  => "$this->mm_database.$this->mm_table",
      _where =>  array(
        "$this->mm_table.$this->mm_local_field in (" . join(',',$ins) . ")",
        "$this->mm_table.$this->mm_foreign_field=" . $this->form->table . '.' . $this->form->id_field
      )
    );

    // Call fromDB

    $this->form->fromDB($errors,$totals,'list','custom',$ambition,$extras,$add,$set);

    // Go through all the rows and attach this value to the right record

    for ($find = 0; $find < $this->form->records; $find++)
      $this->values[$local_records[$this->form->local_values[$find]]][] = $this->form->ids[$find];

    // Make sure we only have unique values

    for ($record = 0; $record < $records; $record++)
      $this->values[$record] = array_unique($this->values[$record]);

    // Set the our labels

    $this->labels = $this->form->labels;

    // Store our originals

    $this->originals = $this->values;

  }



  /*** Process ***/



  //// Process the input's redirecting

  function redirectProcess($task,$record) {

    // Save what record redirected 

    $this->redirected = $record;

    // Create an array of redirect info

    $redirect_info = array(
      'values' => array()
    );

    // Create the redirect url

    $redirect_info['url'] = $this->form->self_url; 
    $redirect_info['values']['task'] = $task;

    // If we're inserting, add the records (assuming 1)
    // else get the id values, or set chosen

    if ($task == 'insert')
      $redirect_info['values']['records'] = Relations_Admin_grab($this->prefix  . $this->name . '_'  . $record . '_inserted',1);

    // Return the redirect info

    return $redirect_info;

  }



  //// Processes the input's returning

  function returnProcess($totals) {

    // Set the redirected record's value to the 
    // form's first chosen or inserted id value.

    if (count($totals[$this->form->label]['choose']))
      $this->values[$this->redirected] = array_unique(array_merge($this->values[$this->redirected],$totals[$this->form->label]['choose']));

    if (count($totals[$this->form->label]['insert']))
      $this->values[$this->redirected] = array_unique(array_merge($this->values[$this->redirected],$totals[$this->form->label]['insert']));

    // Call parent

    parent::returnProcess($totals);

  }



  /*** HTML ***/



  //// Prepares for display

  function prepare(&$errors,&$totals,$task,$state,$records) {

    // Call labels to make sure we have
    // what we need if we're not listing
    // finishing, or going home

    if (!in_array($state,array('list','finish','home')))
      $this->labelDB($errors,$totals,$task,$task,$records);

  }



  //// Returns HTML for URLs

  function linkHTML($record,$suffix_url) {

    // Send the form stuff back

    return $this->form->linkHTML($record,$suffix_url,$this);

  }



  //// Returns HTML for entering

  function enterHTML($record,$alive) {

    // Set the name, insert and choose code

    $name = $this->prefix . $this->name . '_' . $record;
    $insert = 'set_' . $this->prefix . 'redirect("insert","' . $this->name . '",' . $record . ')';
    $choose = 'set_' . $this->prefix . 'redirect("choose","' . $this->name . '",' . $record . ')';

    // If there's errors

    if (is_array($this->errors[$record]) && (count($this->errors[$record]) > 0))
      $errors = "Errors: " . implode(', ',$this->errors[$record]);
    else
      $errors = '';

    // Get the html

    $html = Relations_Admin_MessageHTML($this,$errors,'error');
    $html .= Relations_Admin_HelpHTML($this);

    // If there's focusing

    if ($this->focus[$record])
      $html .= "<a name='focus'></a>\n";

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

      case 'choose':
        if ($this->form->allow('choose')) 
          $html .= Relations_Admin_ChoosesHTML($this,$name,$this->labels,$this->values[$record],$choose,$this->size);
        else
          $html .= Relations_Admin_ValuesHTML($this,$this->values[$record],$this->labels);
        break;

    }
    
    // Add insert functionality if allowed

    if ($this->form->allow('insert')) {    
      $html .= "<br>\n";
      $html .= Relations_Admin_ButtonHTML($this,$name . '_insert','Insert',$insert,'insert_');
      $html .= Relations_Admin_TextHTML($this,$name . '_inserted',1,3,'inserted_');
      $html .= 'New ' . $this->label;
    }

    // Send back the html

    return $html;

  }



  //// Returns HTML for mass set

  function massHTML() {

    // Set the name, choose and changed code

    $name = $this->prefix . $this->name;
    $choose = 'set_' . $this->prefix . 'redirect("choose","' . $this->name . '",0)';
    $changed = "set_$this->prefix" . "mass(document.relations_admin_form.$name" . "_mass)";

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_mass',1,'Use for Mass',false,'mass_') . "<br>\n";

    // If there's focusing

    if ($this->focus['mass'])
      $html .= "<a name='focus'></a>\n";

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

      case 'choose':
        if ($this->form->allow('choose')) 
          $html .= Relations_Admin_ChoosesHTML($this,$name,$this->labels,$this->values[0],$choose,$this->size,'',$changed);
        else
          $html .= Relations_Admin_ValuesHTML($this,$this->values[0],$this->labels);
        break;

    }
    
    // Send back the html

    return $html;

  }



  //// Returns HTML for searching

  function searchHTML() {

    // Set the name, choose and changed code

    $name = $this->prefix . $this->name;
    $choose = 'set_' . $this->prefix . 'redirect("choose","' . $this->name . '",0)';
    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$name" . "_search)";

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',false,'search_') . "<br>\n";

    // If there's focusing

    if ($this->focus[0])
      $html .= "<a name='focus'></a>\n";

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

      case 'choose':
        if ($this->form->allow('choose')) 
          $html .= Relations_Admin_ChoosesHTML($this,$name,$this->labels,$this->values[0],$choose,$this->size,'',$changed);
        else
          $html .= Relations_Admin_ValuesHTML($this,$this->values[0],$this->labels);
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

    $data['type'] = 'MM';

    switch ($state) {

      case 'enter':

        // Add insert functionality if allowed

        if ($this->form->allow('insert')) 
          $data['countrols'][] = array(
            'name' => 'insert',
            'label' => 'Insert',
            'function' => 'set_redirect',
            'arguments' => array('insert'),
            'help' => Relations_Admin_TipData($this,'insert_button')
          );

      case 'search':

        // See what we're displaying as

        if (($this->display == 'choose') && $this->form->allow('choose'))
          $data['countrols'][] = array(
            'name' => 'choose',
            'label' => 'Choose',
            'function' => 'set_redirect',
            'arguments' => array('choose'),
            'help' => Relations_Admin_TipData($this,'choose_button')
          );
            
      case 'link':
        $data['settings']['self_url'] = $this->form->self_url;
        break;


        
    }

    return $data;

  }

}

?>