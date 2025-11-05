<?php
  $page = substr($_SERVER['REQUEST_URI'],1,10);
  if ($page !== '' && !preg_match('/index\.php\??$/i', $page)) {
    $root = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $page = $root . '://' . getenv('HTTP_HOST');
    header("Location: $page", true, 301);
    exit();
  }
  $page = file_get_contents('page.html');
  if (isset($_GET['aboutme'])) {
    $title = 'About Me';
    $desc = 'About me';
    $body = file_get_contents('pages/aboutme.html');
  }
  else if (isset($_GET['resume'])) {
    $title = 'My Resume';
    $desc = 'My resume';
    $body = file_get_contents('pages/resume.html');
  }
  else if (isset($_GET['papers'])) {
    $title = 'My Research';
    $desc = 'My research';
    $body = file_get_contents('pages/papers.html');
  }
  else if (isset($_GET['teaching'])) {
    $title = 'Teaching';
    $desc = 'Teaching';
    $body = file_get_contents('pages/teaching.html');
  }
  else if (isset($_GET['karaoke']) && is_file("images/faber.mp4")) {
    $page = file_get_contents('blank.html');
    $title = 'Faber - In Paris brennen Autos';
    $desc = 'Karaoke';
    $body = '<h3><div class="hcenter">';
    $body .= 'Faber &mdash; In Paris brennen Autos</h3></div>';
    $body .= '<video controls autoplay ';
    $body .= 'style="max-width:95%; max-height:80%; ';
    $body .= 'position:absolute;" ';
    $body .= 'poster="images/faber.jpg">';
    $body .= '<source src="images/faber.mp4" type="video/mp4">';
    $body .= '</video>';
  }
  else {
    $title = 'Chase Phelps';
    $desc = 'Hallo';
    $body = '<h3><div class="hcenter"><br>';
    $body .= 'Hallo!';
    $body .= '<br><br>';
    $body .= '<figure><img src="images/hallo.jpeg"></figure>';
    $body .= '<br><br><br>';
    $body .= '<a href="http://u.fsf.org/16f" target="_blank">';
    $body .= '<img src="images/gnu.png" alt="Powered by GNU">';
    $body .= '</a>';
    $body .= '&nbsp;&nbsp;';
    $body .= '<a href="http://u.fsf.org/16e" target="_blank">';
    $body .= '<img src="images/fsf.png" alt="Free Software Foundation">';
    $body .= '</a>';
  }
  $page = str_replace('TITLETEXT',$title,$page);
  $page = str_replace('DESCRIPTIONTEXT',$desc,$page);
  $page = str_replace('PAGEBODY',$body,$page);
  $page = preg_replace('/\n+/','',$page);
  $page = preg_replace('/\s+/',' ',$page);
  $page = preg_replace('/>\s+</','><',$page);
  $page = str_replace('a href=','a style="color:#4bb4e6" href=',$page);
  preg_match_all('/:FILE=([^<]+)/', $page, $matches);
  foreach ($matches[1] as $match) {
    $page = str_replace("FILE=$match", code2html($match), $page);
  }
  echo $page;
?>
