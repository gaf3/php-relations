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

class Relations_Admin_ChildDB extends Relations_Admin_MM {



  /*** Create ***/



  //// Constructor

  function Relations_Admin_ChildDB() {

    /* 
    
      $name - The name of the input in PHP
      $label - The label of the input (pretty format)
      $local_field - The parent field in this table (use primary)
      $foreign_field - The child field in the child table (connect to parent)
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



  //// Grabs input from html for search

  function sought() {

    // De nada

  }



  /*** Storage ***/



  //// Stores info not entered by the user

  function set($depth) {

    // Save everything

    parent::set($depth);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_values',$this->values);
    Relations_Admin_store($depth . '_' . $this->prefix . $this->name . '_originals',$this->originals);

  }



  //// Retrieves info not entered by the user

  function get($depth) {

    // Load everything

    parent::get($depth);
    $this->values = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_values');
    $this->originals = Relations_Admin_retrieve($depth . '_' . $this->prefix . $this->name . '_originals');

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
        $ins[] = $this->form->childSQL($this->descendants,$this->foreign_field,$this->local_originals[$record]);

    // Return if there's nothing

    if (!count($ins))
      return;

    // Get only uniques

    $ins = array_unique($ins);

    // Create the where

    $where = $this->form->childWhere($this->descendants,$this->foreign_field,$ins);

    // Call the forms makeDB function

    $this->form->makeDB();

    // See if there's any records

    $parents = Relations_toHash($this->form->abstract->selectColumn(array(
      _field => $this->foreign_field,
      _query => new Relations_Query(array(
        _select  => $this->foreign_field,
        _from    => $this->form->database . '.' . $this->form->table,
        _where   => $where,
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



  //// Makes sure everything is valid for toDB

  function toValidate(&$errors,$intentions,$records) {

    // $errors - Array of errors to add to
    // $intentions - The intentions of the records
    // $records - The records to check
    
    // Check input and children

    Relations_Admin_Input::toValidate($errors,$intentions,$records);
    $this->childrenValidate($errors,$intentions,$records);


  }



  /*** Database ***/



  //// Builds SQL data to receive from the database

  function fromSQL(&$sql,$intention,$ambition,$table,$record) {

    // De nada

  }



  //// Sends data to the database
  
  function toDB(&$errors,&$totals,$intentions,$records) {

    // Return true since nothing went wrong

    return true;

  }



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

          $this->form->fromDB($errors,$totals,'list','custom','list');
          $this->values[$record] = $this->form->ids;

        }

      }

    } else {

      // Go through all records to tally all local values

      $ins = array();
      for ($record = 0; $record < $records; $record++)
        $ins[] = $this->form->childSQL($this->descendants,$this->form->table . ".$this->foreign_field",$this->local_originals[$record]);

      // Get only uniques

      $ins = array_unique($ins);

      // Return if there's nothing to check

      if (!count($ins))
        return;

      // Create the where

      $where = $this->form->childWhere($this->descendants,$this->form->table . ".$this->foreign_field",$ins);

      // Configure extras

      $extras = array('local_originals' => 'local_value');
        
      // Configure add 
      
      $add = array(
        _select => array('local_value' => $this->form->table . ".$this->foreign_field"),
        _where  => $where
      );

      // Configure set 
      
      if ($this->display == 'count')
        $set = array(_group_by => $this->form->table . ".$this->foreign_field");
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

    }

    // Set the our labels

    $this->labels = $this->form->labels;

  }



  //// Receives data from the database
  
  function fromDB(&$errors,&$totals,$intention,$ambition,$records) {

    // Set originals from current

    $this->local_originals = $this->local_values;

    // Call labelDB

    $this->labelDB($errors,$totals,$intention,$ambition,$records);

    // Set originals from current

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

    if ($task == 'insert') {

      $redirect_info['values'][$this->foreign_input] = $this->local_values[$record];
      $redirect_info['values']['records'] = Relations_Admin_grab($this->prefix  . $this->name . '_'  . $record . '_inserted',1);

    } else {

      $redirect_info['values']['chosen'] = $this->values[$record];

    }

    // Return the redirect info

    return $redirect_info;

  }



  //// Adds our info to the children update infos

  function updateInfosProcess(&$infos,$intentions,$records) {

    // Take off if this is count (bug I know)

    if ($this->display == 'count')
      return;

    // Create the chosen and replacements array

    $chosen = array();
    $replacements = array();

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // If we don't have any values, skip

      if (!count($this->values[$record]))
        continue;

      // If we're not updating, skip

      if ($intentions[$record] != 'update')
        continue;

      // If the original's current, skip

      if ($this->local_originals[$record] == $this->local_values[$record])
        continue;

      // Add to chosen and replacements

      foreach ($this->values[$record] as $value)
        $chosen[] = $value;

      $replacements[$this->local_values[$record]] = array($this->form->childReplace($this->descendants,$this->local_originals[$record]));

    }

    // If we have no replacements, take off

    if (!count($replacements))
      return;

    // Figure out if the record we're going
    // to is already in the mix

    $found = false;

    for ($info = 0; $info < count($infos); $info++) {

      // If it's the same url

      if ($infos[$info]['url'] == $this->form->self_url) {

        // We found it, so add our chosen replacements

        $found = true;
        $infos[$info]['values']['chosen'] = array_values(array_unique(array_merge($infos[$info]['values']['chosen'],$chosen)));
        $infos[$info]['values'][$this->foreign_input . '_replacements'] = $replacements;

      }

    }

    // If we didn't find it

    if (!$found) {

      // Create our info

      $info = array(
        'url'    => $this->form->self_url,
        'values' => array( 
          'task'   => 'update',
          'chosen' => array_values(array_unique($chosen)),
          $this->foreign_input . '_replacements' => $replacements
        )
      );

      // Add it to all

      $infos[] = $info;

    }

  }



  //// Adds our info to the children copy infos

  function copyInfosProcess(&$infos,$intentions,$records) {

    // Take off if this is count or
    // descendants are prefixed

    if (($this->display == 'count') || ($this->descendants == 'prefix'))
      return;

    // Create the chosen and replacements array

    $chosen = array();
    $replacements = array();

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // If we don't have any originals, skip

      if (!count($this->originals[$record]))
        continue;

      // If we're not copying, skip

      if ($intentions[$record] != 'copy')
        continue;

      // Add to chosen and replacements

      foreach ($this->originals[$record] as $value)
        $chosen[] = rawurlencode($value);

      $replacements[$this->local_values[$record]] = array($this->form->childReplace($this->descendants,$this->local_originals[$record]));

    }

    // If we have no replacements, take off

    if (!count($replacements))
      return;

    // Figure out if the record we're going
    // to is already in the mix

    $found = false;

    for ($info = 0; $info < count($infos); $info++) {

      // If it's the same url

      if ($infos[$info]['url'] == $this->form->self_url) {

        // We found it, so add our chosen replacements

        $found = true;
        $infos[$info]['values']['chosen'] = array_values(array_unique(array_merge($infos[$info]['values']['chosen'],$chosen)));
        $infos[$info]['values'][$this->foreign_input . '_replacements'] = $replacements;

      }

    }

    // If we didn't find it

    if (!$found) {

      // Create our info

      $info = array(
        'url'    => $this->form->self_url,
        'values' => array( 
          'task'   => 'copy',
          'chosen' => array_values(array_unique($chosen)),
          $this->foreign_input . '_replacements' => $replacements
        )
      );

      // Add it to all

      $infos[] = $info;

    }

  }



  //// Adds our info to the children replace infos

  function replaceInfosProcess(&$infos,$intentions,$records) {

    // Take off if this is count (bug I know)

    if ($this->display == 'count')
      return;

    // Create the chosen and replacements array

    $chosen = array();
    $replacements = array();

    // Go through all records

    for ($record = 0; $record < $records; $record++) {

      // If we don't have any originals, skip

      if (!count($this->originals[$record]))
        continue;

      // If we're not updating or replacing, skip

      if (($intentions[$record] != 'update') &&
          ($intentions[$record] != 'replace'))
        continue;

      // Add to chosen and replacements

      foreach ($this->originals[$record] as $value)
        $chosen[] = rawurlencode($value);

      // Set the replacements to an array if neccessary

      if (!is_array($replacements[$this->local_values[0]]))
        $replacements[$this->local_values[0]] = array();

      $replacements[$this->local_values[$record]] = array($this->form->childReplace($this->descendants,$this->local_originals[$record]));

    }

    // If we have no replacements, take off

    if (!count($replacements))
      return;

    // Figure out if the record we're going
    // to is already in the mix

    $found = false;

    for ($info = 0; $info < count($infos); $info++) {

      // If it's the same url

      if ($infos[$info]['url'] == $this->form->self_url) {

        // We found it, so add our chosen replacements

        $found = true;
        $infos[$info]['values']['chosen'] = array_values(array_unique(array_merge($infos[$info]['values']['chosen'],$chosen)));
        $infos[$info]['values'][$this->foreign_input . '_replacements'] = $replacements;

      }

    }

    // If we didn't find it

    if (!$found) {

      // Create our info

      $info = array(
        'url'    => $this->form->self_url,
        'values' => array( 
          'task'   => 'update',
          'chosen' => array_values(array_unique($chosen)),
          $this->foreign_input . '_replacements' => $replacements
        )
      );

      // Add it to all

      $infos[] = $info;

    }

  }



  /*** HTML ***/



  //// Returns HTML for viewing

  function viewHTML($record) {

    // Only return something if we have locals

    if (strlen($this->local_values[$record])) {

      // If we're not count, send the values,
      // else just the count

      if ($this->display != 'count') {
        return Relations_Admin_ValuesHTML($this,$this->values[$record],$this->labels);
      } elseif ($this->values[$record]) {
        return Relations_Admin_ValueHTML($this,$this->values[$record] . ' Records');
      } else {
        return '';
      }

    }

  }



  //// Returns HTML for URLs

  function linkHTML($record,$suffix_url) {

    // Only return something if we have locals

    if (strlen($this->local_values[$record])) {

      // If we're not count, send the links,
      // else just the count

      if ($this->display != 'count') {
        return $this->form->linkHTML($record,$suffix_url,$this);
      } elseif ($this->values[$record]) {
        return Relations_Admin_ValueHTML($this,$this->values[$record] . ' Records');
      } else {
        return '';
      }

    } 

  }



  //// Returns HTML for entering

  function enterHTML($record,$alive) {

    // If we don't have local values, do nothing

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
        (($this->display != 'count') && count($this->values[$record])))
      $html .= Relations_Admin_ButtonHTML($this,$name . '_select','Select',$select,'select_') . '<br>';
   
    // Send the value

    $html .= $this->viewHTML($record);

    // If we have local values, add insert stuff

    if (strlen($this->local_values[$record])) {
      $html .= Relations_Admin_ButtonHTML($this,$name . '_insert','Insert',$insert,'insert_');
      $html .= Relations_Admin_TextHTML($this,$name . '_inserted',1,3,'inserted_');
    }

    // Send back the html

    return $html;

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

    // Call parent 

    $data = Relations_Admin_Input::inputXML($state,$records,$extra);

    // Figure our what to set

    $data['type'] = 'ChildDB';
    $data['settings']['display'] = $this->display;
    $data['settings']['editable'] = 0;
    $data['settings']['searchable'] = 0;
    $data['settings']['structure'] = 'Array';

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