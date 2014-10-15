<?php
ini_set("display_errors", "1");
error_reporting(E_ALL);
?>
<?php

// This file implements a simple password-based login facility. Just include it
// at the top of the page's main file, before anything else is done. The
// passwords are stored in $password_db, which is a simple two-column CSV file.
// The first column is the username, and the second column is the password.

require_once "config.php";

$password_db = datadir() . "/users.csv";
session_start();

// If we are not logged in, either display a form or check that it was
// submitted correctly.
if (!isset($_SESSION["is_logged_in"])) {
  $message = "";

  // If the form was submitted, ...
  if (isset($_POST["username"]) && isset($_POST["password"])) {

    // ... check that it was submitted correctly.
    $ok = FALSE;
    $f = fopen($password_db, "r");
    if ($f !== FALSE) {
      while (($rec = fgetcsv($f)) !== FALSE) {
        if ($rec[0] == $_POST["username"] && $rec[1] == $_POST["password"]) {
          $ok = TRUE;
          break;
        }
      }
    }

    // If so, log the user in and redirect back to ourself (to clear the POST
    // variables).
    if ($ok) {
      $_SESSION["is_logged_in"] = TRUE;
      header("Location: " . $_SERVER["REQUEST_URI"]);
      die();
    }

    // If not, set an error message to tell the user so.
    else {
      $message = "<div class='login-error'>Invalid username or password.</div>";
    }
  } 

  // Display the form.
  include "header.php"; 
  ?>
  <style>
  .login-error {
    color: white;
    background: red;
    padding: 0.6ex;
    margin: 1.2ex;
  }
  </style>
  <h1>Wisconsin Breast Cancer GWAS Catalog</h1>
  <p>Welcome to the Wisconsin Breast Cancer GWAS Catalog, or whatever this is
  going to be called. More info about the study goes here.</p>
  <p>Log in to see this data. To request an account, please email the authors
  (tbd@wisc.edu).</p>
  <?php echo $message; ?>
  <div>
  <form method="post">
  <table>
  <tr><td>Username:</td><td><input type="text" name="username"></td></tr>
  <tr><td>Password:</td><td><input type="password" name="password"></td></tr>
  <tr><td></td><td><input type="submit"></td></tr>
  </table>
  </form>
  </div>
  <?php

  // Die, since we are not logged in.
  die();
}

?>
