<?php
  class EventData {
    private $logfile;
    private $namefile;
    private $timefile;
    public $event;
    public $names;
    public $times;
    public function __construct($event) {
      $this->event = $event;
      $this->logfile = join(DIRECTORY_SEPARATOR, array($event, 'log'));
      $this->namefile = join(DIRECTORY_SEPARATOR, array($event, 'names'));
      $this->timefile = join(DIRECTORY_SEPARATOR, array($event, 'times'));
      if (file_exists($this->namefile)) {
        $this->names =
            file($this->namefile,
                FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        sort($this->names);
      }
      else {
        $this->names = [];
      }
      $this->loadtimes();
    }
    private function loadtimes() {
      $this->times = [];
      $hashes = array_map('md5', $this->names);
      if (file_exists($this->timefile)) {
        $file = fopen($this->timefile, 'r');
        if ($file) {
          $name = '';
          while (($line = fgets($file)) !== false) {
            $line = rtrim($line);
            if (empty($line)) { }
            elseif (strpos($line, '-') === false) {
              $name = $this->names[array_search($line, $hashes)];
              $this->times[$name] = [];
            }
            else {
              array_push($this->times[$name], $line);
            }
          }
          fclose($file);
        }
      }
    }
    private function time2str($time) {
      $time = explode('-', $time);
      return date('D M j', $time[0])
              . ', between '
              . date('Hi', $time[0])
              . ' and '
              . date('Hi', $time[1]);
    }
    public function dumpnames() {
      file_put_contents($this->namefile,
                        implode(PHP_EOL, $this->names) . PHP_EOL);
    }
    public function dumptimes() {
      $file = fopen($this->timefile, 'w');
      if ($file) {
        foreach ($this->times as $name => $times) {
          fwrite($file, md5($name) . PHP_EOL);
          foreach($times as $time) {
            fwrite($file, $time . PHP_EOL);
          }
        }
        fclose($file);
      }
    }
    public function havetime($name) {
      return array_key_exists($name, $this->times);
    }
    public function nexttimestr($name) {
      foreach ($this->times[$name] as $time) {
        yield $this->time2str($time);
      }
    }
    public function nextname() {
      foreach ($this->names as $name) {
        yield $name;
      }
    }
    public function loaded() {
      return (!empty($this->names));
    }
    public function addtime($name, $time) {
      if (!array_key_exists($name, $this->times)) {
        $this->times[$name] = [];
      }
      $file = fopen($this->logfile, 'a');
      if ($file) {
        fwrite($file, '+ '
                      . $name
                      . ' '
                      . $this->time2str($time)
                      . PHP_EOL);
        fclose($file);
      }
      $this->loadtimes();
      array_push($this->times[$name], $time);
      if (count($this->times[$name]) > 1) {
        sort($this->times[$name]);
        $times = array($this->times[$name][0]);
        for ($i = 1; $i < count($this->times[$name]); ++$i) {
          $left = explode('-', $times[count($times)-1]);
          $right = explode('-', $this->times[$name][$i]);
          if ($left[1] < $right[0]) {
            array_push($times, $this->times[$name][$i]);
          }
          elseif ($right[1] > $left[1]) {
            $times[count($times)-1] = $left[0] . '-' . $right[1];
          }
        }
        $this->times[$name] = $times;
      }
      $this->dumptimes();
    }
    public function removetime($name, $timeindex) {
      $line = '- '
            . $name
            . ' '
            . $this->time2str($this->times[$name][$timeindex])
            . PHP_EOL;
      $file = fopen($this->logfile, 'a');
      if ($file) {
        fwrite($file, $line);
        fclose($file);
      }
      $this->loadtimes();
      unset($this->times[$name][$timeindex]);
      if (empty($this->times[$name])) {
        unset($this->times[$name]);
      }
      $this->dumptimes();
    }
  }
  date_default_timezone_set('America/Chicago');
  function removedir($dir) {
    foreach (new DirectoryIterator($dir) as $f) {
      if ($f->isDot()) { }
      elseif ($f->isFile()) {
        unlink($f->getPathname());
      }
      elseif ($f->isDir()) {
        removedir($f->getPathname());
      }
    }
    rmdir($dir);
  }
  function h3text($text) {
    return '<h3>' . $text . '</h3>';
  }
  function h4text($text) {
    return '<h4>' . $text . '</h4>';
  }
  $vars = [];
  $vars['ERRORMSG'] = '';
  if (isset($_POST['eventname'])) {
    $eventdata = new EventData(strtolower($_POST['eventname']));
    if (!$eventdata->loaded()) {
      $vars['ERRORMSG'] = h3text('No ?');
      unset($eventdata);
    }
  }
  if (isset($_POST['firstname']) && isset($_POST['lastname'])) {
    $who = strtolower($_POST['firstname'])
          . '~' . strtolower($_POST['lastname']);
  }
  else if (isset($_POST['who'])) {
    $who = $_POST['who'];
  }
  if (isset($who) && isset($eventdata)) {
    if (substr_count($who, '~') !== 1) {
      $vars['ERRORMSG'] = h3text('No ?');
    }
    else {
      $who = ucwords(str_replace('~', ' ', $who), ' ');
      if (!in_array($who, $eventdata->names)) {
        $vars['ERRORMSG'] = h3text('No ?');
      }
    }
    if (empty($vars['ERRORMSG'])) {
      $vars['WHO'] = $who;
      $vars['EVENTNAME'] = $eventdata->event;
    }
    else {
      unset($who);
    }
  }
  $secretevent = '1234567';
  if (!isset($who)) {
    $body = file_get_contents('pages/setname.html');
  }
  elseif (strcmp($eventdata->event, $secretevent) === 0) {
    if (isset($_POST['del'])) {
      if (strcmp($_POST['del'], 'event') === 0) {
        $counter = -1;
        foreach (glob('*', GLOB_ONLYDIR) as $event) {
          if (strcmp($event, $secretevent) === 0) { }
          elseif (++$counter == $_POST['index']) {
            removedir($event);
            break;
          }
        }
      }
      elseif (strcmp($_POST['del'], 'name') === 0) {
        $eventdata = new EventData($_POST['delevent']);
        unset($eventdata->names[$_POST['index']]);
        $eventdata->dumpnames();
      }
      elseif (strcmp($_POST['del'], 'time') === 0) {
        $eventdata = new EventData($_POST['delevent']);
        unset($eventdata->times[$_POST['delname']][$_POST['index']]);
        $eventdata->dumptimes();
      }
    }
    function delbutton(...$args) {
      $button = '&nbsp;&nbsp;<button class="del-button" '
              . 'type="button" id="del-thing-btn" '
              . 'onclick="del_thing(';
      // which
      $button .= "\"$args[0]\"";
      // index
      $button .= ", $args[1]";
      if (strcmp($args[0], "event") !== 0) {
        // eventname
        $button .= ", \"$args[2]\"";
        if (strcmp($args[0], "name") === 0) {
          // name
          $button .= ", \"$args[3]\"";
        }
      }
      $button .= ')>×</button>';
      return $button;
    }
    $hline = '<hr width="50%" color="#008A00" align="left" />';
    $eventnum = 0;
    $vars['EVENTLIST'] = '';
    foreach (glob('*', GLOB_ONLYDIR) as $event) {
      if (strcmp($event, $secretevent) === 0) {
        continue;
      }
      $namenum = 0;
      $eventdata = new EventData($event);
      $vars['EVENTLIST'] .= h4text($event . ':')
                          . delbutton('event',
                                      $eventnum++)
                          . '<ul type="none">';
      foreach ($eventdata->nextname() as $name) {
        $timenum = 0;
        $vars['EVENTLIST'] .= '<li>' . $name
                            . delbutton('name',
                                        $namenum++, $event)
                            . '<ul type="circle">';
        if (!$eventdata->havetime($name)) {
          $vars['EVENTLIST'] .= '<li>(None currently set)</li>';
        }
        else {
          foreach ($eventdata->nexttimestr($name) as $timestr) {
            $vars['EVENTLIST'] .= '<li>'
                                . $timestr
                                . delbutton('time',
                                            $timenum++, $event, $name)
                                .'</li>';
          }
        }
        $vars['EVENTLIST'] .= '</ul>';
      }
      $vars['EVENTLIST'] .= '</ul>' . $hline;
    }
    $body = file_get_contents('pages/secret.html');
  }
  else {
    if (isset($_POST['delindex'])) {
      $eventdata->removetime($who, $_POST['delindex']);
    }
    elseif (isset($_POST['from']) && isset($_POST['until'])) {
      $_POST['from'] /= 1000;
      $_POST['until'] /= 1000;
      $eventdata->addtime($who,
                          $_POST['from'] . '-' . $_POST['until']);
    }
    $hline = '<hr width="50%" color="#008A00" align="left" />';
    $body = '<b>' . $who . ' availability:</b><br>';
    if (!$eventdata->havetime($who)) {
      $vars['AVAILABILITY'] = '(None currently set)';
    }
    else {
      $vars['AVAILABILITY'] = '<ul type="circle">';
      $counter = 0;
      $deletebutton = '<button class="delete-button" '
                    . 'type="button" id="delete-datetime-btn" '
                    . 'onclick="delete_click(NUM)">'
                    . '×'
                    . '</button>';
      foreach ($eventdata->nexttimestr($who) as $timestr) {
        $vars['AVAILABILITY'] .= '<li>'
                              . $timestr
                              . '&nbsp;&nbsp;'
                              . str_replace('NUM', $counter++, $deletebutton)
                              . '</li>';
      }
      $vars['AVAILABILITY'] .= '</ul>';
    }
    $vars['OTHERAVAIL'] = '';
    foreach ($eventdata->nextname() as $name) {
      if (strcmp($name, $who) === 0) {
        continue;
      }
      if (!$eventdata->havetime($name)) {
        continue;
      }
      $vars['OTHERAVAIL'] .= h4text('Availability of another member:')
                          . '<ul type="circle">';
      foreach ($eventdata->nexttimestr($name) as $timestr) {
        $vars['OTHERAVAIL'] .= '<li>' . $timestr . '</li>';
      }
      $vars['OTHERAVAIL'] .= '</ul>' . $hline;
    }
    $body .= file_get_contents('pages/settimes.html');
  }
  $vars['TITLETEXT'] = 'Schedule Availability';
  $vars['DESCRIPTIONTEXT'] = 'Schedule Availability';
  $page = file_get_contents('page.html');
  $page = str_replace('PAGEBODY', $body, $page);
  foreach ($vars as $key => $value) {
    $page = str_replace($key, $value, $page);
  }
  echo $page;
?>
