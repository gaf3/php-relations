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

class Relations_Admin_DB extends Relations_Admin_List {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_DB() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $field - The field in the table
      $form - The form of the lookup
      $display - The type of HTML input
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
      $this->field,
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
      'FIELD',
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
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_redirected',$this->redirected);

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // Load everything

    parent::get($depth);
    $this->originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_originals');
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



  //// Builds SQL data to receive from the database

  function fromSQL(&$sql,$intention,$ambition,$table,$record) {

    // Check the reason we're getting data

    switch ($intention) {

      case 'select':

        $sql->add(array(_where => "$table.$this->field='" . mysql_escape_string($this->values[$record]) . "'"));
        break;

      case 'search':

        if ($this->sought)
          $sql->add(array(_where => "$table.$this->field='" . mysql_escape_string($this->values[$record]) . "'"));
        break;

    }

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals,$intentions,$records) {

    // Keep track of what was related

    $relateds = array();

    // Go through all the records 

    for ($record = 0; $record < $records; $record++) {

      // Skip if we're ignoring

      if ($intentions[$record] == 'ignore')
        continue;

      // Add what we're changing

      $relateds[] = $this->originals[$record];
      $relateds[] = $this->values[$record];

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

      // If nothing set, we're all set

      if (!is_array($this->values))
        return;

      // We're choosing

      $this->form->chosen = array();

      // Find all the values we don't have
      // labels for

      foreach ($this->values as $value)
        if (!isset($this->labels[$value]))
          $this->form->chosen[] = $value;

      // If nothing chosen, we're all set

      if (!count($this->form->chosen))
        return;

      // Call fromDB

      $this->form->fromDB($errors,$totals,'list','choose',$ambition);

      // If nothing there, we're all set

      if (!is_array($this->form->labels))
        return;

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

    // Set ambition

    if ($ambition == 'select')
      $ambition = 'list';
    else
      $ambition = 'relate';

    // Set chosen as values with only uniques

    $this->form->chosen = $this->values;

    // Call labelDB

    $this->form->fromDB($errors,$totals,'list','choose',$ambition);

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
    $redirect_info['values']['single'] = 1;

    // Return the redirect info

    return $redirect_info;

  }



  //// Processes the input's returning

  function returnProcess($totals) {

    // Set the redirected record's value to the 
    // form's first chosen or inserted id value.

    if ($totals[$this->form->label]['choose'][0])
      $this->values[$this->redirected] = $totals[$this->form->label]['choose'][0];

    if ($totals[$this->form->label]['insert'][0])
      $this->values[$this->redirected] = $totals[$this->form->label]['insert'][0];

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



  //// Returns the script code

  function scriptJS(&$functions) {

    // Add the clear function

    Relations_Admin_ClearChooseJS($functions);

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
    $clear = 'clear_choose(document.relations_admin_form.' . $name . ')';

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

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[$record],$this->size);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[$record],$this->size);
        break;

      case 'image':

        if ($this->values[$record]) {

          $image_src = $this->form->prefix_url . str_replace('%2F','/',rawurlencode($this->values[$record]));
          $html .= Relations_Admin_ImageHTML($this,$image_src,'','60',$value,'') . '<br>';

        }

      case 'file':

        if ($this->values[$record] && $this->form->allow('choose')) {

          $html .=  Relations_Admin_ButtonHTML($this,$name . '_clear','Clear',$clear,'clear_');

        }

      case 'choose':
        if ($this->form->allow('choose')) {
          $html .= Relations_Admin_ChooseHTML($this,$name,$this->labels,$this->values[$record],$choose);
        } else {
          $html .= Relations_Admin_HiddenHTML($this,$name,$this->values[$record]);
          $html .= Relations_Admin_ValueHTML($this,$this->labels[$this->values[$record]]);
        }
        break;

    }

    // Add insert functionality if allowed

    if ($this->form->allow('insert')) {    
      $html .=  Relations_Admin_ButtonHTML($this,$name . '_insert','Insert',$insert,'insert_');
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
    $clear = 'clear_choose(document.relations_admin_form.' . $name . ')';

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_mass',1,'Use for Mass',false,'mass_') . "<br>\n";

    // If there's focusing

    if ($this->focus['mass'])
      $html .= "<a name='focus'></a>\n";

    // See what we're displaying as

    switch ($this->display) {

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'image':

        if ($this->values[0]) {

          $image_src = $this->form->prefix_url . str_replace('%2F','/',rawurlencode($this->values[0]));
          $html .= Relations_Admin_ImageHTML($this,$image_src,'','60',$value,'') . '<br>';

        }

      case 'file':

        if ($this->values[0] && $this->form->allow('choose')) {

          $html .=  Relations_Admin_ButtonHTML($this,$name . '_clear','Clear',$clear,'clear_');

        }

      case 'choose':

        if ($this->form->allow('choose')) {
          $html .= Relations_Admin_ChooseHTML($this,$name,$this->labels,$this->values[0],$choose,'',$changed);
        } else {
          $html .= Relations_Admin_HiddenHTML($this,$name,$this->values[0]);
          $html .= Relations_Admin_ValueHTML($this,$this->labels[$this->values[0]]);
        }

        break;

    }
    
    // Send back the html

    return $html;

  }



  //// Returns HTML for searching

  function searchHTML() {

    // Set the name, choose and changed code

    $name = $this->prefix . $this->name;
    $changed = "set_$this->prefix" . "search(document.relations_admin_form.$name" . "_search)";
    $choose = 'set_' . $this->prefix . 'redirect("choose","' . $this->name . '",0)';
    $clear = 'clear_choose(document.relations_admin_form.' . $name . ')';

    // Get the html

    $html = Relations_Admin_CheckboxHTML($this,$name . '_search',1,'Use in Search',$this->sought,'search_') . "<br>\n";

    // If there's focusing

    if ($this->focus[0])
      $html .= "<a name='focus'></a>\n";

    // See what we're displaying as

    switch ($this->display) {

      case 'radios':
        $html .= Relations_Admin_RadiosHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'select':
        $html .= Relations_Admin_SelectHTML($this,$name,$this->ids,$this->labels,$this->values[0],$this->size,'',$changed);
        break;

      case 'image':

        if ($this->values[0]) {

          $image_src = $this->form->prefix_url . str_replace('%2F','/',rawurlencode($this->values[0]));
          $html .= Relations_Admin_ImageHTML($this,$image_src,'','60',$value,'') . '<br>';

        }

      case 'file':

        if ($this->values[0] && $this->form->allow('choose')) {

          $html .=  Relations_Admin_ButtonHTML($this,$name . '_clear','Clear',$clear,'clear_');

        }

      case 'choose':
        if ($this->form->allow('choose')) {
          $html .= Relations_Admin_ChooseHTML($this,$name,$this->labels,$this->values[0],$choose,'',$changed);
        } else {
          $html .= Relations_Admin_HiddenHTML($this,$name,$this->values[0]);
          $html .= Relations_Admin_ValueHTML($this,$this->labels[$this->values[0]]);
        }

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

    $data['type'] = 'DB';

    switch ($state) {

      case 'enter':

        // Add insert functionality if allowed

        if ($this->form->allow('insert')) 
          $data['controls'][] = array(
            'name' => 'insert',
            'label' => 'Insert',
            'function' => 'set_redirect',
            'arguments' => array('insert'),
            'help' => Relations_Admin_TipData($this,'insert_button')
          );

      case 'search':

        // See what we're displaying as

        if (in_array($this->display,array('image','file')) && $this->form->allow('choose')) 
          $data['controls'][] = array(
            'name' => 'clear',
            'label' => 'Clear',
            'function' => 'set_clear',
            'arguments' => array(),
            'help' => Relations_Admin_TipData($this,'clear_button')
          );
        
        if (in_array($this->display,array('image','file','choose')) && $this->form->allow('choose')) 
            $data['controls'][] = array(
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