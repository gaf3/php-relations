<?php

// +----------------------------------------------------------------------+
// | Relations-IMAP v0.10                                                |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004, GAF-3 Industries, Inc. All rights reserved.      |
// +----------------------------------------------------------------------+
// | This program is free software, you can redistribute it and/or modify |
// | it under the same terms as PHP istelf                                |
// +----------------------------------------------------------------------+
// | Authors: George A. Fitch III (aka Gaffer) <gaf3@gaf3.com>            |
// +----------------------------------------------------------------------+

/*

  PURPOSE

  This is a class to deal with IMAP. It uses both the built in PHP
  functions, as wells some custom socket interaction. This is not
  to reinvent the wheel, merely deal with some short comings of the
  existing php functions.

*/



class Relations_IMAP {

  //// Constructor

  function Relations_IMAP() {

    /* 
    
      $host - The name of the host
      $port - Teh port of the host
      $username - Username to connect with
      $password - Password to connect with
      $delimit - The delimitter to use for folders
      $folder - The default folder to connect to
      $options - Options to connect with
      $socket - Use this socket
      $target - Use this target
      $resource - Use this resource
      $reference - Use this reference

    */

    // Grab the arg list to parse

    $arg_list = func_get_args();

    // Get all the arguments passed

    list(
      $this->host,
      $this->port,
      $this->username,
      $this->password,
      $this->delimit,
      $this->folder,
      $this->options,
      $this->socket,
      $this->target,
      $this->resource,
      $this->reference
    ) = Relations_rearrange(array(
      'HOST',
      'PORT',
      'USERNAME',
      'PASSWORD',
      'DELIMIT',
      'FOLDER',
      'OPTIONS',
      'SOCKET',
      'TARGET',
      'RESOURCE',
      'REFERENCE'
    ),$arg_list);

    // Translate options to hadh

    $this->options = Relations_toHash($this->options);

    // If there's no target, build one

    if (!$this->target) {

      if ($this->options['ssl'])
        $this->target = "ssl://$this->host";
      elseif ($this->options['tls'])
        $this->target = "tls://$this->host";
      else
        $this->target = $this->host;

    }

    // If there's no socket, connect

    if (!$this->socket)
      $this->socket = pfsockopen($this->target,$this->port, $errno, $errstr, 15);

    // Check for connection

    if (!$this->socket)
      die("Failed to connect! $errno $errstr");

    // If there's no reference, build one

    if (!$this->reference) {

      // Build off host and port
      
      $refer = "$this->host:$this->port";

      // Add protocol

      if ($this->options['ssl'])
        $refer .= "/ssl";
      elseif ($this->options['tls'])
        $refer .= "/tls";

      // Add options

      if ($this->options['validate-cert'])
        $refer .= "/validate-cert";
      elseif ($this->options['novalidate-cert'])
        $refer .= "/novalidate-cert";

      // Build a base reference

      $this->reference = "{" . $refer. "}";

    }

    // If there's no resource, create it

    if (!$this->resource)
      $this->resource = imap_open($this->reference, $this->username, $this->password, OP_HALFOPEN | CL_EXPUNGE);

    // If there's no delimit, use '.'

    if (!$this->delimit)
      $this->delimit = '.';

    // Login

    $this->login($this->username, $this->password);

    // Initialize status and response

    $this->status = '';
    $this->response = '';

  }



  //// Runs a command and returns the data

  function command($tag,$command) {

    fwrite($this->socket,"$tag $command\r\n");

    $data = '';

    while (!feof($this->socket) && ($this->response = fgets($this->socket,1024)) && (substr($this->response,0,strlen($tag)) != $tag))
      $data .= $this->response;

    list($tag,$this->status) = explode(' ',$this->response);
    
    return $data;

  }



  //// Logs in a user

  function login($username,$password) {

    // Properly format and run comment

    $username = imap_utf7_encode($username);
    $password = imap_utf7_encode($password);

    $this->command("A001","LOGIN $username $password");

    return ($this->status == 'OK');

  }



  //// Strips out the reference

  function strip($folder) {

    // Remove anything before and including a }

    return preg_replace("/^[^\}]+\}/",'',$folder);

  }



  //// Breaks out bits of a message

  function split($value,$name) {

    // Split the letter and section

    $split = array();
    $delimit = preg_quote($this->delimit);

    list($split['message'],$split['section']) = explode(':',$value);
    $split['folder'] = preg_replace("/$delimit" . "[^$delimit]+$/",'',$split['message']);
    $split['uid'] = preg_replace("/^.+$delimit/",'',$split['message']);

    // Return the value

    return $split[$name];

  }



  //// Builds in bits of a message

  function join($folder,$uid,$section='') {

    $join = $folder . $this->delimit . $uid;

    if ($section)
      $join .= ':' . $section;

    // Return the value

    return $join;

  }



  //// Checks to see if a mailbox exists

  function folded($folder) {

    $folders = imap_list($this->resource, imap_utf7_encode($this->reference), imap_utf7_encode($folder));

    return (is_array($folders) && count($folders));

  }



  //// Checks for a messages existance 

  function exists($message) {

    // Reopen

    imap_reopen($this->resource,imap_utf7_encode("$this->reference$folder"), CL_EXPUNGE);

    // Get the msgno for header info

    $msgno = imap_msgno($this->resource,$uid);

    // Reset

    $this->reset();

    // Give back if valid

    return ($msgno > 0);

  }



  //// Selects a mailbox

  function select($folder) {

    // Properly format and run comment

    $folder = imap_utf7_encode($folder);

    $this->command("S001","SELECT $folder");

    return ($this->status == 'OK');

  }



  //// Resets to original

  function reset() {

    // Point it back to original

    imap_reopen($this->resource,imap_utf7_encode($this->reference), OP_HALFOPEN | CL_EXPUNGE);

  }



  //// Creates a mailbox 

  function create($folder) {

    // Make sure it doesn't already exist

    if (!$this->folded($folder)) {

      imap_createmailbox($this->resource, imap_utf7_encode("$this->reference$folder"));
      imap_subscribe($this->resource, imap_utf7_encode("$this->reference$folder"));

      return true;

    }

    // Something went wrong

    return false;

  }



  //// Copies a message to a new folder 

  function copy($from,$to,$from_id) {

    imap_reopen($this->resource,imap_utf7_encode("$this->reference$from"), CL_EXPUNGE);
    $status = imap_status($this->resource,imap_utf7_encode("$this->reference$to"), SA_UIDNEXT);

    if (imap_mail_copy($this->resource,$from_id,imap_utf7_encode($to),CP_UID))
      $to_id = $status->uidnext;
    else 
      $to_id = 0;

    // Point it back to original

    $this->reset();

    // Return teh id

    return $to_id;

  }



  //// Moves a message to a new folder 

  function move($from,$to,$from_id) {

    imap_reopen($this->resource,imap_utf7_encode("$this->reference$from"), CL_EXPUNGE);
    $status = imap_status($this->resource,imap_utf7_encode("$this->reference$to"), SA_UIDNEXT);

    if (imap_mail_move($this->resource,$from_id,imap_utf7_encode($to),CP_UID))
      $to_id = $status->uidnext;
    else 
      $to_id = 0;

    // Point it back to original

    $this->reset();

    // Return teh id

    return $to_id;

  }



  //// Deletes a message 

  function delete($folder,$uid) {

    imap_reopen($this->resource,imap_utf7_encode("$this->reference$folder"), CL_EXPUNGE);

    $success = imap_delete($this->resource,$uid,FT_UID);

    // Point it back to original

    $this->reset();

    // Return teh id

    return $success;


  }



  //// Removes a mailbox 

  function remove($folder) {

    // Make sure it doesn't already exist

    if ($this->folded($folder)) {

      imap_unsubscribe($this->resource, imap_utf7_encode("$this->reference$folder"));
      imap_deletemailbox($this->resource, imap_utf7_encode("$this->reference$folder"));

      return true;

    }

    // Something went wrong

    return false;

  }



  //// Gets the status of a mailbox

  function status($folder) {

    // Initialize

    $status = array();

    // Encode the folder

    $folder = imap_utf7_encode($folder);

    // Get the status

    $current = 0;
    $data = $this->enclose(preg_replace('/^[^\(]*/','',$this->command("T001","STATUS $folder (MESSAGES RECENT UIDNEXT UIDVALIDITY UNSEEN)")),$current);

    // In case the areas aren't in order 

    $current = 0;
    for ($area = 0; $area < 5; $area++) {

      // Get the first value, which will be
      // the name of the structure

      $name = $this->value($data,$current);

      // Depending on the name, do different
      // things

      if ($name == 'MESSAGES')
        $status['messages'] = $this->value($data,$current);
      elseif ($name == 'RECENT')
        $status['recent'] = $this->value($data,$current);
      elseif ($name == 'UIDNEXT')
        $status['uidnext'] = $this->value($data,$current);
      elseif ($name == 'UIDVALIDITY')
        $status['uidvalidity'] = $this->value($data,$current);
      elseif ($name == 'UNSEEN')
        $status['unseen'] = $this->value($data,$current);

    }

    // Return the status

    return $status;

  }



  //// Sets the flags 

  function flag($folder,$uid,$flags) {

    imap_reopen($this->resource,imap_utf7_encode("$this->reference$folder"), CL_EXPUNGE);

    $sets = array();
    $clears = array();
    $flagged = Relations_toHash($flags);

    if ($flagged['Unseen'])
      $clears[] = "\\Seen";
    else
      $sets[] = "\\Seen";

    if ($flagged['Answered'])
      $sets[] = "\\Answered";
    else
      $clears[] = "\\Answered";

    if ($flagged['Deleted'])
      $sets[] = "\\Deleted";
    else
      $clears[] = "\\Deleted";

    if ($flagged['Draft'])
      $sets[] = "\\Draft";
    else
      $clears[] = "\\Draft";

    if ($flagged['Flagged'])
      $sets[] = "\\Flagged";
    else
      $clears[] = "\\Flagged";

    if (count($sets))
      imap_setflag_full($this->resource,$uid,join(' ',$sets),ST_UID);

    if (count($clears))
      imap_clearflag_full($this->resource,$uid,join(' ',$clears),ST_UID);

    // Point it back to original

    $this->reset();

  }



  //// Climbs a mailbox in its entirety for folders

  function branch() {

    // Initialize the branches

    $branches = array();

    // Get a listing of all the folders

    $folders = imap_list($this->resource, $this->reference, "*");

    // Go through all the folders

    foreach ($folders as $folder)
      $branches[] = $this->strip(imap_utf7_decode($folder));

    // Return the branches

    return $branches;


  }



  //// Gets the total number of messages

  function total() {

    // Initialize the total

    $total = 0;

    // Get the folders

    $folders = $this->branch();

    // Go through and get the counts

    foreach ($folders as $folder) {

      $status = $this->status($folder);
      $total += $status['messages'];

    }

    // Send it back

    return $total;

  }



  //// Climbs a mailbox in its entirety for messages

  function climb() {

    // Initialize the routes

    $routes = array();

    // Get a listing of all the folders

    $folders = imap_list($this->resource, $this->reference, "*");

    // Go through all the folders

    foreach ($folders as $folder) {

      // Decode and extra the name

      $decoded = imap_utf7_decode($folder);
      $name = $this->strip($folder);

      // Get the headers

      $headers = $this->headers($name);

      // Go through them all and add to routes

      foreach ($headers as $header) {

        $routes[] = array(
          'folder' => $name,
          'uid'    => $header['uid'],
          'header' => $header['label'],
          'date'   => $header['date'],
          'subject' => $header['subject'],
          'from' => $this->addresses($header['from']),
          'sender' => $this->addresses($header['sender']),
          'reply_to' => $this->addresses($header['reply_to']),
          'to' => $this->addresses($header['to']),
          'cc' => $this->addresses($header['cc']),
          'bcc' => $this->addresses($header['bcc']),
          'in_reply_to' => $header['in_reply_to'],
          'message_id' => $header['message_id']
        );

      }

    }

    // Return the routes

    return $routes;


  }



  //// Grabs a message 

  function deliver($folder,$uid) {

    // Figure out the earliest text

    $attachments = array();
    $structure = $this->structure($folder,$uid);
    $this->section($structure);
    $this->attach($structure,$attachments);

    $body = '';
    foreach ($attachments as $attachment){
      if ($attachment['mime_type'] == 'text/plain') {
        $body = $attachment;
        break;
      }
    }

    if ($body)
      $body = $this->detach($folder,$uid,$body['section'],$body['encoding']);

    // Return the data

    return $body;

  }



  //// Goes through an address list and return a string 

  function addresses($addresses) {

    // $addresses - Array of addresses

    $values = array();

    // If it's something we can use

    foreach ($addresses as $address) {

      $email = array();

      if ($address['mailbox'])
        $email[] = $address['mailbox'];

      if ($address['host'])
        $email[] = $address['host'];

      $email = join('@',$email);

      if ($address['personal'] && $email)
        $values[] = "$address[personal] <$email>";
      elseif (!$address['personal'] && $email)
        $values[] = $email;
      elseif ($address['personal'] && !$email)
        $values[] = $address['personal'];

    }

    return join("; ",$values);


  }



  //// Gets all the header for a folder

  function headers($folder) {

    // Select the mail box

    $this->select($folder);

    // Get all the headers lines

    $lines = explode("\r\n",$this->command("H001","UID FETCH 1:* (ENVELOPE FLAGS)"));
    array_pop($lines);

    // Initialize the return array

    $headers = array();

    // Go through each line

    foreach ($lines as $line) {

      // Initialize current

      $current = 0;
      $data = $this->enclose(preg_replace('/^[^\(]*/','',$line),$current);

      // Create the record

      $header = array();

      // Reinitialize current and prep the data

      $current = 0;
      $envelope = '';
      $header['flags'] = '';
      $header['uid'] = '';

      // In case the areas aren't in order 

      for ($area = 0; $area < 3; $area++) {

        // Get the first value, which will be
        // the name of the structure

        $name = $this->value($data,$current);

        // Depending on the name, do different
        // things

        if ($name == 'ENVELOPE')
          $envelope = $this->enclose($data,$current);
        elseif ($name == 'FLAGS')
          $header['flags'] = preg_replace('/^\\\\*/','',$this->value($data,$current));
        elseif ($name == 'UID')
          $header['uid'] = $this->value($data,$current);

      }

      // If we got an envelope

      if ($envelope)
        $header = array_merge($header,$this->envelope($envelope));

      // Now figure out the label for the header

      $flagged = join('',preg_replace('/^(.).*/','\\1',$header['flags']));
      $from = $this->addresses($header['from']);

      $header['label'] = "$from $header[subject] $header[date]";
      
      if ($flagged)
        $header['label'] .= " ($flagged)";

      // Add to the array

      $headers[] = $header;

    }

    // Return 

    return $headers;

  }
  
  
  
  //// Parses the structure of a message and 
  //// return a data array, complete with
  //// section number for the body

  function structure($folder,$uid) {

    // First, select the folder

    $this->select($folder);

    // Then call the structure command

    $data = $this->command("T001","UID FETCH $uid (BODYSTRUCTURE)");

    // If the response is bad, return nothing

    if ($this->status != 'OK')
      return false;

    // Rip out the first and last ()'s,
    // get the structure, set all the 
    // sections, and return it

    $structure = $this->section(preg_replace('/^[^\(]*\([^\(]*\(|\)[^\)]*\)$/','',$data));
    $this->number($structure);
    return $structure;
      
  }


  
  //// Parses an envelope

  function envelope($data) {

    // Initialize current 

    $current = 0;
    $envelope = array();

    $envelope['date'] = $this->value($data,$current);
    $envelope['subject'] = $this->value($data,$current);
    $envelope['from'] = $this->address($data,$current);
    $envelope['sender'] = $this->address($data,$current);
    $envelope['reply_to'] = $this->address($data,$current);
    $envelope['to'] = $this->address($data,$current);
    $envelope['cc'] = $this->address($data,$current);
    $envelope['bcc'] = $this->address($data,$current);
    $envelope['in_reply_to'] = $this->value($data,$current);
    $envelope['message_id'] = preg_replace('/^\<|\>$/','',$this->value($data,$current));

    // Return the envelope

    return $envelope;

  }



  //// Parses an address list

  function address($data,&$current) {

    // Initialize current 

    $addresses = array();

    // Check to see if the first char is a 
    // parantheses

    if (substr($data,$current,1) == '(') {

      // Grab the section

      $addressed = $this->enclose($data,$current);

      // Initialize local

      $local = 0;

      // Get all the addresses

      while ($local < strlen($addressed))
        $addresses[] = $this->value($addressed,$local,array('personal','adl','mailbox','host'));

    } else {

      // Grab the NIL

      $this->value($data,$current);

    }

    // Return the addresses

    return $addresses;

  }



  //// Recursively sets the section number
  //// for a message structure

  function number(&$structure,$section='') {

    // If there's nothing in the structure,
    // take off
    
    if (!is_array($structure) || !count($structure))
      return;

    // If it's first

    if (($section == '') && ($structure['type'] != 'multipart'))
      $structure['section'] = 1;
    else
      $structure['section'] = $section;

    // Go through all the parts

    $current = 1;
    for ($part = 0; $part < count($structure['parts']); $part++) {

      if ($structure['parts'][$part]['type'] != 'message') {

        if ($structure['section'] == '')
          $next = $current;
        else
          $next = "$structure[section].$current";

        $current++;

        $this->number($structure['parts'][$part],$next);

      }

    }

  }




  //// Parses a section of data

  function section($data) {

    // Create the structure

    $parse = array();
    $parse['section'] = '';
    $parse['type'] = '';
    $parse['subtype'] = '';
    $parse['id'] = '';
    $parse['description'] = '';
    $parse['encoding'] = '';
    $parse['size'] = '';
    $parse['lines'] = '';
    $parse['parts'] = array();
    $parse['parameters'] = array();
    $parse['disposition'] = array();
    $parse['language'] = '';

    // First figure out the type

    $current = 0;
    while (substr($data,$current,1) == ' ')
      $current++;

    // Get the multipart stuff

    while (substr($data,$current,1) == '(')
      $parse['parts'][] = $this->section($this->enclose($data,$current));

    // If there's parts, then it's a multipart

    if (count($parse['parts'])) {

      // Set it

      $parse['type'] = 'multipart';

    } else {

      // Else get the real type, and set body
      // (if not)

      $parse['type'] = $this->value($data,$current);

    }

    // Get the subtype and parameters

    $parse['subtype'] = $this->value($data,$current);
    $parse['parameters'] = $this->value($data,$current,'hash');

    if (count($parse['parts'])) {

      // Get the disposition and language

      $parse['disposition'] = $this->value($data,$current,'hash');
      $parse['language'] = $this->value($data,$current);

    } else {

      // Get the id, description, encoding, size, lines

      $parse['id'] = $this->value($data,$current);
      $parse['description'] = $this->value($data,$current);
      $parse['encoding'] = $this->value($data,$current);
      $parse['size'] = $this->value($data,$current);
      $parse['lines'] = $this->value($data,$current);

    }

    return $parse;

  }



  //// Gets all data enclose by ()'s

  function enclose($data,&$current) {

    // Go through, looking for the next )

    $depth = 0;
    $start = ++$current;
    for ($current; $current < strlen($data); $current++) {

      $character = substr($data,$current,1);

      // If it's a )

      if ($character == ')') {

        // If we're at zero depth, we've found it

        if ($depth == 0) {

          $enclose = substr($data,$start,$current-$start);
          $current++;

          while (substr($data,$current,1) == ' ')
            $current++;

          return $enclose;

        } else {

          $depth--;

        }
      
      } elseif ($character == '(') {

        $depth++;

      }

    }

  }



  //// Gets the next value

  function value($data,&$current,$list='array') {

    // If the value's NIL, return nothing

    if (substr($data,$current,3) == 'NIL') {

      $current += 3;

      while (substr($data,$current,1) == ' ')
        $current++;

      return '';

    }

    // If the value start with a (

    if (substr($data,$current,1) == '(') {

      // Get the end 

      $values = $this->enclose($data,$current);
      $value = array();
      $local = 0;

      if (is_array($list)) {

        $listed = 0;
        while ($local < strlen($values))
          $value[$list[$listed++]] = $this->value($values,$local);

      } elseif ($list != 'hash') {

        while ($local < strlen($values))
          $value[] = $this->value($values,$local);

      } else {

        while ($local < strlen($values)) {

          $name = $this->value($values,$local);
          $value[$name] = $this->value($values,$local);

        }

      }

      while (substr($data,$current,1) == ' ')
        $current++;

      return $value;
    
    } elseif (substr($data,$current,1) == '"') {

      // Go through until the next non quote

      $start = ++$current;
      while ((substr($data,$current,1) != '"') && ($current < strlen($data)))
        $current++;

      $value = substr($data,$start,$current-$start);
      $current++;

      while (substr($data,$current,1) == ' ')
        $current++;

      return $value;

    } else {

      // Go through until the next non quote

      $start = $current;
      while ((substr($data,$current,1) != ' ') && ($current < strlen($data)))
        $current++;

      $value = substr($data,$start,$current-$start);

      while (substr($data,$current,1) == ' ')
        $current++;

      return $value;


    }

  }



  //// Gets a list of all the attachments, flattened
  //// from the structure

  function attach(&$structure,&$attachments) {

    // Go through this level first, only
    // assigning those that have a section

    if (strlen($structure['section'])) {

      // Copy

      $attachment = $structure;

      // Set the file name

      if ($attachment['parameters']['name'])
        $attachment['filename'] = $attachment['parameters']['name'];
      elseif ($attachment['disposition']['name'])
        $attachment['filename'] = $attachment['disposition']['name'];
      elseif ($attachment['subtype'] == 'html')
        $attachment['filename'] = "attachment.$attachment[subtype]";
      elseif ($attachment['type'] == 'text')
        $attachment['filename'] = "attachment.txt";
      elseif ($attachment['subtype'])
        $attachment['filename'] = "attachment.$attachment[subtype]";
      else
        $attachment['filename'] = "attachment";

      // Set the mime type

      $attachment['mime_type'] = $attachment['type'];
      if ($attachment['subtype'])
        $attachment['mime_type'] .= '/' . $attachment['subtype'];

      unset($attachment['parts']);
      $attachments[$attachment['section']] = $attachment;

    }

    // Go through all the parts, and have
    // them do the same

    for ($part = 0; $part < count($structure['parts']); $part++)
      $this->attach($structure['parts'][$part],$attachments);

  }



  //// Grabs and attachment and returns it

  function &detach($folder,$uid,$section,$encoding) {

    $this->select($this->socket,imap_utf7_encode($folder));
    $body = $this->command("B001","UID FETCH $uid (BODY.PEEK[$section])");

    // Trim the first line

    $data = preg_replace('/^[^\r]+\r\n|\r\n.*$/','',$body);

    // Figure out how to decode it

    switch ($encoding) {

      case 4:
      case '4':
      case 'quoted-printable':

        return quoted_printable_decode($data);

      case 3:
      case '3':
      case 'base64':

        return base64_decode($data);

      default:

        return $data;

    }

  }



  // Returns the errors 

  function errors() {

    // Just send back the regular

    return imap_errors();

  }

}

?>