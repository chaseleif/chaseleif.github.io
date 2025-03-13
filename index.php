<?php
  /*
  session_start();
  if (!isset($_SESSION["phiki_theme"])) {
    $_SESSION["phiki_theme"] = "solarized-dark";
  }
  $themes = array();
  foreach (scandir('resources/themes') as $theme) {
    if (str_ends_with($theme, '.json')) {
      $themes[] = pathinfo($theme)['filename'];
    }
  }
  foreach ($themes as $theme) {
    echo "$theme<br>";
  }
  $s = 'FILE: arg.sh  FILE: boo.py  FILE: demo.c';
  $pattern = '/FILE: (?<name>\S+)/';
  if (preg_match_all($pattern, $s, $matches)) {
    $parts = array_filter($matches,
                          fn($key)=>is_string($key), ARRAY_FILTER_USE_KEY);
    foreach ($parts['name'] as $part) {
      if (is_file($part)) {
        echo $part . "<br>";
        print_r(pathinfo($part));
        // ['dirname'='.', 'basename'='arg.sh',
        // 'extension'='sh', 'filename'='arg']
        echo "<br>";
      }
    }
  }
  */
  require_once "Phiki/Autoloader.php";
  spl_autoload_register("Phiki\\Autoloader::load");
  function code2html($file) {
    $text = file_get_contents($file);
    if ($text === false) return;
    $text = trim($text);
    $theme = "solarized-dark";
    $code_hl = new Phiki\Phiki();
    switch (pathinfo($file)['extension']) {
      case 'c': $lang='c'; break;
      case 'cpp': $lang='cpp'; break;
      case 'py': $lang='python'; break;
      case 'sh': $lang='shellscript'; break;
      case 'tex': $lang='latex'; break;
      case 'diff': case 'patch': $lang='diff'; break;
      default: $lang='txt'; break;
    }
    return $code_hl->codeToHTML($text, $lang, $theme);
  }
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
  else if (isset($_GET['samples'])) {
    $title = "Samples";
    $desc = "Samples";
    $body = '';
    $listing = file_get_contents('samples/listing');
    preg_match_all("/(.+)=(.+)/", $listing, $listing);
    foreach ($listing[0] as $topic) {
      $topic = preg_split("/=/", $topic);
      $body .= "<h4>" . $topic[0] . "</h4>";
      $body .= '<span class="subsubbr"></span>';
      $body .= '<ul type="none">';
      foreach (scandir("samples/" . $topic[1]) as $file) {
        if ($file === "." || $file === "..") { continue; }
        if (str_ends_with($file, '.nfo')) { continue; }
        $name = pathinfo($file)['filename'];
        $body .= '<li><a href="index.php?samples&' . $name . '">';
        $body .= "<b>$file</b></a>";
        if (is_file("samples/$topic[1]/$file.nfo")) {
          $body .= "&nbsp;&mdash;&nbsp;";
          $body .= file_get_contents("samples/$topic[1]/$file.nfo");
        }
        if (isset($_GET[$name])) {
          $body .= ":FILE=samples/$topic[1]/$file";
        }
        $body .= '</li>';
      }
      $body .= '</ul>';
      $body .= '<hr width="50%" color="#99A3A4" align="left" />';
    }
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
  preg_match_all("/:FILE=([^<]+)/", $page, $matches);
  foreach ($matches[1] as $match) {
    $page = str_replace("FILE=$match", code2html($match), $page);
  }
  echo $page;
?>
