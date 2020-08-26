<?php

require_once('Relations.php');

class Relations_Family_Member {
	
  //// Create a Relations_Family_Member object. This
  //// object is a list of values to select from.

  function Relations_Family_Member() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get the field, table, where clause sent

    list(
      $this->name,
      $this->label,
      $this->database,
      $this->table,
      $this->id_field,
      $this->query,
      $this->alias
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'QUERY',
      'ALIAS'
    ),$arg_list);

    if (!(strlen($this->alias) > 0))
      $this->alias = $this->name;

    // Intialize relationships

    $this->parents = array();
    $this->children = array();
    $this->brothers = array();
    $this->sisters = array();

    // Initialize chosen ids and labels

    $this->chosen_count = 0;
    $this->chosen_ids_string = '';
    $this->chosen_ids_array = array();
    $this->chosen_ids_select = array();

    $this->chosen_labels_string = '';
    $this->chosen_labels_array = array();
    $this->chosen_labels_hash = array();
    $this->chosen_labels_select = array();

    // Initialize available ids and labels

    $this->available_count = 0;
    $this->available_ids_array = array();
    $this->available_ids_select = array();

    $this->available_labels_array = array();
    $this->available_labels_hash = array();
    $this->available_labels_select = array();

    // Initialize all selection settings 

    $this->filter = '';
    $this->match = 0;
    $this->group = 0;
    $this->limit = '';
    $this->ignore = 0;

  }



  //// Returns html info about the Relations::Family::Member 
  //// object. Useful for debugging and export purposes.

  function toHTML() {

    // Create a html string to hold everything

    $html = '<ul>';

    // 411

    $html .= "<b>Relations::Family::Member: $this</b>";
    $html .= "<li>Name: $this->name</li>";
    $html .= "<li>Label: $this->label</li>";
    $html .= "<li>Database: $this->database</li>";
    $html .= "<li>Table: $this->table</li>";
    $html .= "<li>Alias: $this->alias</li>";
    $html .= "<li>ID Field: $this->id_field</li>";
    $html .= "<li>Query:</li>";

    $html .= $this->query->toHTML();

    $html .= "<li>Chosen Count: $this->chosen_count</li>";
    $html .= "<li>Chosen IDs and Labels: </li>";

    $html .= "<ul>";

    foreach ($this->chosen_ids_array as $id) {

      $html .= "<li>ID: $id ";
      $html .= "Label: " . $this->chosen_labels_hash[$id] . "</li>";

    }

    $html .= "</ul>";

    $html .= "<li>Filter:  $this->filter</li>";
    $html .= "<li>Match:  $this->match</li>";
    $html .= "<li>Group:  $this->group</li>";
    $html .= "<li>Limit:  $this->limit</li>";
    $html .= "<li>Ignore:  $this->ignore</li>";

    $html .= "<li>Available IDs and Labels: </li>";

    $html .= "<ul>";

    foreach ($this->available_ids_array as $id) {

      $html .= "<li>ID: $id ";
      $html .= "Label: $this->available_labels_hash->$id</li>";

    }

    $html .= "</ul>";

    $html .= "<li>Parents: </li>";

    $html .= "<ul>";

    foreach ($this->parents as $lineage) {

      $html .= "<li>Name: $lineage->parent_name</li>";

    }

    $html .= "</ul>";

    $html .= "<li>Children: </li>";

    $html .= "<ul>";

    foreach ($this->children as $lineage) {

      $html .= "<li>Name: $lineage->child_name</li>";

    }

    $html .= "</ul>";

    $html .= "<li>Brothers: </li>";

    $html .= "<ul>";

    foreach ($this->brothers as $rivalry) {

      $html .= "<li>Name: $rivalry->brother_name</li>";

    }

    $html .= "</ul>";

    $html .= "<li>Sisters: </li>";

    $html .= "<ul>";

    foreach ($this->sisters as $rivalry) {

      $html .= "<li>\Name: $rivalry->sister_name</li>";

    }

    $html .= "</ul>";

    // Return the html

    return $html;

  }

}

?>