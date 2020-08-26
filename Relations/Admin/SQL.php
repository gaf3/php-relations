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

class Relations_Admin_SQL extends Relations_Admin_ChildDB {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_SQL() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $local_field - The parent field in this table
      $foreign_field - The child field in the query 
      $query - The query bits to lookup other values
      $form - The form of the lookup
      $display - The display in HTML
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
      $this->query,
      $this->form,
      $this->display,
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
      'QUERY',
      'FORM',
      'DISPLAY',
      'FORBIDS',
      'HELPS',
      'STYLES',
      'CLASSES',
      'ELEMENTS'
    ),$arg_list);

  }



  /*** Validate ***/



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check input

    Relations_Admin_Input::toValidate($errors,$intentions,$records);


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

    // Go through all records to tally all local values

    $ins = array();

    for ($record = 0; $record < $records; $record++)
      $ins[] = "'" . mysql_escape_string($this->local_originals[$record]) . "'";

    // Get only uniques

    $ins = array_unique($ins);

    // Return if there's nothing to check

    if (!count($ins))
      return;

    // Copy our query

    $query = clone $this->query;

    // Add our stuff
    
    $query->add(array(
      _select  => array('local_value' => "$this->foreign_field"),
      _where =>  array(
        "$this->foreign_field in (" . join(',',$ins) . ")",
      )
    ));

    // Configure extras

    $extras = array('local_originals' => 'local_value');
      
    // Configure add 

    $add = array();

    if (strlen($query->select))
      $add['_select'] = $query->select;

    if (strlen($query->from))
      $add['_from'] = $query->from;

    if (strlen($query->where))
      $add['_where'] = $query->where;

    if (strlen($query->group_by))
      $add['_group_by'] = $query->group_by;

    if (strlen($query->having))
      $add['_having'] = $query->having;

    if (strlen($query->order_by))
      $add['_order_by'] = $query->order_by;

    if (strlen($query->limit))
      $add['_limit'] = $query->limit;

    if (strlen($query->options))
      $add['_options'] = $query->options;

    // Configure set 
    
    if ($this->display == 'count')
      $set = array(_group_by => $this->foreign_field);
    else
      $set = array();

    // Configure purpose

    if ($this->display == 'count')
      $purpose = 'count';
    else
      $purpose = 'list';

    // Call fromDB

    $this->form->fromDB($errors,$totals,$purpose,'custom','list',$extras,$add,$set);

    // Go through all the rows and attach this value to the right record

    for ($find = 0; $find < count($this->form->local_originals); $find++)
      if ($this->display != 'count')
        $this->values[$local_records[$this->form->local_originals[$find]]][] = $this->form->ids[$find];
      else
        $this->values[$local_records[$this->form->local_originals[$find]]] += $this->form->counts[$find];

    // Make sure we only have unique values

    if ($this->display != 'count')
      for ($record = 0; $record < $records; $record++)
        $this->values[$record] = array_unique($this->values[$record]);

    // Set the our labels

    if ($this->display != 'count')
      $this->labels = $this->form->labels;

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
    $redirect_info['values']['chosen'] = $this->values[$record];

    // Return the redirect info

    return $redirect_info;

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



  //// Returns HTML for entering

  function enterHTML($record,$alive) {

    // If we don't have local values, add insert stuff

    if (!strlen($this->local_values[$record]))
      return '';

    // Set the name, and select and insert functions

    $name = $this->prefix . $this->name . '_' . $record;
    $select = 'set_' . $this->prefix . 'redirect("select","' . $this->name . '",' . $record . ');';
    $insert = 'set_' . $this->prefix . 'redirect("insert","' . $this->name . '",' . $record . ');';

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

    // If we have values, send a select button

    if ((($this->display == 'count') && strlen($this->values[$record])) || 
        (($this->display != 'count') && count($this->values[$record]))) {

      // Add select functionality if allowed

      if ($this->form->allow('select')) {    
        $html .= Relations_Admin_ButtonHTML($this,$name . '_select','Select',$select,'select_');
      }

      // If we're not count, throw a separator in

      if ($this->display != 'count')
        $html .= "<br>\n";

    }

    // Send the value

    $html .= $this->viewHTML($record);

    // Send back the html

    return $html;

  }

  //// Retutns the input XML

  function inputXML($state,$records,$extra=false) {

    // Call parent 

    $data = Relations_Admin_Input::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'SQL';
    $data['settings']['display'] = $this->display;
    $data['settings']['structure'] = 'Array';

    switch ($state) {

      case 'enter':

        if ($this->form->allow('select')) 
          $data['countrols'][] = array(
            'name' => 'select',
            'label' => 'Select',
            'function' => 'set_redirect',
            'arguments' => array('select'),
            'help' => Relations_Admin_TipData($this,'select_button')
          );

      case 'link':

        $data['settings']['self_url'] = $this->form->self_url;

      case 'view':
      case 'browse':
      case 'link':
      case 'preview':

        if ($this->display != 'count')
          $data['options'] = $this->labels;

    }

    return $data;

  }

}

?>