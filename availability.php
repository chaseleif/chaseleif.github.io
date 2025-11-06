<?php
  function setvars($vars, $body) {
    foreach ($vars as $key => $value) {
      $body = str_replace($key, $value, $body);
    }
    return $body;
  }
  $vars = [];
  $vars['ERRORMSG'] = '';
  if (isset($_POST['eventname'])) {
    $event = strtolower($_POST['eventname']);
    if (is_dir($event)) {
      $members = join(DIRECTORY_SEPARATOR, array($event, 'members'));
      if (!is_file($members)) {
        $vars['ERRORMSG'] .= 'What';
      }
    }
    else {
      $vars['ERRORMSG'] .= 'What';
    }
  }
  if (!empty($vars['ERRORMSG'])) {
    unset($event);
    unset($members);
  }
  if (isset($event)) {
    if (isset($_POST['firstname']) && isset($_POST['lastname'])) {
      $who = strtolower($_POST['firstname'])
            . '~' . strtolower($_POST['lastname']);
    }
    else if (isset($_POST['who'])) {
      $who = strtolower($_POST['who']);
    }
  }
  if (isset($who)) {
    if (substr_count($who, '~') !== 1) {
      $vars['ERRORMSG'] = 'Who';
    }
    else {
      $who = ucwords(str_replace('~', ' ', $who), ' ');
      $members = file($members, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
      if (!in_array($who, $members)) {
        $vars['ERRORMSG'] = $who;
      }
    }
    if (empty($vars['ERRORMSG'])) {
      $vars['WHO'] = $who;
      $vars['EVENTNAME'] = $event;
    }
  }
  if (!empty($vars['ERRORMSG'])) {
    $vars['ERRORMSG'] = '<h3>' . $vars['ERRORMSG'] . ' ?</h3>';
  }
  if (!isset($vars['WHO'])) {
    $body = file_get_contents('pages/setname.html');
  }
  else {
    $filename = join(DIRECTORY_SEPARATOR, array($event, 'times'));
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
            . date('Hi', $timerange[0])
            . ' and '
            . date('Hi', $timerange[1]);
    }
    $hline = '<hr width="50%" color="#008A00" align="left" />';
    $body = '<b>' . $vars['WHO'] . ' availability:</b><br>';
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
        if (!empty($vars['OTHERAVAIL'])) {
          $vars['OTHERAVAIL'] .= '</ul>' . $hline;
        }
        $vars['OTHERAVAIL'] .= '<h4>Availability of another member:</h4>'
                            . '<ul type="circle">';
      }
      else {
        $vars['OTHERAVAIL'] .= '<li>'
                            . timerange2str($timerange)
                            . '</li>';
      }
    }
    if (!empty($vars['OTHERAVAIL'])) {
      $vars['OTHERAVAIL'] .= '</ul>' . $hline;
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
