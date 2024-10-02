<?php
  $page = file_get_contents('page.html');
  if (isset($_GET['aboutme'])) {
    $title = "About Me";
    $desc = "About me";
    $body = file_get_contents('pages/aboutme.html');
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
    $title = "Chase Phelps";
    $desc = "Hallo";
    $body = "<h3><div class=\"hcenter\"><br>";
    $body .= "Hallo!";
    $body .= "<br><br>";
    $body .= "<figure><img src=\"./images/hallo.jpeg\"></figure>";
    $body .= "<br><br><br>";
    $body .= "<a href=\"http://u.fsf.org/16f\" target=\"_blank\">";
    $body .= "<img src=\"images/gnu.png\" alt=\"Powered by GNU\">";
    $body .= "</a>";
    $body .= "&nbsp;&nbsp;";
    $body .= "<a href=\"http://u.fsf.org/16e\" target=\"_blank\">";
    $body .= "<img src=\"images/fsf.png\" alt=\"Free Software Foundation\">";
    $body .= "</a>";
  }
  $page = str_replace("TITLETEXT",$title,$page);
  $page = str_replace("DESCRIPTIONTEXT",$desc,$page);
  $page = str_replace("PAGEBODY",$body,$page);
  $page = preg_replace('/\n+/','',$page);
  $page = preg_replace('/\s+/',' ',$page);
  $page = preg_replace('/>\s+</','><',$page);
  echo $page;
?>
