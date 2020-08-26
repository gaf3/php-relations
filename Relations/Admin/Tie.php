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
require_once('Relations/Admin/MM.php');

class Relations_Admin_Tie extends Relations_Admin_MM {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_Tie() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $local_field - The id field in this table
      $tie_value - The value indicating this relationship
      $tie_database - The tie database
      $tie_table - The tie table
      $tie_value_field - The tie relationship field
      $tie_local_field - The tie local field field 
      $tie_foreign_field - The tie foreign field
      $form - The form of the lookup
      $display - The type of HTML display
      $size - The size of the selected list
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
      $this->tie_value,
      $this->tie_database,
      $this->tie_table,
      $this->tie_value_field,
      $this->tie_local_field,
      $this->tie_foreign_field,
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
      'TIE_VALUE',
      'TIE_DATABASE',
      'TIE_TABLE',
      'TIE_VALUE_FIELD',
      'TIE_LOCAL_FIELD',
      'TIE_FOREIGN_FIELD',
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



  /*** Database ***/



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

          $tie = $this->parent_prefix . $this->name . '_tie';

          $sql->add(array(
            _from  => array($tie => "$this->tie_database.$this->tie_table"),
            _where => array(
              "$table.$this->local_field=$tie.$this->tie_local_field",
              "$tie.$this->tie_value_field='$this->tie_value'",
              "$tie.$this->tie_foreign_field in (" . implode(',',$values) . ")"
            )
          ));

        }

        break;

    }

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
          "$this->tie_database.$this->tie_table",
          array(
            $this->tie_value_field => "'" . mysql_escape_string($this->tie_value) . "'",
            $this->tie_local_field => "'" . mysql_escape_string($this->local_originals[$record]) . "'"
          )
        );

      }

      // If there was any errors, log and return

      if (mysql_error()) {

        $errors[$this->prefix . $this->name . '_' . $record][] = mysql_error();
        return false;

      }

      // If we're inesrting or updating, add the new

      if (in_array($intentions[$record],array('insert','copy','update','replace'))) {

        // If there's no values to inesrt, skip

        if (!count($this->values[$record]))
          continue;

        // Create a values clause

        $values = array();

        foreach ($this->values[$record] as $value)
          $values[] = array(
            $this->tie_value_field => "'" . mysql_escape_string($this->tie_value) . "'",
            $this->tie_local_field => "'" . mysql_escape_string($this->local_values[$record]) . "'",
            $this->tie_foreign_field => "'" . mysql_escape_string($value) . "'"
          );

        // Insert the rows into the Tie table

        $this->form->abstract->insertRows("$this->tie_database.$this->tie_table",$values);

        // If there was any errors, log and return

        if (mysql_error()) {

          $errors[$this->prefix . $this->name . '_' . $record][] = mysql_error();
          return false;

        }

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
      _select  => array('local_value' => "$this->tie_table.$this->tie_local_field"),
      _from  => "$this->tie_database.$this->tie_table",
      _where =>  array(
        "$this->tie_table.$this->tie_value_field='" . mysql_escape_string($this->tie_value) . "'",
        "$this->tie_table.$this->tie_local_field in (" . join(',',$ins) . ")",
        "$this->tie_table.$this->tie_foreign_field=" . $this->form->table . '.' . $this->form->id_field
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

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'Tie';

    return $data;

  }

}

?>