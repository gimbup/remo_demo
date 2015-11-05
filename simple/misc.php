 <?php

// -- Functions for database access to get instructor/semester info
// -- Doug DeCarlo  11/2007

// Run query and return first value
// (or default if fails or empty)
function queryValue($query, $default=null, $htmlspec=true)
{
    $result = mysql_query($query);
    if (!$result || mysql_num_rows($result) < 1) {
        return $default;
    }
    if ($htmlspec) {
      return htmlspecialchars(mysql_result($result, 0), ENT_QUOTES);
    } else {
      return mysql_result($result, 0);
    }
}

// Return instructor name
function instructorID($pw)
{
  if ($pw == null || $pw == "") {
     die("Password required.");
  }
  $query = "select iid from instructor where pass='$pw'";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown instructor id='$pw'  " . mysql_error());
  }

  return mysql_result($result, 0);
}

// Return instructor name
function instructorName($iid, $lastnameonly=false)
{
  if (!$iid) {
     die("Need instructor id.");
  }
  $query = "select firstname,lastname from instructor where iid='$iid'";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown instructor id='$iid'  " . mysql_error());
  }

  $row = mysql_fetch_assoc($result);
  return $lastnameonly ? $row["lastname"] : ($row["firstname"] . '&nbsp;' . $row["lastname"]);
}

// Get list of instructor names given class id
// (typically one instructor)
function instructorNames($clid, $lastOnly=false, $sep=", ")
{
    $query = "select instructor.firstname,instructor.lastname from class, classAssignment, instructor " . 
             "where instructor.iid=classAssignment.iid and classAssignment.clid=$clid and class.clid=$clid";

    $result = mysql_query($query);
    if (!$result) return "";

    $name = "";
    $first = true;
    while ($row = mysql_fetch_assoc($result)) {
        if ($first) {
            $first = false;
        } else {
            $name .= $sep;
        }
        if ($lastOnly) {
            $name .= $row['lastname'];
        } else {
            $name .= $row['firstname'] . ' ' . $row['lastname'];
        }
    }
    
    if ($name == "")
      $name = "(unassigned)";

    return htmlspecialchars($name, ENT_QUOTES);
}

// Return instructor email
function instructorEmail($iid)
{
  if (!$iid) {
     die("Need instructor id.");
  }
  $query = "select email from instructor where iid='$iid'";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown instructor id='$iid'  " . mysql_error());
  }

  return mysql_result($result, 0);
}

// Return boolean for whether instructor is a professor (not a PTL)
function instructorIsProf($iid)
{
  if (!$iid) {
     die("Need instructor id.");
  }
  $query = "select position from instructor where iid='$iid' and position='professor'";
  $result = mysql_query($query);

  if (!$result) {
     die("Unknown instructor id='$iid'  " . mysql_error());
  }

  // Return true if this is a professor (a non-empty query)
  return mysql_num_rows($result) >= 1;
}

function nextSemester($sid)
{
  return queryValue("select sid from semester where snum=(select min(snum) from semester where snum > (select snum from semester where sid='$sid'))");
}

// List of courses taught by iid in semester sid
function semesterCourses($sid, $iid, $withnotes=false, $sep=", ")
{
  $query = "select class.cid, name, ifnull(class.cidShow,class.cid) as cidShow from class,classAssignment,course where course.cid=class.cid and class.clid=classAssignment.clid";
  $query .= " and sid='$sid' and iid=$iid";
  $result = mysql_query($query);
  if (!$result) {
    die("Cannot get courses. " . mysql_error());
  }
  $res = "";
  $first = true;
  while ($row = mysql_fetch_assoc($result)) {
    if (!$first)
      $res .= $sep;
    $first = false;
    $res .= $row['cidShow'];
    if ($withnotes)
      $res .= " (" . shortCourseName($row['name']) . ")";
  }
  return $res;
}

// List of commitments for iid in semester sid
function semesterCommitments($sid, $iid, $withnotes=false, $sep=", ")
{
  $query = "select reason, notes from commitment,semester where commitment.sid=semester.sid and iid=$iid and semester.sid='$sid'";
  $result = mysql_query($query);
  if (!$result) {
    die("Cannot get commitments. " . mysql_error());
  }
  $res = "";
  $first = true;
  while ($row = mysql_fetch_assoc($result)) {
    if (!$first)
      $res .= $sep;
    $first = false;
    $res .= $row['reason'];
    if ($withnotes && $row['notes'] != null)
      $res .= " (" . $row['notes'] . ")";
  }
  return $res;
}

// Make out list of commitments for selection menu
function commitmentOptionList($selectedSid, $selectedIid, $selectedCoid)
{
  $query = "select coid,commitment.sid,commitment.iid,reason from commitment,instructor,semester ";
  $query .= "where commitment.iid=instructor.iid and commitment.sid=semester.sid ";
  if ($selectedSid != null) $query .= "and commitment.sid='$selectedSid' ";
  if ($selectedIid != null) $query .= "and commitment.iid='$selectedIid' ";
  $query .= "order by lastname, firstname, snum";

  $result = mysql_query($query);
  if (!$result) {
    die("Cannot get commitments. " . mysql_error());
  }
  echo "  <option value='-1'" . 
    ($selectedCoid == -1 ? " selected='yes'" : "") .
    ">New commitment</option>";
  
  while ($row = mysql_fetch_assoc($result)) {
    $coid = $row['coid'];
    $name = instructorName($row['iid'], true) . " (" . semesterName($row['sid']) . "): " . $row['reason'];
    
    echo "  <option value='$coid'" . 
      ($selectedCoid == $coid ? " selected='yes'" : "") . ">$name</option>\n";
  }
}

function shortCourseName($cname)
{
  $dict = array("Advanced" => "Adv",
		"Algorithms" => "Alg",
		"Analysis" => "An",
		"Application" => "App",
		"Applications" => "App",
		"Applied" => "App",
		"Architecture" => "Arch",
		"Aspects" => "Asp",
		"Biomedicine" => "Biomed",
		"Combinatorial" => "Comb",
		"Complexity" => "Cplx",
		"Commerce" => "Comm",
		"Computation" => "Comp",
		"Computational" => "Comp",
		"Computer" => "Comp",
		"Computers" => "Comp",
		"Computing" => "Comp",
		"Design" => "Des",
		"Differential" => "Diff",
		"Distributed" => "Dist",
		"Electronic" => "E",
		"Engineering" => "Eng",
		"Equations" => "Eqs",
		"Foundations" => "Fnds",
		"Implementation" => "Imp",
		"Information" => "Info",
		"Insights" => "Ins",
		"Intelligence" => "Intel",
		"Introduction" => "Intro",
		"Knowledge" => "Know",
		"Language" => "Lang",
		"Languages" => "Lang",
		"Management" => "Manage",
		"Methodology" => "Meth",
		"Methods" => "Meth",
		"Multimedia" => "MM",
		"Operating" => "Oper",
		"Optimization" => "Opt",
		"Pattern" => "Pat",
		"Physical" => "Phys",
		"Principles" => "Prin",
		"Programming" => "Prog",
		"Recognition" => "Rec",
		"Representation" => "Rep",
		"Science" => "Sci",
		"Secure" => "Sec",
		"Security" => "Sec",
		"Services" => "Serv",
		"Seminar" => "Sem",
		"Software" => "Soft",
		"Structures" => "Struct",
		"Systems" => "Sys",
		"Technology" => "Tech",
		"Topics" => "Top",
		"The" => "the", 
		"and" => "", "the" => "", "to" => "", "in" => "", "for" => "", "of" => "");

  $words = preg_split("/[\s]+/", $cname);
  $short = "";
  $first = true;
  foreach ($words as $word) {
    $word = chop($word, ':,');
    $sword = isset($dict[$word]) ? $dict[$word] : $word;
    if ($sword == "") continue;
    if (!$first) $short .= " ";
    $first = false;
    $short .= $sword;
  }
  return $short;
}

// Form option list of courses
// -- mark selectedCid as selected
function courseOptionList($selectedCid="", $skip000 = true, $shorten = false, $defaultText = null)
{
    $query = "select cid,name from course ";
    if ($skip000) $query .= "where cid <> '000' ";
    $query .= "order by cid";

    $result = mysql_query($query);
    if (!$result) {
        die("Cannot get courses. " . mysql_error());
    }

    echo "  <option value=''" . 
        ($selectedCid == "" ? " selected='yes'" : "") .
        ">$defaultText</option>";

    while ($row = mysql_fetch_assoc($result)) {
	$cid = htmlspecialchars($row['cid'], ENT_QUOTES);
	if ($cid == '0') $cid = '000';
	$thisname = $row['name'];
	if ($shorten) $thisname = shortCourseName($thisname);
	$name = htmlspecialchars($thisname, ENT_QUOTES);

        echo "  <option value='$cid'" . 
            ($selectedCid == $cid ? " selected='yes'" : "") .
            ">$cid ($name)</option>\n";
    }    
}

// Form option list of instructors
// -- mark selectedIid as selected
function instructorOptionList($selectedIid="", $listpw = false, $defaultText=null)
{
    // XXX skip test names...
    if ($listpw) {
      $query = "select pass as iid,";
    } else {
      $query = "select iid,";
    }
    $query .= "lastname,firstname from instructor where lastname != 'Test' order by lastname,firstname";

    $result = mysql_query($query);
    if (!$result) {
        die("Cannot get instructors. " . mysql_error());
    }

    echo "  <option value=''" . 
        ($selectedIid == "" ? " selected='yes'" : "") .
        ">$defaultText</option>";

    while ($row = mysql_fetch_assoc($result)) {
        $iid = $row['iid'];
	$lname = htmlspecialchars($row['lastname'], ENT_QUOTES);
	$fname = htmlspecialchars($row['firstname'], ENT_QUOTES);

        echo "  <option value='$iid'" . 
            ($selectedIid == $iid ? " selected='yes'" : "") .
            ">$lname, $fname</option>\n";
    }    
}

// Print out options list of semesters, where first one is default
// (for use in a <select>)
function semesterOptionList($selectedSid="", $addblank=false, $defaultText=null, $fallOnly=false)
{
  // Make options list of semesters in database
   $result = mysql_query("select sid,name,snum from semester " . 
			 ($fallOnly ? "where sid regexp '^f' ": "") . "order by snum desc");					 
  if (!$result) {
    die("Cannot get semester list. " . mysql_error());
  }

  // Add blank semester
  if ($addblank) {
    echo "  <option value=''";
    if ($selectedSid=="") echo " selected='yes'";
    echo ">$defaultText</option>\r\n";
  }

  // If no selection specified, use current "active" semester
  if ($selectedSid == "")
    $selectedSid = queryValue("select sid from semester where status='A'", "");

  // List semesters
  $first = true;
  while ($row = mysql_fetch_assoc($result)) {
    if ($first) {
      // Select first one if no selection specified
      $first = false;
      if ($selectedSid == "" && !$addblank) {
	$sel = "selected='yes'";
      }
    } else {
      $sel = "";
    }

    // Mark designed one as selected
    if ($selectedSid == $row['sid']) {
      $sel = "selected='yes'";
    }
    echo "  <option $sel value='" . $row['sid'] . "'>" . $row['name'] . "</option>\r\n";
  }
}

// Return name of semester sid
function semesterName($sid)
{
  if (!$sid) {
     die("Need semester id.");
  }
  $query = "select name from semester where sid='$sid'";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown semester id=$sid: " . mysql_error());
  }

  return mysql_result($result, 0);
}

// Return status of semester sid
function semesterStatus($sid)
{
  if (!$sid) {
     die("Need semester id.");
  }
  $query = "select status from semester where sid='$sid'";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown semester id=$sid: " . mysql_error());
  }

  return mysql_result($result, 0);
}

// ------------

function dutyName($dutyid)
{
  $query = "select name from duty where dutyid=$dutyid";
  $result = mysql_query($query);

  if (!$result || mysql_num_rows($result) < 1) {
     die("Unknown duty $dutyid: " . mysql_error());
  }

  return mysql_result($result, 0);
}

// Make out list of duties for selection menu
function dutyOptionList($selectedDutyid=null, $sid=null, $addBlank=false)
{
  if ($sid != null) {
    // Get standing and occurring duties for a particular semester
    $query = "select duty.dutyid,name,standing from duty left join dutyOccurrence on duty.dutyid=dutyOccurrence.dutyid where duty.standing=1 or dutyOccurrence.sid='$sid' order by name";
  } else {
    // Get all duties, standing or not
    $query = "select dutyid,name from duty order by name";
  }

  $result = mysql_query($query);
  if (!$result) {
    die("Cannot get duties. " . mysql_error());
  }
  if ($addBlank) {
    echo "  <option value='-1'" . 
      ($selectedDutyid == -1 ? " selected='yes'" : "") .
      ">New duty</option>";
  }
  
  while ($row = mysql_fetch_assoc($result)) {
    $dutyid = $row['dutyid'];
    $name = $row['name'];

    // Take first one as selected if nothing was
    if ($selectedDutyid == null) $selectedDutyid=$dutyid;
    
    echo "  <option value='$dutyid'" . 
      ($selectedDutyid == $dutyid ? " selected='yes'" : "") . ">$name</option>\n";
  }
}

// Make out list of duties for selection menu
function areaList($selectedArea)
{
  $query = "select name from area";

  $result = mysql_query($query);
  if (!$result) {
    die("Cannot get areas. " . mysql_error());
  }

  if ($selectedArea == "")
    echo "  <option value='' selected='yes'></option>\n";

  while ($row = mysql_fetch_assoc($result)) {
    $name = $row['name'];
    
    echo "  <option value='$name'" . 
      ($selectedArea == $name ? " selected='yes'" : "") . ">$name</option>\n";
  }
}

// -------------


// Check if present (if any changes at all)
function logPresent($table, $iid, $sid)
{
  $query = "select max(ts) AS ts from $table where iid='$iid'";
  if ($sid) $query = $query . " and sid='$sid'";

  $result = mysql_query($query);
  if (!$result) {
    die("Cannot check log " . mysql_error());
  }
  if (mysql_num_rows($result) < 1) {
    return false;
  }
  if (mysql_result($result, 0) == null)
    return false;
  
  return true;
}

// Get most recent date of change
function logGetDate($table, $iid, $sid, $format)
{
  $query = "select date_format(max(ts), '$format') AS ts from $table where iid='$iid'";
  if ($sid) $query = $query . " and sid='$sid'";

  $result = mysql_query($query);
  if (!$result) {
    die("Cannot check log " . mysql_error());
  }
  if (mysql_num_rows($result) < 1) {
    return false;
  }

  return htmlspecialchars(mysql_result($result, 0), ENT_QUOTES);
}

// Generate random password based on a seed string (deterministic given string)
function generatePassword($seedstring, $length=8)
{
  $seed = 0;
  // Crappy way to generate seed based on string
  for ($i = 1; $i < strlen($seedstring); $i++) {
    $c = ord(substr($seedstring, $i, 1));
    if ($i % 2 == 0) {
      $seed += $c * $c + 3 * $c + 7;
    } else {
      $seed = (floor($seed / 37) * $c) % 9167433;
    }
  }
  srand($seed);

  $chars = "aeuybdghjmnpqrstvzBDGHJLMNPQRSTVWXZAEUY";
  $password = '';
  for ($i = 0; $i < $length; $i++) {
    $password .= $chars[rand() % strlen($chars)];
  }
  return $password;
}

// Form error message string for status bar
function errorText($text)
{
    return "<font size=+1 color=red><b>Error:</b></font> $text<br/>";
}

?>
