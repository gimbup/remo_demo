<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">

<head>
<title>Update Instructor</title>
<style>
.error {color: #FF0000;}
h2{text-align: center;}
.createnew{margin-left: auto; margin-right: auto;}
.notice{background:yellow; color: red; text-align: center;}
.list{text-align: center; margin-left: auto; margin-right: auto; border-collapse:collapse;}
.list, .row {border: 1px solid black;}
.row{height: 50px; width: 200px;}
</style>
</head>

<body>

<?php
require('login.php');
$db = connect();
require ('misc.php');


$firstnameErr = $lastnameErr = $emailErr = $positionErr = "";
//$firstname = $lastname = $email = $position = "";

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if($_POST["action"] == "Create"){
        createInstructor();
    }
    if($_POST["action"] == "Delete"){
        deleteInstructor($_POST["lastname"], $_POST["firstname"]);
    }
}

function createInstructor(){
    $flag = true;

    if(empty($_POST["lastname"])){
        global $lastnameErr;
        $lastnameErr = "Last name is required";
        $flag = false;
    }else{
        $lastname = test_input($_POST["lastname"]);
    }    
    if(empty($_POST["firstname"])){
        global $firstnameErr; 
        $firstnameErr = "First name is required";
        $flag = false;
    }else{
        $firstname = test_input($_POST["firstname"]);
    }                                              

    if(empty($_POST["email"])){
        global $emailErr;
        $emailErr = "Email is required";
        $flag = false;
    }else{
        $email = test_input($_POST["email"]);
    }

    $position = $_POST["position"];
    
    if($flag == true){
        $firstname = mysql_real_escape_string($firstname);
        $lastname = mysql_real_escape_string($lastname);
        $email = mysql_real_escape_string($email);
        $position = mysql_real_escape_string($position);

        $pw = generatePassword($firstname . " " . $lastname);
        
        $query = "SELECT * FROM Instructor WHERE firstname='$firstname' AND lastname='$lastname'";//change tablename
        $result = mysql_query($query);
        if ($result != null && mysql_num_rows($result) >= 1) {
           echo "<p class=notice>Unable to create. Instructor already exists. If you want to change information please first delete from the list below.</p>";
        }else{
           $query = "INSERT INTO Instructor (firstname, lastname, email, position,pass) VALUES ( '$firstname', '$lastname', '$email', '$position', '$pw')";//change tablename
           $result = mysql_query($query);
           if (!$result) {
               die("Cannot create instructor: " . mysql_error());
           } else {
               echo "<p class=notice>New instructor ". $firstname . " " . $lastname. " is created.</p>";
           }
        }
    }
}
function test_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function deleteInstructor($lastname, $firstname){
    $firstname = mysql_real_escape_string($firstname);
    $lastname = mysql_real_escape_string($lastname);

    $query = "DELETE FROM Instructor Where firstname='$firstname' AND lastname='$lastname'";//change tablename
    if(mysql_query($query)){
        echo "<p class=notice>Instructor ". $firstname . " ". $lastname .  " is deleted. </p>";
    }else{
        die ("Error deleting record: " . mysql_error());
    }
}
?>



<h2>Create New Instructor</h2>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>"> 

<table class="createnew">
  <tr><td>Last name</td>  
      <td> <input type="text" size=30 name='lastname' value=''></td>
      <td> <span class="error">* <?php echo $lastnameErr;?></span></td>
      </tr>
  <tr><td>First name</td> 
      <td> <input type="text" size=30 name='firstname' value=''></td>
      <td> <span class="error">* <?php echo $firstnameErr;?></span></td></tr>
  <tr><td>Position</td>
    <td>
      <select name='position'>
      <option selected='yes' value='professor'>Professor</option>
      <option value='instructor'>Staff instructor</option>
      <option value='ptl'>Part-time lecturer (PTL)</option>
      <option value='ta'>Teaching Assistant (TA)</option>
      <option value='postdoc'>Post doc</option>
      <option value='visitor'>Visitor/Visiting Professor</option>
      <option value='other'>Other</option>
      </select>
    </td>
    <td> <span class="error">* </span></td></tr>
  <tr><td> Email</td> 
      <td><input type="text" size=30 name='email' value=''></td>
      <td> <span class="error">* <?php echo $emailErr;?></span></td></tr>
</table>
<center><input type="submit" name="action" value="Create"></center>

</form>

<h2>Active Instructor List</h2>
<table class="list">
  <tbody><tr>
    <td class="row">Name</td>
    <td class="row">E-mail</td>
    <td class="row">Position</td>
  </tr></tbody>
  <?php
  $query = "SELECT lastname, firstname, email, position FROM Instructor WHERE active = 1";//change talbename
  $result = mysql_query($query);

  if (mysql_num_rows($result) > 0){
    //$serverString = htmlspecialchars($_SERVER['PHP_SELF']);
    while($row = mysql_fetch_assoc($result)){
        echo "<tr><form method='post' action=". htmlspecialchars($_SERVER["PHP_SELF"]) . "> 
        <td class=row>". $row["firstname"]. " " . $row["lastname"] . "</td>
        <td class=row>". $row["email"]. "</td>
        <td class=row>". $row["position"]. "</td>
        <td><input type='hidden' name='firstname' value=". $row["firstname"] . "></td>
        <td><input type='hidden' name='lastname' value=". $row["lastname"] . "></td>
        <td><input type='submit' name='action' value='Delete'></td>
        </form></tr>";
    }
  }

  ?>
</table>
</body>
</html>
