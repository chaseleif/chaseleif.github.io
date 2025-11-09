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
      }
      else {
        $this->names = [];
      }
      $hashes = array_map('md5', $this->names);
      $this->times = [];
      if (file_exists($this->timefile) {
        $file = fopen($this->timefile);
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
    public function havetime($name) {
      return array_key_exists($name, $this->times);
    }
    public function nexttimestr($name) {
      foreach ($this->times[$name] as $time) {
        yield this->time2str($time);
      }
    }
    public function nextname() {
      foreach ($this->names as $name) {
        yield $name;
      }
    }
    public function dumptimes() {
      file_put_contents($this->timefile,
                        implode(PHP_EOL, $this->times . PHP_EOL));
    }
    public function loaded() {
      return (!empty(this->names));
    }
    public function addtime($name, $time) {
      array_push($this->times[$name], $time);
      $file = fopen($this->logfile, 'a');
      if ($file) {
        fwrite($file, '+ '
                      . $name
                      . ' '
                      . this->time2str($time)
                      . PHP_EOL);
        fclose($file);
      }
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
    public function removetime($name, $timeindex) {
      $line = '- '
            . $name
            . ' '
            . this->time2str($this->times[$name][$timeindex])
            . PHP_EOL;
      unset($this->times[$name][$timeindex]);
      if (empty($this->times[$name])) {
        unset($this->times[$name]);
      }
      $file = fopen($this->logfile, 'a');
      if ($file) {
        fwrite($line);
        fclose($file);
      }
      file_put_contents($this->timefile,
                        implode(PHP_EOL, $this->times) . PHP_EOL);
    }
  }
  date_default_timezone_set('America/Chicago');
  function h3text($text) {
    return '<h3>' . $text . '</h3>';
  }
  function h4text($text) {
    return '<h4>' . $text . '</h4>';
  }
  function setvars($vars, $body) {
    foreach ($vars as $key => $value) {
      $body = str_replace($key, $value, $body);
    }
    return $body;
  }
  $vars = [];
  $vars['ERRORMSG'] = '';
  if (isset($_POST['eventname'])) {
    $eventdata = EventData(strtolower($_POST['eventname']));
    if (!$eventdata->loaded()) {
      $vars['ERRORMSG'] = h3text('What ?');
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
      $vars['ERRORMSG'] = h3text('Who ?');
    }
    else {
      $who = ucwords(str_replace('~', ' ', $who), ' ');
      if (!in_array($who, $eventdata->names)) {
        $vars['ERRORMSG'] = h3text("$who ?");
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
  $secretevent = 'some secret event';
  if (!isset($who)) {
    $body = file_get_contents('pages/setname.html');
  }
  elseif (strcmp($event, $secretevent) === 0) {
    $events = [];
    foreach (glob('*', GLOB_ONLYDIR) as $event) {
      if (is_file(join(DIRECTORY_SEPARATOR, array($event, 'members')))) {
        array_push($events, $event);
      }
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
      if (!empty($vars['OTHERAVAIL'])) {
        $vars['OTHERAVAIL'] .= '</ul>' . $hline;
      }
      $vars['OTHERAVAIL'] .= h4text('Availability of another member:')
                          . '<ul type="circle">';
      foreach ($eventdata->nexttimestr($name) as $timestr) {
        $vars['OTHERAVAIL'] .= '<li>' . $timestr . '</li>';
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
