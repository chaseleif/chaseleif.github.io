<?php
  function setvars($vars, $body) {
    foreach ($vars as $key => $value) {
      $body = str_replace($key, $value, $body);
    }
    $body = str_replace('ERRORMSG', '', $body);
    return $body;
  }
  $vars = [];
  if (isset($_POST['firstname']) && isset($_POST['lastname'])) {
    $who = strtolower($_POST['firstname'])
          . '~' . strtolower($_POST['lastname']);
  }
  else if (isset($_POST['who'])) {
    $who = strtolower($_POST['who']);
  }
  else if (isset($_GET['who'])) {
    $who = strtolower($_GET['who']);
  }
  if (isset($who)) {
    if (substr_count($who, '~') !== 1) {
      $vars['ERRORMSG'] = 'Who';
    }
    else {
      $who = ucwords(str_replace('~', ' ', $who), ' ');
      $members = file('members', FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
      if (!in_array($who, $members)) {
        $vars['ERRORMSG'] = $who;
      }
    }
    if (isset($vars['ERRORMSG'])) {
      $vars['ERRORMSG'] = '<h3>' . $vars['ERRORMSG'] . ' ?</h3>';
    }
    else {
      $vars['WHO'] = $who;
    }
    unset($who);
  }
  if (!isset($vars['WHO'])) {
    $body = file_get_contents('pages/setname.html');
  }
  else {
    $filename = 'times';
    $memberid = md5($vars['WHO']);
    $availabilities = [];
    $myavailability = [];
    $otheravailable = [];
    if (file_exists($filename)) {
      $file = fopen($filename, 'r');
      $counter = 0;
      $delindex = isset($_POST['delindex']) ? $_POST['delindex'] : -1;
      if ($file) {
        $inmember = 0;
        while (($line = fgets($file)) !== false) {
          $line = rtrim($line);
          if (empty($line)) { }
          elseif ($inmember === 0) {
            if (strpos($line, '-') !== false) {
              array_push($availabilities, $line);
              continue;
            }
            if (strcmp($line, $memberid) !== 0) {
              array_push($availabilities, $line);
              continue;
            }
            $inmember = 1;
          }
          elseif ($inmember === 1) {
            if (strpos($line, '-') !== false) {
              if ($counter++ != $delindex) {
                array_push($myavailability, $line);
              }
            }
            else {
              array_push($availabilities, $line);
              $inmember = 2;
            }
          }
          else {
            array_push($availabilities, $line);
          }
        }
        fclose($file);
      }
    }
    if (isset($_POST['from']) && isset($_POST['until'])) {
      $_POST['from'] /= 1000;
      $_POST['until'] /= 1000;
      array_push($myavailability,$_POST['from'] . '-' . $_POST['until']);
      unset($_POST['from']);
      unset($_POST['until']);
    }
    function timerange2str($timerange) {
      $timerange = explode('-', $timerange);
      return date('D M j', $timerange[0])
            . ', between '
            . date('H:i', $timerange[0])
            . ' and '
            . date('H:i', $timerange[1]);
    }
    $body = '<b>Current availability for ' . $vars['WHO'] . ':</b><br><br>';
    if (empty($myavailability)) {
      $vars['AVAILABILITY'] = '(None currently set)';
      if ($delindex >= 0) {
        $availabilities = implode(PHP_EOL, $availabilities) . PHP_EOL;
        file_put_contents($filename, $availabilities);
      }
    }
    else {
      sort($myavailability);
      if (count($myavailability) > 1) {
        $deloverlap = array($myavailability[0]);
        for ($i=1; $i<count($myavailability); ++$i) {
          $left = explode('-', $deloverlap[count($deloverlap)-1]);
          $right = explode('-', $myavailability[$i]);
          if ($left[1] < $right[0]) {
            array_push($deloverlap, $myavailability[$i]);
          }
          elseif ($right[1] <= $left[1]) { }
          else {
            $deloverlap[count($deloverlap)-1] = $left[0] . '-' . $right[1];
          }
        }
        $myavailability = $deloverlap;
      }
      array_push($availabilities, $memberid);
      $availabilities = array_merge($availabilities, $myavailability);
      file_put_contents($filename, implode(PHP_EOL, $availabilities) . PHP_EOL);
      date_default_timezone_set('America/Chicago');
      $vars['AVAILABILITY'] = '<ul type="circle">';
      $counter = 0;
      $deletebutton = '<button class="delete-button" '
                    . 'type="button" id="delete-datetime-btn" '
                    . 'onclick="delete_click(NUM)">'
                    . '×'
                    . '</button>';
      foreach ($myavailability as $timerange) {
        $vars['AVAILABILITY'] .= '<li>'
                              . timerange2str($timerange)
                              . '&nbsp;&nbsp;'
                              . str_replace('NUM', $counter++, $deletebutton)
                              . '</li>';
      }
      $vars['AVAILABILITY'] .= '</ul>';
    }
    $vars['OTHERAVAIL'] = '';
    foreach ($availabilities as $timerange) {
      if (strpos($timerange, '-') === false) {
        if (strcmp($timerange, $memberid) === 0) {
          break;
        }
        if (empty($vars['OTHERAVAIL'])) {
          $vars['OTHERAVAIL'] = '<br><br>';
        }
        else {
          $vars['OTHERAVAIL'] .= '</ul>';
        }
        $vars['OTHERAVAIL'] .= '<br><br><b>Availability for other member:</b>'
                            . '<br><br><ul type="circle">';
      }
      else {
        $vars['OTHERAVAIL'] .= '<li>'
                            . timerange2str($timerange)
                            . '</li>';
      }
    }
    if (!empty($vars['OTHERAVAIL'])) {
      $vars['OTHERAVAIL'] .= '</ul>';
    }
    $body .= file_get_contents('pages/settimes.html');
  }
  $vars['TITLETEXT'] = 'Schedule Availability';
  $vars['DESCRIPTIONTEXT'] = 'Schedule Availability';
  $page = file_get_contents('page.html');
  $page = str_replace('PAGEBODY', $body, $page);
  $page = setvars($vars, $page);
  echo $page;
?>
