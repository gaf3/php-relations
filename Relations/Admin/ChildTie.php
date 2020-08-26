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
require_once('Relations/Admin/ChildDB.php');

class Relations_Admin_ChildTie extends Relations_Admin_ChildDB {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_ChildTie() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $local_field - The parent field in this table (use primary)
      $foreign_field - The child field in the child table (connect to parent)
      $tie_value - The value indicating this relationship
      $tie_database - The tie database
      $tie_table - The tie table
      $tie_value_field - The tie relationship field
      $tie_local_field - The tie local field field 
      $tie_foreign_field - The tie foreign field
      $form - The form of the lookup
      $foreign_input - The child input in the child form (connect to parent)
      $display - The display HTML
      $size - The size of the display
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
      $this->foreign_field,
      $this->tie_value,
      $this->tie_database,
      $this->tie_table,
      $this->tie_value_field,
      $this->tie_local_field,
      $this->tie_foreign_field,
      $this->form,
      $this->foreign_input,
      $this->display,
      $this->size,
      $this->forbids,
      $this->helps,
      $this->styles,
      $this->classes,
      $this->elements
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'LOCAL_FIELD',
      'FOREIGN_FIELD',
      'TIE_VALUE',
      'TIE_DATABASE',
      'TIE_TABLE',
      'TIE_VALUE_FIELD',
      'TIE_LOCAL_FIELD',
      'TIE_FOREIGN_FIELD',
      'FORM',
      'FOREIGN_INPUT',
      'DISPLAY',
      'SIZE',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

  }



  /*** Validate ***/



  //// Checks values to see if children exist

  function childrenValidate(&$errors,$intentions,$records) {

    // Create an array of parents we 
    // care about

    $ins = array();

    // Go through all the records and add if
    // we deleting

    for ($record = 0; $record < $records; $record++)
      if ($intentions[$record] == 'delete')
        $ins[] = $this->form->childSQL($this->descendants,$this->tie_local_field,$this->local_originals[$record]);

    // Return if there's nothing

    if (!count($ins))
      return;

    // Get only uniques

    $ins = array_unique($ins);

    // Create the where

    $where = $this->form->childWhere($this->descendants,$this->tie_local_field,$ins);

    // See if there's any records

    $parents = Relations_toHash($this->form->abstract->selectColumn(array(
      _field => $this->tie_local_field,
      _query => new Relations_Query(array(
        _select  => $this->tie_local_field,
        _from    => $this->tie_database . '.' . $this->tie_table,
        _where   => array(
          $this->tie_value_field . "='" . mysql_escape_string($this->tie_value) . "'",
          $where
        ),
        _options => 'distinct'
      ))
    )));

    // Return if there's nothing

    if (!count($parents))
      return;

    // Go through all the records and indicate
    // if there was children found

    for ($record = 0; $record < $records; $record++)
      if (($intentions[$record] == 'delete') && $this->form->childExists($this->descendants,$parents,$this->local_originals[$record]))
        $errors[$this->prefix . $this->name . '_' . $record][] = 'Children still exist';

  }



  /*** Database ***/



  //// Gets the labels from the database
  
  function labelDB(&$errors,&$totals,$intention,$ambition,$records) {

    // Go through all the local records to
    // reverse lookup local values to 
    // local records, also initialize the
    // values array.

    $local_records = array();
    for ($record = 0; $record < $records; $record++) {

      $local_records[$this->local_originals[$record]] = $record;

      if ($this->display != 'count')
        $this->values[$record] = array();
      else
        $this->values[$record] = 0;

    }

    if ($this->descendants == 'oprefix') {

      for ($record = 0; $record < $records; $record++) {

        $this->form->above = $this->local_originals[$record];

        // Get matching 
        
        if ($this->display == 'count') {

          $this->form->fromDB($errors,$totals,'count','custom','list');
          $this->values[$record] = $this->form->counts[0];

        } else {

          $this->form->fromDB($errors,$totals,'count','custom','list');
          $this->values[$record] = $this->form->ids;

        }

      }

    } else {

      // Go through all records to tally all local values

      $ins = array();
      for ($record = 0; $record < $records; $record++)
        $ins[] = $this->form->childSQL($this->descendants,"$this->tie_table.$this->tie_local_field",$this->local_originals[$record]);

      // Get only uniques

      $ins = array_unique($ins);

      // Return if there's nothing to check

      if (!count($ins))
        return;

      // Create the where

      $where = $this->form->childWhere($this->descendants,"$this->tie_table.$this->tie_local_field",$ins);

      // Configure extras

      $extras = array('local_originals' => 'local_value');
        
      // Configure add 
      
      $add = array(
        _select  => array('local_value' => "$this->tie_table.$this->tie_local_field"),
        _from  => "$this->tie_database.$this->tie_table",
        _where =>  array(
          "$this->tie_table.$this->tie_value_field='" . mysql_escape_string($this->tie_value) . "'",
          $where,
          "$this->tie_table.$this->tie_foreign_field=" . $this->form->table . '.' . $this->form->id_field
        )
      );

      // Configure set 
      
      if ($this->display == 'count')
        $set = array(_group_by => "$this->tie_table.$this->tie_local_field");
      else
        $set = array();

      // Configure purpose

      if ($this->display == 'count')
        $purpose = 'count';
      else
        $purpose = 'list';

      // Call fromDB

      $this->form->fromDB($errors,$totals,$purpose,'custom','list',$extras,$add,$set);

      // Set the values

      $values = $this->form->childValues($this->descendants,$this->display,$local_records,$this->local_originals);
      foreach ($values as $record=>$value)
        $this->values[$record] = $value;

      // Make sure we only have unique values

      if ($this->display != 'count')
        for ($record = 0; $record < $records; $record++)
          $this->values[$record] = array_unique($this->values[$record]);

    }

    // Set the our labels

    $this->labels = $this->form->labels;

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = parent::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'ChildTie';

    return $data;

  }

}

?>