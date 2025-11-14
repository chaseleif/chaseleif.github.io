<?php
  function event2path($event) {
    return join(DIRECTORY_SEPARATOR, array('events', $event));
  }
  function eventfile($event, $file) {
    return join(DIRECTORY_SEPARATOR, array('events', $event, $file));
  }
  function createevent($event, $owner) {
    if (is_file(event2path($event))) {
      return;
    }
    mkdir(event2path($event), 0700);
    $filenames = ['log', 'names', 'times', 'event'];
    foreach ($filenames as $filename) {
      $filename = eventfile($event, $filename);
      $file = fopen($filename, 'w');
      if ($file) {
        fclose($file);
        chmod($filename, 0600);
      }
    }
    file_put_contents(eventfile($event, 'event'), $owner . PHP_EOL);
  }
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
  function wraptext($wrap, $text) {
    return "<$wrap>$text</$wrap>" . PHP_EOL;
  }
  function possessive($name) {
    if (substr($name, -1) === 's') {
      return "$name'";
    }
    return "$name's";
  }
  function initevents() {
    $GLOBALS['events'] = [];
    foreach (glob(join(DIRECTORY_SEPARATOR,
                  array('events', '*'))) as $event) {
      $event = strtolower(basename($event));
      if (is_file(eventfile($event, 'event'))) {
        $owner = rtrim(file_get_contents(eventfile($event, 'event')));
        if (substr_count($owner, '=') === 1) {
          $owner = explode('=', $owner);
          if (strcmp($owner[0], 'secret') === 0) {
            $GLOBALS['secret'] = $event;
            $owner = $owner[1];
          }
          else {
            continue;
          }
        }
        $GLOBALS['events'][$event] = $owner;
      }
    }
    ksort($GLOBALS['events']);
  }
  class InState {
    public $errormsg;
    public $eventdata;
    public $whoami;
    public function __construct() {
      $this->errormsg = '';
      foreach ($_POST as $key => $value) {
        $_POST[$key] = strtolower(trim($value));
        if ($_POST[$key] === '') {
          unset($_POST[$key]);
        }
      }
      if (isset($_POST['eventname'])) {
        $this->eventdata = new EventData($_POST['eventname']);
        if (!$this->eventdata->valid()) {
          $this->errormsg = 'No ?';
          unset($this->eventdata);
        }
      }
      if (!isset($this->eventdata)) { }
      elseif (isset($_POST['firstname']) && isset($_POST['lastname'])) {
        $this->whoami = $_POST['firstname'] . '~' . $_POST['lastname'];
      }
      elseif (isset($_POST['who'])) {
        $this->whoami = $_POST['who'];
      }
      if (isset($this->whoami)) {
        if (substr_count($this->whoami, '~') !== 1) {
          $this->errormsg = 'No ?';
        }
        else {
          $this->whoami = ucwords(str_replace('~', ' ', $this->whoami));
          if (strcmp($this->whoami, $this->eventdata->owner) !== 0
              && !in_array($this->whoami, $this->eventdata->names)) {
            $this->errormsg = 'No ?';
          }
        }
      }
      if ($this->errormsg !== '') {
        unset($this->whoami);
        unset($this->eventdata);
        return;
      }
      else if (!isset($this->eventdata)) { }
      elseif (strcmp($this->eventdata->event, $GLOBALS['secret']) === 0) {
        $this->valmgmtargs();
      }
    }
    private function valmgmtargs() {
      // event management
      if (isset($_POST['add'])) {
        if (strcmp($_POST['add'], 'event') === 0) {
          $postevent = 'newname';
        }
        elseif(strcmp($_POST['add'], 'name') === 0) {
          $postname = 'newname';
          $postevent = 'addnameto';
        }
        if ((isset($postname) && !isset($_POST[$postname])) ||
            !isset($postevent) || !isset($_POST[$postevent])) {
          $this->errormsg = 'What are you doing ?';
        }
        elseif (!ctype_alnum($_POST[$postevent])) {
          $this->errormsg = 'Event name must be alphanumeric';
        }
        elseif (!isset($postname) && file_exists(event2path($_POST[$postevent]))) {
          $this->errormsg = 'Event name ' . $_POST[$postevent] . ' is unavailable';
        }
        elseif (isset($postname) && substr_count($_POST[$postname], ' ') !== 1) {
          $this->errormsg = 'Expected name as: "First Last"';
        }
        else if (isset($postname) &&
                  !array_all(explode(' ', $_POST[$postname]),
                              function (string $value) {
                                return ctype_alnum($value);
                              })) {
          $this->errormsg = 'First and last name must be alphanumeric';
        }
        elseif (isset($postname)) {
          $_POST[$postname] = ucwords($_POST[$postname]);
        }
      }
      elseif (isset($_POST['del'])) {
        if (strcmp($_POST['del'], 'event') === 0) {
          if (strcmp($_POST['delevent'], $GLOBALS['secret']) === 0) {
            $this->errormsg = 'What are you doing ?';
          }
        }
      }
    }
  }
  class EventData {
    private $logfile;
    private $namefile;
    private $bannerfile;
    private $timefile;
    public $event;
    public $names;
    public $owner;
    public $times;
    public function __construct($event) {
      if (!array_key_exists($event, $GLOBALS['events'])) {
        return;
      }
      $this->event = $event;
      $this->owner = $GLOBALS['events'][$event];
      $this->logfile = eventfile($this->event, 'log');
      $this->namefile = eventfile($this->event,'names');
      $this->bannerfile = eventfile($this->event, 'banner');
      $this->timefile = eventfile($this->event, 'times');
      $this->loadnames();
      $this->loadtimes();
    }
    private function loadnames() {
      $this->names = [];
      if (file_exists($this->namefile)) {
        $this->names = file($this->namefile,
                            FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        sort($this->names);
      }
    }
    public function getbanner() {
      $banner = '';
      if (is_file($this->bannerfile)) {
        $banner = file($this->bannerfile,
                        FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        $banner = implode('<br>' . PHP_EOL, $banner);
        $banner = wraptext('h4', $banner);
      }
      return wraptext('h3', $this->event) . $banner . 'HLINE';
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
            if ($line === '') { }
            elseif (strpos($line, '-') === false) {
              $name = array_search($line, $hashes);
              if ($name === false) {
                $name = '';
              }
              else {
                $name = $this->names[$name];
                $this->times[$name] = [];
              }
            }
            elseif ($name !== '') {
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
    public function havename($name) {
      return in_array($name, $this->names);
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
    public function valid() {
      return (isset($this->owner) && $this->owner !== '');
    }
    private function log($line) {
      $file = fopen($this->logfile, 'a');
      if ($file) {
        fwrite($file, $line . PHP_EOL);
        fclose($file);
      }
    }
    public function addtime($name, $time) {
      $this->log("+ $name {$this->time2str($time)}");
      $this->loadtimes();
      if (!array_key_exists($name, $this->times)) {
        $this->times[$name] = [];
      }
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
      $this->log("- $name "
                . $this->time2str($this->times[$name][$timeindex]));
      $this->loadtimes();
      unset($this->times[$name][$timeindex]);
      if (empty($this->times[$name])) {
        unset($this->times[$name]);
      }
      $this->dumptimes();
    }
    public function addname($name) {
      if (!in_array($name, $this->names)) {
        array_push($this->names, $name);
        $this->dumpnames();
      }
    }
    public function removename($index) {
      $this->loadtimes();
      $name = $this->names[$index];
      if (array_key_exists($name, $this->times)) {
        unset($this->times[$name]);
        $this->dumptimes();
      }
      unset($this->names[$index]);
      $this->dumpnames();
    }
  }
  date_default_timezone_set('America/Chicago');
  initevents();
  $state = new InState();
  $vars = [ 'ERRORMSG' => $state->errormsg ];
  if (isset($state->whoami) && isset($state->eventdata)) {
    $vars['WHO'] = $state->whoami;
    $vars['EVENTNAME'] = $state->eventdata->event;
  }
  if (!isset($state->whoami)) {
    $body = file_get_contents('pages/setname.html');
  }
  elseif (strcmp($state->eventdata->event, $GLOBALS['secret']) === 0) {
    if ($state->errormsg !== '') { }
    elseif (isset($_POST['del'])) {
      if (strcmp($_POST['del'], 'event') === 0) {
        removedir(event2path($_POST['delevent']));
      }
      elseif (strcmp($_POST['del'], 'name') === 0) {
        $eventdata = new EventData($_POST['delevent']);
        $eventdata->removename($_POST['index']);
      }
      elseif (strcmp($_POST['del'], 'time') === 0) {
        $eventdata = new EventData($_POST['delevent']);
        $eventdata->removetime($_POST['delname'], $_POST['index']);
      }
    }
    elseif (isset($_POST['add'])) {
      $newname = $_POST['newname'];
      if (strcmp($_POST['add'], 'event') === 0) {
        createevent($newname, $state->whoami);
      }
      elseif (strcmp($_POST['add'], 'name') === 0) {
        $eventdata = new EventData($_POST['addnameto']);
        if ($eventdata->valid()) {
          $eventdata->addname($newname);
        }
        else {
          $vars['ERRORMSG'] = wraptext('h3', 'Problem loading the event ?');
        }
      }
    }
    if ($vars['ERRORMSG'] === '') {
      initevents();
    }
    function modbutton(...$args) {
      $button = '&nbsp;&nbsp;<button class="mod-button" '
              . 'type="button" id="mod-thing-btn" '
              . 'onclick="mod_thing(';
      // action ('add' or 'del')
      $button .= "'$args[0]'";
      // which
      $button .= ", '$args[1]'";
      if (strcmp($args[0], 'del') === 0) {
        if (strcmp($args[1], "event") === 0) {
          $button .= ", '$args[2]'";
        }
        else {//if (strcmp($args[1], "event") !== 0) {
          $button .= ", $args[2]";
          // eventname
          $button .= ", '$args[3]'";
          // name only needs which, index, and eventname
          if (strcmp($args[1], "name") !== 0) {
            // name
            $button .= ", '$args[4]'";
          }
        }
        $button .= ')">×</button>';
      }
      else { //if (strcmp($args[0], 'add') === 0) {
        // adding an event doesn't need any additional information
        // adding a name to an event needs the event name
        if (strcmp($args[1], "name") === 0) {
          $button .= ", '$args[2]'";
        }
        $button .= ')">➕</button>';
      }
      return $button . PHP_EOL;
    }
    function inputfield($id, $text) {
      return '<input id="' . $id . '" type="text" placeholder="' . $text . '"/>' . PHP_EOL;
    }
    $vars['EVENTLIST'] = '';
    foreach ($GLOBALS['events'] as $event => $owner) {
      if (strcmp($event, $GLOBALS['secret']) === 0) {
        continue;
      }
      $eventdata = new EventData($event);
      if (!$eventdata->valid()) {
        continue;
      }
      $namenum = 0;
      $vars['EVENTLIST'] .= '<h4>' . $event . ':'
                          . PHP_EOL
                          . modbutton('del', 'event', $event)
                          . '</h4><ul type="none">'
                          . PHP_EOL
                          . '<li>'
                          . 'Add: '
                          . inputfield($event . '-name', 'First Last')
                          . modbutton('add', 'name', $event)
                          . '</li><br>'
                          . PHP_EOL;
      foreach ($eventdata->nextname() as $name) {
        $timenum = 0;
        $vars['EVENTLIST'] .= '<li>' . $name . PHP_EOL
                            . modbutton('del', 'name',
                                        $namenum++, $event)
                            . '<ul type="circle">';
        if (!$eventdata->havetime($name)) {
          $vars['EVENTLIST'] .= wraptext('li', '(None currently set)');
        }
        else {
          foreach ($eventdata->nexttimestr($name) as $timestr) {
            $vars['EVENTLIST'] .= '<li>'
                                . $timestr
                                . modbutton('del', 'time',
                                            $timenum++, $event, $name)
                                . '</li>' . PHP_EOL;
          }
        }
        $vars['EVENTLIST'] .= '</ul>';
      }
      $vars['EVENTLIST'] .= '</ul>' . 'HLINE';
    }
    $body = file_get_contents('pages/secret.html');
  }
  else {
    if (isset($_POST['delindex'])) {
      $state->eventdata->removetime($state->whoami, $_POST['delindex']);
    }
    elseif (isset($_POST['from']) && isset($_POST['until'])) {
      $_POST['from'] /= 1000;
      $_POST['until'] /= 1000;
      $state->eventdata->addtime($state->whoami,
                          $_POST['from'] . '-' . $_POST['until']);
    }
    $body = $state->eventdata->getbanner();
    if (!$state->eventdata->havename($state->whoami)) {
      $vars['AVAILABILITY'] = '';
    }
    else {
      $body .= wraptext('b', possessive($state->whoami) . ' availability:')
            . '<br>';
      $vars['AVAILABILITY'] = '<ul type="circle">';
      if (!$state->eventdata->havetime($state->whoami)) {
        $vars['AVAILABILITY'] .= wraptext('li', '(None currently set)');
      }
      else {
        $counter = 0;
        $deletebutton = '<button class="delete-button" '
                      . 'type="button" id="delete-datetime-btn" '
                      . 'onclick="delete_click(NUM)">'
                      . '×'
                      . '</button>';
        foreach ($state->eventdata->nexttimestr($state->whoami) as $timestr) {
          $vars['AVAILABILITY'] .= wraptext('li',
                                            $timestr
                                            . '&nbsp;&nbsp;'
                                            . str_replace('NUM',
                                                          $counter++,
                                                          $deletebutton));
        }
      }
      $vars['AVAILABILITY'] .= '</ul>'
                            . 'HLINE'
                            . file_get_contents('pages/timechooser.html')
                            . 'HLINE';
    }
    $vars['OTHERAVAIL'] = '';
    foreach ($state->eventdata->nextname() as $name) {
      if (strcmp($name, $state->whoami) === 0) {
        continue;
      }
      if (!$state->eventdata->havetime($name)) {
        continue;
      }
      $vars['OTHERAVAIL'] .= wraptext('h4', "Another member's availability:")
                          . '<ul type="circle">';
      foreach ($state->eventdata->nexttimestr($name) as $timestr) {
        $vars['OTHERAVAIL'] .= wraptext('li', $timestr);
      }
      $vars['OTHERAVAIL'] .= '</ul>' . 'HLINE';
    }
    $body .= file_get_contents('pages/settimes.html');
  }
  $vars['TITLETEXT'] = 'Schedule Availability';
  $vars['DESCRIPTIONTEXT'] = 'Schedule Availability';
  $vars['HLINE'] =
      '<hr width="50%" color="#008A00" align="left" />' . PHP_EOL;
  $page = file_get_contents('page.html');
  $page = str_replace('PAGEBODY', $body, $page);
  foreach ($vars as $key => $value) {
    $page = str_replace($key, $value, $page);
  }
  echo $page;
?>
