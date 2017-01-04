<?php

if (session_status() != PHP_SESSION_ACTIVE) {
  session_start();
}

class Page {
    private $header = '';
    private $body = '';
    private $footer = '';
    private $title = '';
    private $menuitems = array('<span class="glyphicon glyphicon-home"></span> Home' => 'index.php');
    private $error = null;
    private $warning = null;
    private $info = null;
    private $buildTime = '';

    public function __construct($title) {
        $this->title = $title;
        if (!isset($_SESSION['characterID'])) {
            $this->addMenuItem('<span class="glyphicon glyphicon-user"></span> Login', array('Fleet member login' => 'login.php?login=member', 'Register Fleet' => 'login.php?login=fc&page=fleet.php'));
        } else {
            $this->addMenuItem('<span class="glyphicon glyphicon-plus-sign"></span> Register Fleet', 'registerfleet.php');
            $this->addMenuItem('<span class="glyphicon glyphicon-send"></span> My fit', 'fitting.php');
            $this->addMenuItem('<span class="glyphicon glyphicon-knight"></span> My fleet', 'fleet.php');
        }
        $this->addMenuItem('<span class="glyphicon glyphicon-info-sign"></span> About', 'about.php');
    }

    function setTitle($title){
        $this->title = $title;
    }

    function addHeader($header){
        $this->header .= $header;
    }

    function addBody($body){
        $this->body .= $body;
    }

    function addFooter($footer){
        $this->footer .= $footer;
    }

    function addMenuItem($name, $url) {
        $this->menuitems[$name] = $url;
    }

    function setError($html) {
        $this->error = $html;
    }

    function setWarning($html) {
        $this->warning = $html;
    }

    function setInfo($html) {
        $this->info = $html;
    }

    function setBuildTime($html) {
        $this->buildTime = $html;
    }

    function display() {
        $page ='<!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
        <head>
            <meta charset="utf-8"/>
            <link rel="canonical" href="'.URL::url_path().'"/>
            <meta name="og:site_name" content="Fleet Yo!">
            <meta name="description" content="This service is brought to you by Snitch Ashor of BRGF (The BLOC).">
            <meta name="og:description" content="This service is brought to you by Snitch Ashor of BRGF (The BLOC).">
            <meta name="og:title" content="Small Gang/Fleet management Tool">
            <meta name="twitter:title" content="Small Gang/Fleet management Tool">
            <meta name="og:image" content="'.URL::url_path().'img/logo.png">
            <meta name="twitter:image" content="'.URL::url_path().'img/logo.png">
            <meta name="og:url" content="'.URL::url_path().'">
            <meta name="twitter:card" content="summary">
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <link rel="stylesheet" href="css/cyborg.min.css" type="text/css" />
            <link rel="stylesheet" href="css/custom.css" type="text/css" /> 
            <title>'.$this->title.'</title>
            '.$this->header.'
        </head>
        <body>

        <!-- Fixed navbar -->
        <nav class="navbar navbar-inverse">
          <div class="container">
            <div class="navbar-header">
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="'.URL::url_path().'">Fleet-Yo!</a>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
              <ul class="nav navbar-nav">';
                foreach ($this->menuitems as $name => $url) {
                    if (is_array($url)) {
                        $page .= '<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'.$name.' <span class="caret"></span></a><ul class="dropdown-menu">';
                        foreach ($url as $subname => $suburl) {
                            $page .= '<li><a href="'.$suburl.'">'.$subname.'</a>';
                        }
                        $page .= '</ul></li>';
                    } else {
                        if ($url == basename($_SERVER['SCRIPT_NAME'])) {
                            $page .= '<li class="active"><a href="'.$url.'">'.$name.'</a>';
                        } else {
                            $page .= '<li><a href="'.$url.'">'.$name.'</a>';
                        }
                    }
                }
                if (isset($_SESSION['characterID'])) {
                $page .= '<li class="dropdown navbar-right">
                              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">'.$_SESSION['characterName'].' <span class="caret"></span><img src="https://imageserver.eveonline.com/Character/'.$_SESSION['characterID'].'_32.jpg"></a>
                                  <ul class="dropdown-menu">
                                      <li><a href="logout.php">Logout</a></li>
                                      <li><a href="charinfo.php">Info</a></li>
                                  </ul>
                          </li>';
                }
        $page .= '</ul>
            </div><!--/.nav-collapse -->
          </div>
        </nav>
        <div class="container" role="main">';
    if (!$this->cookiesOk()) {
        $page .= '<div id="cookiequestion" class="panel panel-info">
            <div class="panel-heading">Cookies disclaimer</div>
            <script>
              function cookiesaccept() {
                $.ajax({
                type: "POST",
                url: "'.URL::url_path().'acceptcookies.php'.'",
                data: {cookiesaccept: "true"},
                success:function()
                {
                  $("#cookiequestion").slideUp("slow", function() { $("#cookiequestion").remove();});
                }
                });
              } 
            </script>
            <div class="panel-body"><p>This website uses cookies to keep you logged in.</p><br/>
            <button class="btn btn-info" onclick="cookiesaccept()">Got it.</button></div>
        </div>';
    }

    if (isset($this->error)) {
        $page .= '<div class="panel panel-danger">
            <div class="panel-heading">Error</div>
            <div class="panel-body">'.$this->error.'</div>
        </div>';
    }
    if (isset($this->warning)) {
        $page .= '<div class="panel panel-warning">
            <div class="panel-heading">Warning</div>
            <div class="panel-body">'.$this->warning.'</div>
        </div>';
    }
    if (isset($this->info)) {
        $page .= '<div class="panel panel-primary">
            <div class="panel-heading">Information</div>
            <div class="panel-body">'.$this->info.'</div>
        </div>';
    }

    if ($this->body != '') {
        $page .= '<div class="panel panel-default">
                <div class="panel-heading"><h4>'.$this->title.'</h4></div>
                <div class="panel-body">
                    '.$this->body.'<br/>';
            if ($this->buildTime != '') {
                 $page .= '<p class="text-right small"><em>Page built in '.$this->buildTime.' seconds.</em></p>';
            }
    } else {$page .= '<div><br/></div>'; }
    $page .= '</div></div></div>
        <footer class="footer navbar-inverse">
            <div class="container">
                <p class="text-muted">EVE Online, the EVE logo, EVE and all associated logos and designs are the intellectual property of <a href="http://www.ccpgames.com/">CCP</a> hf. All artwork, screenshots, characters, vehicles, storylines, world facts or other recognizable features of the intellectual property relating to these trademarks are likewise the intellectual property of CCP hf. EVE Online and the EVE logo are the registered trademarks of CCP hf. All rights are reserved worldwide. All other trademarks are the property of their respective owners. CCP hf. has granted permission to Fleet-Yo to use EVE Online and all associated logos and designs for promotional and information purposes on its website but does not endorse, and is not in any way affiliated with, Fleet-Yo. CCP is in no way responsible for the content on or functioning of this website, nor can it be liable for any damage arising from the use of this website.</p>
            </div>
        </footer>
        <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        '.$this->footer.'
        </body>
        </html>';
        echo $page;
        exit;
    }

    public static function cookiesOk() {
        if (isset($_COOKIE["fleetyocookies"])) {
            return true;
        } else {
            return false;
        }
    }

}
