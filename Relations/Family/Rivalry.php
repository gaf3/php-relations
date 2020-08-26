<?php

require_once('Relations.php');

class Relations_Family_Rivalry {
	
  //// Create a Relations_Family_Rivalry object. This object 
  //// holds sql value and which members are needed to 
  //// create this value.

  function Relations_Family_Rivalry($sister_name,$sister_field,$brother_name,$brother_field) {

    // $sister_name - Sister family name (one)
    // $sister_field  - Sister member field used as a foreign key
    // $brother_name  - Brother family name (one)
    // $brother_field   - Brother member field using the foreign key

    $this->sister_name = $sister_name;
    $this->sister_field = $sister_field;
    $this->brother_name = $brother_name;
    $this->brother_field = $brother_field;

  }



  //// Returns html info about the Relations_Family_Rivalry 
  //// object. Useful for debugging and export purposes.

  function toHTML() {

    // Create a html string to hold everything

    $html = '';

    // 411

    $html .= "<b>Relations_Family_Rivalry: $this</b>";

    $html .= "<ul>";

    $html .= "<li>Sister Name: $this->sister_name</li>";
    $html .= "<li>Sister Field: $this->sister_field</li>";

    $html .= "<li>Brother Name: $this->brother_name</li>";
    $html .= "<li>Brother Field: $this->brother_field</li>";

    $html .= "</ul>";

    // Return the html

    return $html;

  }

}