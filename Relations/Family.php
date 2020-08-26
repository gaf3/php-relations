<?php

// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Relations-Family version 0.93                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002, GAF-3 Industries, Inc. All rights reserved.      |
// +----------------------------------------------------------------------+
// | This program is free software, you can redistribute it and/or modify |
// | it under the same terms as PHP istelf                                |
// +----------------------------------------------------------------------+
// | Authors: George A. Fitch III (aka Gaffer) <gaf3@gaf3.com>            |
// +----------------------------------------------------------------------+

  /*

    PURPOSE

    This PHP class uses Relations objects to simplify searching through
    and reporting on large, complex MySQL databases, especially those 
    with foreign keys. It uses an object orientated interface, complete 
    with functions to create and manipulate the relational family.

    HOW IT WORKS

    With Relations_Family you can create a 'family' of members for querying 
    records. A member could be a table, or it could be a query on a table, like
    all the different months from a table's date field. Once the members are
    created, you can specify how those members are related, who's using who
    as a foreign key lookup, and what values in members you might be interested 
    in reporting on, like whether a customer has paid their bill.

    Once the 'family' is complete, you can select records from one member, and
    the query all the matching records from another member. For example, say you 
    a product table being used as a lookup for a order items tables, and you want
    to find all the order items for a certain product. You can select that 
    product's record from the product member, and then view the order item 
    records to find all the order items for that product.

    You can also build a large query for report purposes using the selections 
    from various members as well as values you might be interested in. For 
    example, say you want to know which customer are paid up and how much 
    business they've generated in the past. You can specify which members'
    selections you want to use to narrow down the report and which values
    you'd like in the report and then use the query returned to see who's
    paid and for how much.

  */

require_once('Relations.php');
require_once('Relations/Query.php');
require_once('Relations/Abstract.php');
require_once('Relations/Family/Member.php');
require_once('Relations/Family/Lineage.php');
require_once('Relations/Family/Rivalry.php');
require_once('Relations/Family/Value.php');

class Relations_Family {
	
  //// Create a Relations_Family object.

  function Relations_Family(&$abstract) {

    // Die if they didn't send an abstract

    if (!($abstract))
      die("Relations_Family requires a Relations_Abstract object!");

    // Add the info into the hash.

    $this->abstract = &$abstract;

    // Create an array and a hash of members, a 
    // a hash lookup by name of members, a hash
    // lookup by label of members, and a hash 
    // lookup by name of values. Store the 
    // references into the object

    $this->members = array();
    $this->names = array();
    $this->labels = array();
    $this->values = array();

  }

  //// Adds a member to this family. 

  function addMember() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $label,
      $database,
      $table,
      $id_field,
      $query,
      $alias,
      $member
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'DATABASE',
      'TABLE',
      'ID_FIELD',
      'QUERY',
      'ALIAS',
      'MEMBER'
    ),$arg_list);

    // $name - Member name
    // $label - The label to display for the member
    // $database - The member's database
    // $table - The member's table 
    // $id_field - The name of the member's id field
    // $query - The member's query object, or hash
    // $alias - The alias for the member's table
    // $member - Member object (copy)

    // If they didn't a member, create one

    if (!$member) {

      // If the query's a hash. 

      if (is_array($query)) {

        // Convert it to a Relations_Query object. 

        $query = new Relations_Query($query);

      // Else clone the query

      } else {

        $query = clone $query;

      }

      // Give the user an error message if they didn't
      // send a query argument.

      if (!($query)) 
        return $this->abstract->reportError("addMember failed: No query or member sent<p>");

      // Create a member using what they sent

      $member = new Relations_Family_Member(
        array(
          _name     => $name,
          _label    => $label,
          _database => $database,
          _table    => $table,
          _id_field => $id_field,
          _alias    => $alias,
          _query    => $query
        )
      );

    }

    // Double check to make sure we don't already have
    // a member with the same name.

    if (strlen($this->names[$member->name]) > 0)
      return $this->abstract->reportError("addMember failed: Dupe name: $member->name<p>");

    // Double check to make sure we don't already have
    // a member with the same label.

    if (strlen($this->labels[$member->label]) > 0)
      return $this->abstract->reportError("addMember failed: Dupe label: $member->label<p>"); 

    // Add the member to the array of lists, the names 
    // hash, and the labels hash, so we can look it up 
    // when we need to.

    $this->members[] = &$member;
    $this->names[$member->name] = &$member;
    $this->labels[$member->label] = &$member;

    // Return true so they know everything worked.

    return true;

  }

  //// Establishes a one to many relationship between
  //// two members. 

  function addLineage() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $parent_name,
      $parent_field,
      $child_name,
      $child_field,
      $parent_label,
      $child_label
    ) = Relations_rearrange(array(
      'PARENT_NAME',
      'PARENT_FIELD',
      'CHILD_NAME',
      'CHILD_FIELD',
      'PARENT_LABEL',
      'CHILD_LABEL'
    ),$arg_list);

    // $parent_name - The name of the one member
    // $parent_field - The connecting field of the one member
    // $child_name - The name of the many member
    // $child_field - The connecting field of the many member
    // $parent_label - The label of the one member
    // $child_label - The label of the many member

    // If the parent label was sent but isn't found, 
    // something went wrong. Let the user know what's up.

    if ($parent_label && !($this->labels[$parent_label]))
      return $this->abstract->reportError("addLineage failed: parent label '$parent_label' not found<p>");

    // If the parent name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($parent_name && !($this->names[$parent_name]))
      return $this->abstract->reportError("addLineage failed: parent name '$parent_name' not found<p>");

    // Unless they sent a parent name, get the parent 
    // name using the parent label. 

    if (!($parent_name))
      $parent_name = $this->labels[$parent_label]->name;

    // If the child label was sent but isn't found, 
    // something went wrong. Let the user know what's up.

    if ($child_label && !($this->labels[$child_label]))
      return $this->abstract->reportError("addLineage failed: child label '$child_label' not found<p>");

    // If the child name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($child_name && !($this->names[$child_name]))
      return $this->abstract->reportError("addLineage failed: child name '$child_name' not found<p>");

    // Unless they sent a child name, get the child 
    // name using the child label. 

    if (!($child_name))
      $child_name = $this->labels[$child_label]->name;

    // If the parent field name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($parent_field))
      return $this->abstract->reportError("addLineage failed: parent field not sent<p>");

    // If the child field name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($child_field))
      return $this->abstract->reportError("addLineage failed: child field not sent<p>");

    // Create the new lineage object using the info sent.

    $lineage = new Relations_Family_Lineage($parent_name,$parent_field,$child_name,$child_field);

    // Ok, everything checks out. Add the lineage to both
    // the parent and child so they know that they're related 
    // and how.

    $this->names[$child_name]->parents[] = &$lineage;
    $this->names[$parent_name]->children[] = &$lineage;

    // Return true because everything worked out.

    return true;

  }


  //// Establishes a one to one relationship between
  //// two members. 

  function addRivalry() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $sister_name,
      $sister_field,
      $brother_name,
      $brother_field,
      $sister_label,
      $brother_label
    ) = Relations_rearrange(array(
      'SISTER_NAME',
      'SISTER_FIELD',
      'BROTHER_NAME',
      'BROTHER_FIELD',
      'SISTER_LABEL',
      'BROTHER_LABEL'
    ),$arg_list);

    // $sister_name - The name of the one member
    // $sister_field - The connecting field of the one member
    // $brother_name - The name of the many member
    // $brother_field - The connecting field of the many member
    // $sister_label - The label of the one member
    // $brother_label - The label of the many member

    // If the sister label was sent but isn't found, 
    // something went wrong. Let the user know what's up.

    if ($sister_label && !($this->labels[$sister_label]))
      return $this->abstract->reportError("addRivalry failed: sister label '$sister_label' not found<p>");

    // If the sister name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($sister_name && !($this->names[$sister_name]))
      return $this->abstract->reportError("addRivalry failed: sister name '$sister_name' not found<p>");

    // Unless they sent a sister name, get the sister 
    // name using the sister label. 

    if (!($sister_name))
      $sister_name = $this->labels[$sister_label]->name;

    // If the brother label was sent but isn't found, 
    // something went wrong. Let the user know what's up.

    if ($brother_label && !($this->labels[$brother_label]))
      return $this->abstract->reportError("addRivalry failed: brother label '$brother_label' not found<p>");

    // If the brother name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($brother_name && !($this->names[$brother_name]))
      return $this->abstract->reportError("addRivalry failed: brother name '$brother_name' not found<p>");

    // Unless they sent a brother name, get the brother 
    // name using the brother label. 

    if (!($brother_name))
      $brother_name = $this->labels[$brother_label]->name;

    // If the sister field name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($sister_field))
      return $this->abstract->reportError("addRivalry failed: sister field not sent<p>");

    // If the brother field name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($brother_field))
      return $this->abstract->reportError("addRivalry failed: brother field not sent<p>");

    // Create the new rivalry object using the info sent.

    $rivalry = new Relations_Family_Rivalry($sister_name,$sister_field,$brother_name,$brother_field);

    // Ok, everything checks out. Add the rivalry to both
    // the sister and brother so they know that they're related 
    // and how.

    $this->names[$brother_name]->sisters[] = &$rivalry;
    $this->names[$sister_name]->brothers[] = &$rivalry;

    // Return true because everything worked out.

    return true;

  }



  //// Adds a value held by one or more members. 

  function addValue() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $sql,
      $member_names,
      $member_labels
    ) = Relations_rearrange(array(
      'NAME',
      'SQL',
      'MEMBER_NAMES',
      'MEMBER_LABELS'
    ),$arg_list);

    // $name - The name of the value
    // $sql - The SQL field/equation of the value
    // $members_names - The names of the members that hold this value
    // $member_labels - The labels of the members that hold this value

    // Unless they sent names or members or a value, get the 
    // names using the labels. 

    if (!($member_names)) {

      // Declare an array ref to hold the new names

      $member_names = array();

      // Make sure the labels are in array format

      $member_labels = Relations_toArray($member_labels);

      // Go through each label

      foreach ($member_labels as $member_label) {

        // If this label isn't part of the family, let 
        // the user know something's wrong.

        if (!($this->labels[$member_label]))
          return $this->abstract->reportError("addValue failed: label, $member_label, not found<p>");

        // This label's legit. Add its member's name 
        // to the names array.

        $member_names[] = $this->labels[$member_label]->name;

      }

    }

    // Make sure member_names is an array

    $member_names = Relations_toArray($member_names);

    // Go through the members and make sure they're all valid

    foreach ($member_names as $member_name) {

      // If this name isn't part of the family, let 
      // the user know something's wrong.

      if (!($this->names[$member_name]))
        return $this->abstract->reportError("addValue failed: name, $member_name, not found<p>");

    }

    // If the value name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($name))
      return $this->abstract->reportError("addValue failed: value name not sent<p>");

    // If the sql name isn't defined, something went
    // wrong. Let the user know what's up.

    if (!($sql))
      return $this->abstract->reportError("addValue failed: value sql not sent<p>");

    // If the value members aren't defined, something went
    // wrong. Let the user know what's up.

    if (!(count($member_names) > 0))
      return $this->abstract->reportError("addValue failed: member names not sent<p>");
 
    // Create the new value with the info sent.

    $value = new Relations_Family_Value($name,$sql,$member_names);

    // Double check to make sure we don't already have
    // a value with the same name.

    if ($this->values[$value->name])
      return $this->abstract->reportError("addValue failed: Dupe name: $value->name<p>");

    // Ok, everything checks out. Add the value to 
    // this family.

    $this->values[$value->name] = $value;

    // Return the value because everything's alright.

    return $value;

  }


  //// Gets the chosen items of a member. 

  function getChosen() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $label
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL'
    ),$arg_list);

    // $name - The name of the member
    // $label - The label of the member

    // If the label was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($label && !($this->labels[$label]))
      return $this->abstract->reportError("getChosen failed: member label '$label' not found<p>");

    // If the name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($name && !($this->names[$name]))
      return $this->abstract->reportError("getChosen failed: member name '$name' not found<p>");

    // If they didn't send a name, get the member name 
    // using the label. Then get the member using the 
    // name.

    if (!($name))
      $name = $this->labels[$label]->name;
    $member = &$this->names[$name];

    // Create an array to hold all the values.

    $chosen = array();

    // Fill that array

    $chosen[count] = $member->chosen_count;
    $chosen[ids_string] = $member->chosen_ids_string;
    $chosen[ids_array] = $member->chosen_ids_array;
    $chosen[ids_select] = $member->chosen_ids_select;

    $chosen[labels_string] = $member->chosen_labels_string;
    $chosen[labels_array] = $member->chosen_labels_array;
    $chosen[labels_hash] = $member->chosen_labels_hash;
    $chosen[labels_select] = $member->chosen_labels_select;

    $chosen[filter] = $member->filter;
    $chosen[match] = $member->match;
    $chosen[group] = $member->group;
    $chosen[limit] = $member->limit;
    $chosen[ignore] = $member->ignore;

    // Return the hash ref

    return $chosen;

  }



  //// Sets the chosen items of a member. 

  function setChosen() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $ids,
      $labels,
      $match,
      $group,
      $filter,
      $limit,
      $ignore,
      $selects,
      $label,
      $labels_hash
    ) = Relations_rearrange(array(
      'NAME',
      'IDS',
      'LABELS',
      'MATCH',
      'GROUP',
      'FILTER',
      'LIMIT',
      'IGNORE',
      'SELECTS',
      'LABEL',
      'LABELS_HASH'
    ),$arg_list);

    // $name - The name of the member
    // $ids - The selected ids
    // $labels - The selected labels
    // $match - Mathcing any of all selections
    // $group - Group inclusively or exclusively
    // $filter - The filter for the labels
    // $limit - Limit settings
    // $ignore - Whether or not we're ignoring this member
    // $selects - The select ids from a HTML select list
    // $label - The label of the member
    // $labels_hash - The labels array keyed by the ids

    // If the label was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($label && !($this->labels[$label]))
      return $this->abstract->reportError("setChosen failed: member label '$label' not found<p>");

    // If the name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($name && !($this->names[$name]))
      return $this->abstract->reportError("setChosen failed: member name '$name' not found<p>");

    // Unless they sent a name, get the member name 
    // using the label. Then get the member using 
    // the name.

    if (!($name))
      $name = $this->labels[$label]->name;
    $member = &$this->names[$name];

    // If the selects array was sent, then use that. A selects
    // array is an array of "$id\t$label" values. This is done
    // so we can see both the selected ids and labels return 
    // from HTML select list.

    if ($selects) {

      // Set the count based on the number of selects, and
      // and set the ids for the selects to what was sent.

      $member->chosen_count = count($selects);
      $member->chosen_ids_select = $selects;

      // Empty out the ids array, the labels array,
      // the labels hash, and the labels select hash
      // because we're going to fill them.

      $member->chosen_ids_array = array();
      $member->chosen_labels_array = array();
      $member->chosen_labels_hash = array();
      $member->chosen_labels_select = array();

      // Go through all the selects and fill the other
      // storage forms.

      foreach ($selects as $select) {

        list($id,$label) = explode("\t",$select);

        $member->chosen_ids_array[] = $id;
        $member->chosen_labels_array[] = $label;
        $member->chosen_labels_hash[$id] = $label;
        $member->chosen_labels_select[$select] = $label;

      }

    }

    // If the ids were set as an array and the labels were
    // sent as a hash.

    else if (is_array($ids) && $labels_hash) {

      // Set the count based on the number of ids, and
      // and set the array for the ids and the hashref
      // of the labels to what was sent.

      $member->chosen_count = count($ids);
      $member->chosen_ids_array = $ids;
      $member->chosen_labels_hash = $labels_hash;

      // Empty out the ids select array, the labels array,
      // the labels select hash because we're going to 
      // fill them.

      $member->chosen_ids_select = array();
      $member->chosen_labels_array = array();
      $member->chosen_labels_select = array();

      // Go through all the ids and fill the other
      // storage forms.

      foreach ($ids as $id) {

        $member->chosen_ids_select[] = "$id\t$labels_hash[$id]";
        $member->chosen_labels_array[] = $labels_hash[$id];
        $member->chosen_labels_select["$id\t$labels_hash[$id]"] = $labels_hash[$id];

      }

    }

    // Else $ids and $labels are arrays or strings

    else {

      // Make sure $ids and $labels are array refs.
      // Split $labels by tabs, not commas.

      $ids = Relations_toArray($ids);
      $labels = Relations_toArray($labels,"\t");
      
      // Set the count based on the number of ids, and
      // and set the array for the ids and the hashref
      // of the labels to what was sent.

      $member->chosen_count = count($ids);
      $member->chosen_ids_array = $ids;
      $member->chosen_labels_array = $labels;

      // Empty out the ids select array, the labels hash,
      // the labels select hash.

      $member->chosen_ids_select = array();
      $member->chosen_labels_hash = array();
      $member->chosen_labels_select = array();

      // Go through all the ids and fill the other
      // storage forms.

      for ($i = 0; $i < count($ids); $i++) {

        $member->chosen_ids_select[] = "$ids[$i]\t$labels[$i]";
        $member->chosen_labels_hash[$ids[$i]] = $labels[$i];
        $member->chosen_labels_select["$ids[$i]\t$labels[$i]"] = $labels[$i];

      }

    }

    // Set the strings accordingly.

    $member->chosen_ids_string = implode(',', $member->chosen_ids_array);
    $member->chosen_labels_string = implode("\t", $member->chosen_labels_array);

    // Grab the other settings if sent.

    $member->filter = $filter;
    $member->match = $match;
    $member->group = $group;
    $member->limit = $limit;
    $member->ignore = $ignore;

    // Return the whole shabang set.

    return $this->getChosen($member->name);

  }



  //// Clears the chosen items of a member. 

  function clearChosen() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $label
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL'
    ),$arg_list);

    // $name - The name of the member
    // $label - The label of the member

    // If the label was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($label && !($this->labels[$label]))
      return $this->abstract->reportError("setChosen failed: member label '$label' not found<p>");

    // If the name was sent but isn't found, something 
    // went wrong. Let the user know what's up.

    if ($name && !($this->names[$name]))
      return $this->abstract->reportError("setChosen failed: member name '$name' not found<p>");

    // Unless they sent a name, get the member name 
    // using the label. Then get the member using 
    // the name.

    if (!($name))
      $name = $this->labels[$label]->name;
    $member = &$this->names[$name];

    // Clear everything

    $member->chosen_count = 0;
    $member->chosen_ids_string = '';
    $member->chosen_ids_array = array();
    $member->chosen_ids_select = array();
    $member->chosen_labels_string = '';
    $member->chosen_labels_array = array();
    $member->chosen_labels_hash = array();
    $member->chosen_labels_select = array();
    $member->filter = '';
    $member->match = 0;
    $member->group = 0;
    $member->limit = 0;
    $member->ignore = 0;

  }



  //// Clears the chosen items for all members. 

  function clear() {

    # Go through all members, clearing as we go

    foreach (array_keys($this->names) as $name)
      $this->clearChosen($name);

  }



  //// Gets whether a member needs to be involved in 
  //// a query. 

  function getNeeds($member_name,&$needs,&$needed,$skip = 0){

    // $member_name - The name of the member being evaluated
    // $needs - Hash ref of members needs by name
    // $needed - Hash ref of members evaluated by name
    // $skip - Skip this members needs

    // If we've got stuff selected, and we're not to be 
    // ignored, then we need to be in a query.

   $need = (($this->names[$member_name]->chosen_count > 0) && 
           !($this->names[$member_name]->ignore) && !($skip));

    // Add ourselves to the hash, showing that we've been
    // evaluated for a need to be in a query.

    $needed[$member_name] = 1;

    // Go thorugh all our relatives, and || their need with 
    // ours, unless they've already been evaluated for 
    // need. We || them because if they need to be in a query, 
    // and they haven't been checked yet, then we need to be in 
    // a query to connect them to the original member that needs 
    // a query.

    // Parents

    foreach ($this->names[$member_name]->parents as $lineage) {

      if ($needed[$lineage->parent_name])
        continue;

      $need = $this->getNeeds($lineage->parent_name,$needs,$needed) || $need;

    }

    // Children

    foreach ($this->names[$member_name]->children as $lineage) {

      if ($needed[$lineage->child_name])
        continue;

      $need = $this->getNeeds($lineage->child_name,$needs,$needed) || $need;

    }

    // Brothers

    foreach ($this->names[$member_name]->brothers as $rivarly) {

      if ($needed[$rivarly->brother_name])
        continue;

      $need = $this->getNeeds($rivarly->brother_name,$needs,$needed) || $need;

    }

    // Sisters

    foreach ($this->names[$member_name]->sisters as $rivarly) {

      if ($needed[$rivarly->sister_name])
        continue;

      $need = $this->getNeeds($rivarly->sister_name,$needs,$needed) || $need;

    }

    // Return whether we're needed, and set the
    // needs hash.

    $needs[$member_name] = $need;

    return $need;

  }



  //// Gets a member's chosen id values based on what's
  //// been selected and what its match value is.

  function getIDs($member_name,&$ids,&$ided,$no_all = 0,$skip = 0) {

    // $member_name - The name of the member
    // $ids - Array of hashes of id values
    // $ided - Hash ref of lists already id'ed
    // $no_all - Whether or not we can match all
    // $skip - Skip the member being currently checked.

    // Grab the actual member to make life easier

    $member = &$this->names[$member_name];

    // If we've got stuff selected, we're not to be ignored, 
    // and we're not being skipped then we need to be ided.

    if (($member->chosen_count > 0) && !($member->ignore) && !($skip)) {

      // Unless we're set to match all, and we're allowed to
      // match all ids.

      if (!($member->match && !($no_all))) {

        // Then we're just going to add our ids to the 
        // values array of hashes.

        // Go through each row of ids

        for($i = 0; $i < count($ids); $i++) {

          // Put our ids in keyed by our name

          $ids[$i][$member->name] = $member->chosen_ids_string;

        }

      // If we're to match all, then we need to increase the
      // rows of values X times, where X is the number of our 
      // selected ids.

      } else {

        // Declare a new array for the array of hashes of 
        // ids.

        $new_ids = array();

        // Go through each row in the values

        foreach ($ids as $row) {

          // Go through each of our ids

          foreach ($member->chosen_ids_array as $id) {

            // Create a new row from the current ids row,
            // assign our current id to it, and add it to the
            // new array of hashes of ids

            $row[$member->name] = $id;

            $new_ids[] = $row;

          }

        }

        // Point the old ids to our new ids.

        $ids = $new_ids;

      }

    }

    // Add ourselves to the hash, showing that we've
    // added our ids, and are thus ided.

    $ided[$member->name] = 1;

    // Go through all our relatives, add add their ids to
    // ids, unless they've already been id'ed. 

    // Parents

    foreach ($member->parents as $lineage) {

      if ($ided[$lineage->parent_name])
        continue;

      $ids = $this->getIDs($lineage->parent_name,$ids,$ided);

    }

    // Children
    foreach ($member->children as $lineage) {

      if ($ided[$lineage->child_name])
        continue;

      $ids = $this->getIDs($lineage->child_name,$ids,$ided,$no_all);

    }

    // Brothers

    foreach ($member->brothers as $rivalry) {

      if ($ided[$rivalry->brother_name])
        continue;

      $ids = $this->getIDs($rivalry->brother_name,$ids,$ided,$no_all);

    }

    // Sisters

    foreach ($member->sisters as $rivalry) {

      if ($ided[$rivalry->sister_name])
        continue;

      $ids = $this->getIDs($rivalry->sister_name,$ids,$ided,$no_all);

    }

    // Return the collection of ids we have. 

    return $ids;

  }



  //// Gets a member's contribution to the query. 

  function getQuery($member_name,&$query,$row,$needs,&$queried) {

    // $member_name - The name of the member
    // $query - The query to build
    // $row - The current $ids row to use
    // $needs - Hash of who needs to be in the query
    // $queried - Hash of members that have added to the query

    // Grab the actual member to make life easier

    $member = &$this->names[$member_name];

    // Our table is needed in the query.

    $query->add(array(_from => array($member->alias => "$member->database.$member->table")));

    // If we have stuff chosen, then our chosen ids 
    // need to be in the query. Make sure we exclude
    // our chosen values if we're supposed to.

    if ($row[$member->name]) {

      if ($member->group)
        $group = ' not';
      else
        $group = '';

      $member_id = "$member->alias." . 
                   "$member->id_field";

      $query->add(array(_where => "$member_id$group in (" . $row[$member->name] . ")"));

    }

    // Add ourselves to the hash, showing that we've
    // added to the query.

    $queried[$member->name] = 1;

    // Go thorugh all our relatives, add add their query bits to
    // the query, unless they've already done that or they just 
    // don't need to.

    // Parents

    foreach ($member->parents as $lineage) {

      if ($queried[$lineage->parent_name] || 
           !$needs[$lineage->parent_name])
        continue;

      $parent_field = $this->names[$lineage->parent_name]->alias . '.' .
                                   $lineage->parent_field;

      $child_field = $this->names[$lineage->child_name]->alias . '.' . 
                                  $lineage->child_field;

      $query->add(array(_where => "$child_field=$parent_field"));

      $this->getQuery($lineage->parent_name,$query,$row,$needs,$queried);

    }

    // Children

    foreach ($member->children as $lineage) {

      if ($queried[$lineage->child_name] || 
           !$needs[$lineage->child_name])
        continue;

      $parent_field = $this->names[$lineage->parent_name]->alias . '.' .
                                   $lineage->parent_field;

      $child_field = $this->names[$lineage->child_name]->alias . '.' .
                                  $lineage->child_field;

      $query->add(array(_where => "$parent_field=$child_field"));

      $this->getQuery($lineage->child_name,$query,$row,$needs,$queried);

    }

    // Brothers

    foreach ($member->brothers as $rivalry) {

      if ($queried[$rivalry->brother_name] || 
           !$needs[$rivalry->brother_name])
        continue;

      $brother_field = $this->names[$rivalry->brother_name]->alias . '.' .
                                    $rivalry->brother_field;

      $sister_field = $this->names[$rivalry->sister_name]->alias . '.' .
                                   $rivalry->sister_field;

      $query->add(array(_where => "$sister_field=$brother_field"));

      $this->getQuery($rivalry->brother_name,$query,$row,$needs,$queried);

    }

    // Sisters

    foreach ($member->sisters as $rivalry) {

      if ($queried[$rivalry->sister_name] || 
           !$needs[$rivalry->sister_name])
        continue;

      $brother_field = $this->names[$rivalry->brother_name]->alias . '.' .
                                    $rivalry->brother_field;

      $sister_field = $this->names[$rivalry->sister_name]->alias . '.' .
                                   $rivalry->sister_field;

      $query->add(array(_where => "$brother_field=$sister_field"));

      $this->getQuery($rivalry->sister_name,$query,$row,$needs,$queried);

    }

  }



  //// Gets the available records for a member 
  //// based on other members selections.

  function getAvailable() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $label,
      $focus
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL',
      'FOCUS'
    ),$arg_list);

    // $name - The name of the member
    // $label - The label of the member
    // $focus - Whether to use one's own chosen ids

    // If the label isn't found, something went
    // wrong. Let the user know what's up.

    if ($label && !$this->labels[$label])
      return $this->abstract->reportError("getAvailable failed: member label '$label' not found<p>");

    // If the name isn't found, something went
    // wrong. Let the user know what's up.

    if ($name && !$this->names[$name])
      return $this->abstract->reportError("getAvailable failed: member name '$name' not found<p>");

    // Unless they sent a name or member, get the member name 
    // using the label. Then unless they sent a member, get 
    // the member using the name.

    if (!$name)
      $name = $this->labels[$label]->name;

    $member = &$this->names[$name];

    // Create a query to hold the avaiable items. Base it off
    // of what was specified at the creation of the current 
    // member and what's been selected.

    $available_query = clone $member->query;

    // If we have a filter, put it in.

    if ($member->filter) {

      $available_query->add(array(_having => "label like '%" . mysql_escape_string($member->filter) . "%'"));

    }
    
    // If we have a limit, put it in.

    if ($member->limit) {

      $available_query->add(array(_limit => $member->limit));

    }
    
    // Now we need to see if any of the other need to add to
    // the query, starting at this member. So create the hashes 
    // to hold the needs. The needs is just a hashref keyed
    // by the member name and set to whether the member needs
    // to be queried, 1.
    
    $needs = array();
    $needed = array();

    // Now call the recursive getNeeds, starting at the 
    // current member. Make sure the first member's skipped 
    // too, since we're not going build a query of avaiable 
    // records if the only selections are from this member.

    $need = $this->getNeeds($member->name,$needs,$needed,1);

    // If there's a need to have other members contribute to 
    // the query.

    if ($need) {

      // Create an empty ids set. A ids set is an array ref
      // of hashrefs of selected ids, keyed by the member name. To
      // create an empty ids set, we need an empty hash's 
      // reference in the first member of the array ref. 

      $ids = array();
      $ids[] = array();

      // Like getNeeds, we also need a hash for keeping track 
      // of which members we've ided. 

      $ided = array();

      // Call the recursive gets ids. Skip the current member
      // and don't allow match all's on the member and all their
      // connected members except parents.

      $ids = $this->getIDs($member->name,$ids,$ided,1,1 && !$focus);

      // Go through all the ids sets found and create a 
      // temporary table for each set. Start the set 
      // suffixes at 0.

      $set = 0;

      foreach ($ids as $row) {

        // Now we have to make a hash to hold who's been queried
        // and who hasn't. 

        $queried = array();

        // Create a query object for this values set, adding our
        // id to the select clause.

        $row_query = new Relations_Query(array(
          _select => array('id_field' => "$member->alias.$member->id_field"), 
          _options => 'distinct'
        ));

        // Run the recursive get query. 

        $this->getQuery($member->name,$row_query,$row,$needs,$queried);

        // Now create a temporary table with the query

        $table = $member->name . '_query_' . $set;
        $create = "create temporary table $table ";
        $condition = "$member->table.$member->id_field=$table.id_field";
        $row_string = $create . $row_query->get();

        // If we can't select the database, something went
        // wrong. Let the user know what's up.

        if (!$this->abstract->runQuery("use $member->database"))
          return $this->abstract->reportError("getAvailable failed: couldn't select database<p>");

        // If we can't drop the table, something went
        // wrong. Let the user know what's up.

        if (!$this->abstract->runQuery("drop table if exists $table"))
          return $this->abstract->reportError("getAvailable failed: couldn't drop table<p>");

        // If we can't drop the table, something went
        // wrong. Let the user know what's up.

        if (!$this->abstract->runQuery($row_string))
          return $this->abstract->reportError("getAvailable failed: couldn't query row: $row_string<p>");

        // Add this temp table and requirement to the 
        // avaiable query, and increase the set var.

        $available_query->add(
          array(
            _from  => $table,
            _where => $condition
          )
        );

        $set++;

      }

    }

    // Prepare and execute the main query

    $available_string = $available_query->get();

    // If we can't query available, something went
    // wrong. Let the user know what's up.

    if (!($result = mysql_query($available_string))) 
      return $this->abstract->reportError("getAvailable failed: couldn't query available: $available_string<p>");

    // Clear out the member's available stuff.

    $member->available_count = 0;
    $member->available_ids_array = array();
    $member->available_ids_select = array();

    $member->available_labels_array = array();
    $member->available_labels_hash = array();
    $member->available_labels_select = array();

    // Populate all members

    while ($row = mysql_fetch_array($result)) {

      $member->available_ids_array[] = $row[id];
      $member->available_ids_select[] = "$row[id]\t$row[label]";

      $member->available_labels_array[] = $row[label];
      $member->available_labels_hash[$row[id]] = $row[label];
      $member->available_labels_select["$row[id]\t$row[label]"] = $row[label];

    }

    // Grab the count 

    $member->available_count = count($member->available_ids_array);

    // Create the info hash to return and fill it

    $available = array();

    $available[filter] = $member->filter;
    $available[match] = $member->match;
    $available[group] = $member->group;
    $available[limit] = $member->limit;
    $available[ignore] = $member->ignore;
    $available[count] = $member->available_count;
    $available[ids_array] = $member->available_ids_array;
    $available[ids_select] = $member->available_ids_select;
    $available[labels_array] = $member->available_labels_array;
    $available[labels_hash] = $member->available_labels_hash;
    $available[labels_select] = $member->available_labels_select;

    return $available;

  }



  //// Sets chosen items from available items, using the
  //// members current chosen ids, as well as other members
  //// chosen ids.

  function chooseAvailable() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $name,
      $label
    ) = Relations_rearrange(array(
      'NAME',
      'LABEL'
    ),$arg_list);

    // $name - The name of the member
    // $label - The label of the member

    // If the label isn't found, something went
    // wrong. Let the user know what's up.

    if ($label && !$this->labels[$label])
      return $this->abstract->reportError("chooseAvailable failed: member label '$label' not found<p>");

    // If the name isn't found, something went
    // wrong. Let the user know what's up.

    if ($name && !$this->names[$name])
      return $this->abstract->reportError("chooseAvailable failed: member name '$name' not found<p>");

    // Unless they sent a name or member, get the member name 
    // using the label. Then unless they sent a member, get 
    // the member using the name.

    if (!$name)
      $name = $this->labels[$label]->name;

    $member = &$this->names[$name];

    // Get the available members ids, including using the 
    // member's own ids in the query.

    $available = $this->getAvailable(array(_name => $member->name, _focus => 1));

    // Return the result from setting the chosen ids to
    // the available ids and labels.

    return $this->setChosen(
      array(
        _name   => $member->name,
        _ids    => $available[ids_array],
        _labels => $available[labels_hash],
        _filter => $member->filter,
        _match  => $member->match,
        _group  => $member->group,
        _limit  => $member->limit,
        _ignore => $member->ignore
      )
    );

  }



  //// Gets who'll be attending a reunion

  function getVisits($member_name,&$visits,&$visited,$ids,$valued) {

    // $member_name - The member's name
    // $visits - Hash of who'll visit
    // $visited - Hash ref of who's been checked
    // $ids - IDs to use in the reunion
    // $valued - Hash ref of reunion values' members

    // Snag the actual member from the name

    $member = &$this->names[$member_name];

    // If we're valued or our ids are playing a role 

    $visit = $valued[$member->name] || strlen($ids[$member->name]) > 0;

    // Add ourselves to the hash, showing that we've been
    // evaluated for a visit to the reunion

    $visited[$member->name] = 1;

    // Go through all our relatives, and || their visit with 
    // ours, unless they've already been evaluated for a
    // visit. We || them because if they need to be part of 
    // the reunion, and they haven't been checked yet, then we 
    // need to connect them to the central member of the
    // reunion.

    // Parents

    foreach ($member->parents as $lineage) {

      if ($visited[$lineage->parent_name])
        continue;

      $visit = $this->getVisits($lineage->parent_name,$visits,$visited,$ids,$valued) || $visit;

    }

    // Children

    foreach ($member->children as $lineage) {

      if ($visited[$lineage->child_name])
        continue;

      $visit = $this->getVisits($lineage->child_name,$visits,$visited,$ids,$valued) || $visit;

    }

    // Brothers

    foreach ($member->brothers as $rivalry) {

      if ($visited[$rivalry->brother_name])
        continue;

      $visit = $this->getVisits($rivalry->brother_name,$visits,$visited,$ids,$valued) || $visit;

    }

    // Sisters

    foreach ($member->sisters as $rivalry) {

      if ($visited[$rivalry->sister_name])
        continue;

      $visit = $this->getVisits($rivalry->sister_name,$visits,$visited,$ids,$valued) || $visit;

    }

    // Return whether we're to visit, and set the
    // visits hash.

    $visits[$member->name] = $visit;

    return $visit;

  }



  //// Gets the reunion for the family.

  function getReunion() {

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the data

    list(
      $data,
      $use_names,
      $group_by,
      $order_by,
      $start_name,
      $query,
      $use_labels,
      $start_label
    ) = Relations_rearrange(array(
      'DATA',
      'USE_NAMES',
      'GROUP_BY',
      'ORDER_BY',
      'START_NAME',
      'QUERY',
      'USE_LABELS',
      'START_LABEL'
    ),$arg_list);

    // $data - The values of the data
    // $use_names -  Use IDs from these members (by name)
    // $group_by - Group by values
    // $order_by -Group by values
    // $start_name - Name of member to start from
    // $query - Query to start out with
    // $use_labels -  Use IDs from these members (by label)
    // $start_label - Label of member to start from

    // Look up use names from use labels unless use names 
    // or use members is set, or they didn't send use labels

    if (!($use_names || !$use_labels)) {

      // Convert use labels to an array, and 
      // set use names to an empty array.

      $use_labels = Relations_toArray($use_labels);
      $use_names = array();

      // Go through each use label

      foreach ($use_labels as $use_label) {

        // If this use label isn't found, something went
        // wrong. Let the user know what's up.

        if (!$this->labels[$use_label]->name)
          return $this->abstract->reportError("getReunion failed: use member label '$use_label' not found<p>");

        // Add this member's name to use_names

        $use_names[] = $this->labels[$use_label]->name;

      }

    }
   
    // Look up use members from use names unless use 
    // members is set, or they didn't send use names

    if ($use_names) {

      // Convert use names to an array, and 
      // set use members to an empty array.

      $use_names = Relations_toArray($use_names);
      $use_members = array();

      // Go through each use name

      foreach ($use_names as $use_name) {

        // If this use name isn't found, something went
        // wrong. Let the user know what's up.

        if (!($this->names[$use_name]))
          return $this->abstract->reportError("getReunion failed: use member name '$use_name' not found<p>");

        // Add this member to use_members

        $use_members[] = &$this->names[$use_name];

      }

    }

    // Create an empty hash of ids to hold the ids
    // to use in the reunion. 

    $ids = array();

    // Look up ids from use members if they sent use 
    // members, and key them by name.

    if ($use_members) {

      foreach ($use_members as $use_member) {

        // If this member has stuff set and its not
        // to be ignored, use its selected ids.

        if (($use_member->chosen_count > 0) && !$use_member->ignore)
          $ids[$use_member->name] = $use_member->chosen_ids_string;

      }

    }
   
    // If the start label isn't found, something went
    // wrong. Let the user know what's up.

    if ($start_label && !$this->labels[$start_label]->name)
      return $this->abstract->reportError("getReunion failed: start member label '$start_label' not found<p>");

    // If the start name isn't found, something went
    // wrong. Let the user know what's up.

    if ($start_name && !$this->names[$start_name])
      return $this->abstract->reportError("getReunion failed: start member name '$start_name' not found<p>");

    // Unless they sent a name or member, get the member name 
    // using the label. Then unless they sent a member, get 
    // the member using the name.

    if (!$start_name)
      $start_name = $this->labels[$start_label]->name;

    $start_member = &$this->names[$start_name];

    // Make sure all values are in array form
    
    $data = Relations_toArray($data);
    $group_by = Relations_toArray($group_by);
    $order_by = Relations_toArray($order_by);

    // Create a query if they didn't send one

    if (!is_object($query))
      $query = new Relations_Query();

    // Create a hash for all the values needed

    $values = array();

    // Create a hash to hold all the members 
    // that have a needed value, also create
    // the select part of the query as well 
    // as arrays to hold the quoted values
    // for the group by and order by clause

    $valued = array();
    $select = array();
    $quoted_group_by = array();
    $quoted_order_by = array();

    // Go through all the group by field values.

    foreach ($group_by as $value) {

      // Go through each of this values members

      foreach ($this->values[$value]->names as $member_name) {

        // This member is valued since we need it
        // to calculate this value. 

        $valued[$member_name] = 1;
        
        // Add this value to the select hash, with 
        // the key being the name with quotes around 
        // in case it has spaces in it, and the value
        // being the sql part of the value. 

        $select[mysql_escape_string($this->values[$value]->name)] = $this->values[$value]->sql; 

      }

      // Add this value, with quotes, to the quoted
      // group by array.

      $quoted_group_by[] = mysql_escape_string($this->values[$value]->name); 

    }

    // Go through all the order by field values.

    foreach ($order_by as $value) {

      // There might be a desc or asc in the order by
      // value. Let's pop it out for now and add it 
      // later.

      if (strstr($value,' desc')) {

        $value = str_replace(' desc','',$value);
        $order = ' desc';

      }

      if (strstr($value,' asc')) {

        $value = str_replace(' asc','',$value);
        $order = ' asc';

      }

      // Go through each of this values members

      foreach ($this->values[$value]->names as $member_name) {

        // This member is valued since we need it
        // to calculate this value. 

        $valued[$member_name] = 1; 

        // Add this value to the select hash, with 
        // the key being the name with quotes around 
        // in case it has spaces in it, and the value
        // being the sql part of the value. 

        $select[mysql_escape_string($this->values[$value]->name)] = $this->values[$value]->sql; 

      }

      // Add this value, with quotes, to the quoted
      // order by array, complete with the sort 
      // direction.

      $quoted_order_by[] = mysql_escape_string($this->values[$value]->name) . $order; 

      // Add the sort direction back to the original
      // value as well cuz we don't want to change
      // what the user sent.

      // $value .= $order;

    }

    // Go through all the data field values.

    foreach ($data as $value) {

      // Go through each of this values members

      foreach ($this->values[$value]->names as $member_name) {

        // This member is valued since we need it
        // to calculate this value. 

        $valued[$member_name] = 1; 

        // Add this value to the select hash, with 
        // the key being the name with quotes around 
        // in case it has spaces in it, and the value
        // being the sql part of the value. 

        $select[mysql_escape_string($this->values[$value]->name)] = $this->values[$value]->sql; 

      }

    }

    // If we value nothing, something went wrong
    // with the reunion.

    if (count($valued) == 0)
      return $this->abstract->reportError("getReunion failed: nothing valued<p>");
   
    // Unless we were able to lookup a start member,
    // use the first valued member for the reunion.
    
    if (!$start_name)
      list($start_name) = array_keys($valued);

    // Get all the members that are visting the 
    // reunion.

    $visits = array();
    $visited = array();

    $this->getVisits($start_name,$visits,$visited,$ids,$valued);

    // Now we have to make a hash to hold who's been queried
    // and who hasn't. 

    $queried = array();

    // Wipe that have empty arrays

    if (count($select) == 0)
      $select = '';

    if (count($quoted_group_by) == 0)
      $quoted_group_by = '';

    if (count($quoted_order_by) == 0)
      $quoted_order_by = '';

    // Add to the query object for this values set.

    $query->add(
      array(
        _select   => $select,
        _group_by => $quoted_group_by,
        _order_by => $quoted_order_by
      )
    );

    // Run the recursive get query and return it

    $this->getQuery($start_name,$query,$ids,$visits,$queried);

    return $query;

  }



  //// Returns html info about the Relations_Family 
  //// object. Useful for debugging and export purposes.

  function toHTML() {

    // Create a html string to hold everything

    $html = '<ul>';

    // 411

    $html .= "<li><b>Relations_Family: $this</b></li>";

    $html .= "<li>Members:<li>";

    $html .= "<li>";

    foreach ($this->members as $member) {

      $html .= $member->toHTML();

    }

    $html .= "</li>";

    $html .= "<li>Values:</li>";

    foreach ($this->values as $name=>$value) {

      $html .= $value->toHTML();

    }

    $html .= "</li>";

    $html .= "</ul>";

    // Return the html

    return $html;

  }

}

?>