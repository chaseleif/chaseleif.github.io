<?php
  $page = file_get_contents('page.html');
  if (isset($_GET['aboutme'])) {
    $title = "About Me";
    $desc = "About me";
    $body = file_get_contents('pages/about.html');
  }
  else if (isset($_GET['resume'])) {
    $title = "My Resume";
    $desc = "My resume";
    $body = file_get_contents('pages/resume.html');
  }
  else if (isset($_GET['papers'])) {
    $title = "My Research";
    $desc = "My research";
    $body = file_get_contents('pages/papers.html');
  }
  else if (isset($_GET['teaching'])) {
    $title = "Teaching";
    $desc = "Teaching";
    $body = file_get_contents('pages/teaching.html');
  }
  else if (isset($_GET['activities'])) {
    $title = "Activities";
    $desc = "Activities";
    $body = file_get_contents('pages/activities.html');
  }
  else {
    $title = "";
    $desc = "";
    $body = "<h3><div class=\"hcenter\"><br>";
    $body .= "Hallo!";
    $body .= "<br><br>";
    $body .= "<figure><img src=\"./images/hallo.jpeg\"></figure>";
  }
  $page = str_replace("TITLETEXT",$title,$page);
  $page = str_replace("DESCRIPTIONTEXT",$desc,$page);
  $page = str_replace("PAGEBODY",$body,$page);
  $page = preg_replace('/\n+/','',$page);
  $page = preg_replace('/\s+/',' ',$page);
  echo $page;
?>
