<?php

require_once('Relations.php');

class Relations_Family_Lineage {
	
  //// Create a Relations_Family_Lineage object. This object 
  //// holds sql value and which members are needed to 
  //// create this value.

  function Relations_Family_Lineage($parent_name,$parent_field,$child_name,$child_field) {

    // $parent_name - Parent family name (one)
    // $parent_field  - Parent member field used as a foreign key
    // $child_name  - Child family name (many)
    // $child_field   - Child member field using the foreign key

    $this->parent_name = $parent_name;
    $this->parent_field = $parent_field;
    $this->child_name = $child_name;
    $this->child_field = $child_field;

  }



  //// Returns html info about the Relations_Family_Lineage 
  //// object. Useful for debugging and export purposes.

  function toHTML() {

    // Create a html string to hold everything

    $html = '';

    // 411

    $html .= "<b>Relations_Family_Lineage: $this</b>";

    $html .= "<ul>";

    $html .= "<li>Parent Name: $this->parent_name</li>";
    $html .= "<li>Parent Field: $this->parent_field</li>";

    $html .= "<li>Child Name: $this->child_name</li>";
    $html .= "<li>Child Field: $this->child_field</li>";

    $html .= "</ul>";

    // Return the html

    return $html;

  }

}