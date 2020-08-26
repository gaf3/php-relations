<?php

require_once('Relations.php');

class Relations_Family_Value {
	
  //// Create a Relations::Family::Value object. This object 
  //// holds sql value and which members are needed to 
  //// create this value.

  function Relations_Family_Value($name,$sql,$names) {

    // $name - The name in the query of this value
    // $sql  - The SQL field/equation of this value
    // $names  - Members that hold this value 

    $this->name = $name;
    $this->sql = $sql;
    $this->names = $names;

  }



  //// Returns html info about the Relations_Family_Value 
  //// object. Useful for debugging and export purposes.

  function toHTML($string,$current) {

    // Create a html string to hold everything

    $html = '';

    // 411

    $html .= "<b>Relations_Family_Value: $this</b>";

    $html .= "<ul>";
    $html .= "<li>Name: $this->name</li>";
    $html .= "<li>SQL:  $this->sql</li>";
    $html .= "<li>Members:</li>";
    
    $html .= "<ul>";

    foreach ($this->members as $member) {

      $html .= "<li>Label: $member->label ";
      $html .= "Name: $member->name ";
      $html .= "Member: $member</li>";

    }

    $html .= "</ul>";
    $html .= "</ul>";

    // Return the html

    return $html;

  }

}

?>