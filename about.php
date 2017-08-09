<?php
require_once("initialise.inc");
require_once("bcswidgets.inc");

ob_start();
?>
<?php
//If an authentication cookie is presented, its token will be checked. If found and still valid, the corresponding user details and permissions will be retrieved.
$PAGETITLE = "About Nucleus";
switch("/about.php") {
    case '/workspace.php':
        $WORKSPACE = array(
            'id' => (isset($_GET['ws']) ? IdentifierStr($_GET['ws']) : 'people'),
        );
        $PAGETITLE = ucfirst($WORKSPACE['id']);        
        switch($WORKSPACE['id']){
            
        }
        break;
    case '/record.php':
        $RECORD = array(
            'type' => (isset($_GET['rec']) ? IdentifierStr($_GET['rec']) : ''),
        );
        switch($RECORD['type']) {
            case 'directdebitjob':
                $RECORD['gridleft'] = 'col-lg-9';
                $RECORD['gridright'] = 'col-lg-3';
                break;
            case 'invoice':
                $RECORD['gridleft'] = 'col-lg-8';
                $RECORD['gridright'] = 'col-lg-4';
                break;
            default:
                $RECORD['gridleft'] = 'col-md-8';
                $RECORD['gridright'] = 'col-md-4';
        }
        $PAGETITLE = ucfirst($RECORD['type']);
        break;
    case '/full.php':
        $PAGELOADER = array(
            'id' => (isset($_GET['page']) ? IdentifierStr($_GET['page']) : 'editor'),
        );
        $PAGETITLE = ucfirst($PAGELOADER['id']);        
        break;
}
Authenticate();
if(!$AUTHENTICATION['Authenticated'] && (strpos('', 'AllowGuest') === FALSE))
{
    header('Location: https://'.$_SERVER['HTTP_HOST'].'/login.php?redirect=/about.php'.(empty($_SERVER['QUERY_STRING']) ? "" : "&".$_SERVER['QUERY_STRING']), TRUE, 307);
    die();
}
?>
<!DOCTYPE html>
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en-GB"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en-GB"> <!--<![endif]-->
	<head>
		<meta charset="UTF-8">    
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<title><?php echo (!empty($PAGETITLE) ? $PAGETITLE : "About Nucleus"); ?></title>
		<meta name="description" content="Nucleus">
		<meta name="author" content="Guido Gybels">
		<meta name="robots" content="noindex, nofollow">
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1.0">

		<link rel="icon" href="/img/favicon.ico">
		<link rel="apple-touch-icon" href="/img/icon57.png" sizes="57x57">
		<link rel="apple-touch-icon" href="/img/icon72.png" sizes="72x72">
		<link rel="apple-touch-icon" href="/img/icon76.png" sizes="76x76">
		<link rel="apple-touch-icon" href="/img/icon114.png" sizes="114x114">
		<link rel="apple-touch-icon" href="/img/icon120.png" sizes="120x120">
		<link rel="apple-touch-icon" href="/img/icon144.png" sizes="144x144">
		<link rel="apple-touch-icon" href="/img/icon152.png" sizes="152x152">

		<link rel="stylesheet" href="/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/plugins.css">
		<link rel="stylesheet" href="/css/main.css">
		<link rel="stylesheet" href="/css/bcs.css">
		<link rel="stylesheet" href="/css/themes.css">
		<link rel="stylesheet" href="/css/fullcalendar.css">

		<script src="/js/vendor/modernizr-2.7.1-respond-1.4.2.min.js"></script>
		<script src="/js/moment.min.js"></script>

	</head>
	<body>
<?php if(file_exists(CONSTLocalStorageRoot."googletagmanager.txt")){ echo file_get_contents(CONSTLocalStorageRoot."googletagmanager.txt"); } ?>
		


<?php
require_once('ccontrols.inc');
?>
		<div id="page-container" class="sidebar-no-animations footer-fixed">


        <!-- Main Container -->
			<div id="main-container">
				
                
				<!-- Page content -->
				<div id="page-content">

					<!-- Page Header -->
					
                    
					
					
					<!-- END Page Header -->

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="block full">
                                <p class="lead">Nucleus Version 0.10.60.4363<br>Compiled 23/05/2017 10:22:33</p>
                                <h5 class="text-info">Acknowledgements</h5>
                                <p class="text-mono">
                                    This system is built around the open source technologies HTML5, CSS3, Apache, MySQL and PHP. Development takes place on the Uniform Server, a self-contained WAMP package. Most of the code is written in phpDesigner 8, and the database design is done using MySQL Workbench and Navicat for MySQL. Client-side scripting is based on Javascript and the JQuery library and uses the Bootstrap framework. Firebug helped considerably in analysing and debugging of markup, stylesheets and Javascript code.                  
                                </p>
                                <h5 class="text-info">Designed for Firefox &amp; Chrome</h5>
                                <p class="text-mono">
                                    The Nucleus system is designed and optimised for <a href="http://www.mozilla.org/en-US/firefox/fx/" target="_blank">Firefox</a> and <a href="https://www.google.com/chrome/browser/desktop/" target="_blank">Chrome</a>. It should render well on other modern, standards compliant browsers.
                                </p>
                                <h5 class="text-info">JQuery Licensing (MIT License)</h5>
                                <p class="text-mono">Copyright (c) 2011 John Resig, <a href="http://jquery.com/" target="_blank">http://jquery.com/</a><br />
                                    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:<br />
                                    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.<br />
                                    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
                                </p>
                                <h5 class="text-info">DataTables Licensing (BSD license)</h5>
                                <p class="text-mono">Copyright (c) 2008-2011, Allan Jardine<br />
                                    All rights reserved.<br />
                                    Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
                                </p>
                                <ul class="text-mono">
                                    <li>Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.</li>
                                    <li>Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.</li>
                                    <li>Neither the name of Allan Jardine nor SpryMedia UK may be used to endorse or promote products derived from this software without specific prior written permission.</li>
                                </ul>
                                <p class="text-mono">
                                    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
                                </p>                                 
                            </div>
                        </div>
<?php
Use Aws\Common\Aws;

if(defined('__DEBUGMODE') && __DEBUGMODE && Authenticate() && HasPermission('adm_syssettings'))
{
    Div(array('class' => 'col-xs-12'), 6);
    Div(array('class' => array('block', 'full')), 7);
    $glyphbuttons = array();
    $glyphbuttons[] = array('icon' => 'fa-arrows-v', 'colour' => 'default', 'disabled' => FALSE, 'tooltip' => 'Show or Hide Debug Information', 'style' => 'alt', 'function' => 'toggle');
    $glyphbuttons[] = array('icon' => 'gi-fullscreen', 'colour' => 'default', 'disabled' => FALSE, 'tooltip' => 'Toggle Debug Information to full screen', 'style' => 'alt', 'function' => 'fullscreen');
    $titleitem = array('caption' => 'Debug Information', 'glyphbuttons' => $glyphbuttons);
    BlockTitle($titleitem, 8);
    Div(array('class' => 'block-content'), 8);
    $items = array('Local Storage Root' => CONSTLocalStorageRoot, 'Settings file' => CONSTConfigFile, 'Encryption Key' => $SYSTEM_SETTINGS["Security"]["EncryptionKey"],
                   'Debug Output Destination' => sys_get_temp_dir(), 'AWS SDK' => 'Version '.Aws::VERSION);
    if(defined("_SYSTEM_TTFONTS"))
    {
        $items['Font Folder'] = _SYSTEM_TTFONTS;
    }
    $desclist = array('orientation' => 'horizontal', 'items' => $items, 'id' => 'debuginfolist');
    DescriptionList($desclist, 7);
    Div(null, 8);
    Div(null, 7);
    Div(null, 6);
}
?>                     
                    </div>
				</div><!-- END Page Content -->

				<footer class="clearfix">
					<div class="pull-left">
						Developed by <a href="http://www.guidogybels.eu/" target="_blank">Guido Gybels</a>
					</div>
					<div class="pull-right">
						<a href="/about.php" target="_blank">About</a> &middot; <a href="#modal-terms" data-toggle="modal" class="register-terms">Terms</a><span class="hidden-xs"> &middot; Designed for <a href="http://www.mozilla.org/en-US/firefox/fx/" target="_blank">Firefox</a> &amp; <a href="https://www.google.com/chrome/browser/desktop/" target="_blank">Chrome</a></span>
					</div>
				</footer>

			</div><!-- END Main Container -->



            <!-- Containers for modal dialogs -->
            <div id="dlgStandard" class="modal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                    </div>    
                </div>
            </div>

            <div id="dlgLarge" class="modal" data-backdrop="static" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                    </div>    
                </div>
            </div>

            <div id="dlgConfirmation" class="modal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h3 class="modal-title" id="dlgConfirmationTitle">Confirm</h3>
                        </div>
                        <div class="modal-body" id="dlgConfirmationBody">
                            <p>Are you sure?</p>
                        </div>
                        <div class="modal-footer">
					       <button id="dlgConfirmationBtnYes" type="button" class="btn btn-sm btn-success" name="yes"><i class="gi gi-ok_2"></i> Yes</button>
					       <button id="dlgConfirmationBtnNo" type="button" class="btn btn-sm btn-primary" data-dismiss="modal" name="no"><i class="gi gi-ban"></i> No</button>
					       <button id="dlgConfirmationBtnCancel" type="button" class="btn btn-sm btn-warning" data-dismiss="modal" name="cancel"><i class="gi fa-times"></i> Cancel</button>
					       <button id="dlgConfirmationBtnRetry" type="button" class="btn btn-sm btn-primary" data-dismiss="modal" name="cancel"><i class="gi hi-repeat"></i> Retry</button>
                        </div>
                    </div>    
                </div>
            </div>

		</div><!-- END Page Container -->
		<!-- Scroll to top link, initialized in js/app.js - scrollToTop() -->
		<a href="#" id="to-top"><i class="fa fa-angle-double-up"></i></a>

		<!-- Include local copy of Jquery library -->
		<script src="/js/vendor/jquery-1.12.0.min.js"></script>

		<!-- Bootstrap.js, Bootbox.js, Jquery plugins and Custom JS code -->
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/plugins.js"></script>
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/slib.js"></script>
		<script src="/js/vendor/bootstrap.min.js"></script>
		<script src="/js/proui.js"></script>
		<script src="/js/app.js"></script>
         

        <!-- Modal Terms -->
<?php TermsAndConditions(2); ?>
        <!-- END Modal Terms -->

<?php
if((count($VALIDATION) > 0) || (isset($DATATABLES) && (count($DATATABLES) > 0)) || (count($HANDLERS) > 0))
{
	echo "\n\t\t<!-- JQuery routines for this Page -->\n";
    echo "\t\t<script type=\"text/javascript\">\n";
    echo "\t\t\tjQuery(function($) {\n\n";
    if(count($VALIDATION) > 0)
    {
        echo "\t\t\t\t//Form Validation\n";
        foreach($VALIDATION AS $aformid => $data) {
            jsValidation($aformid, $data);
        }
        echo "\n";
    }
    if(count($HANDLERS) > 0)
    {
        echo "\t\t\t\t//Attach Event Handlers\n";
        foreach($HANDLERS AS $controlid => $events)
        {
            foreach($events AS $eventname => $eventdata)
            {
                assert(isset($eventdata['function']));
                echo "\t\t\t\t$('#".escSelector($controlid)."').{$eventname}(function( event ) {\n";
                if(is_string($eventdata['function']))
                {
                    $lines = explode("\n", $eventdata['function']);
                    foreach($lines AS $line)
                    {
                        echo "\t\t\t\t\t".rtrim($line)."\n";
                    }
                }
                echo "\t\t\t\t})".(!empty($eventdata['firstrun']) ? ".".$eventname."()" : "").";\n";
            }
        }
//    $HANDLERS[$trusted_controlid][$trusted_event] = array('function' => $function, 'firstrun' => $firstrun);
    }
    if ((isset($DATATABLES)) && (count($DATATABLES) > 0))
    {
        echo "\t\t\t\t//Datatables Initialisation\n";
        echo "\t\t\t\tApp.datatables();\n\n";
        foreach($DATATABLES AS $datatable) {
            jsInitDatatable($datatable);            
        }
        echo "\n";
    }    
    echo "\t\t\t});\n";
    echo "\t\t</script>\n";
}

if(count($PLUGINS) > 0)
{
	echo "\n\t\t<!-- Load Additional Plugins for this page -->\n";
	foreach($PLUGINS AS $name => $script)
	{
        if(is_string($script))
        {
            if(strcasecmp(substr($script, 0, 4), 'http') == 0)
            {
                echo "\t\t<script src=\"{$script}\"></script>\n";
            }
            else
            {
                echo "\t\t<script src=\"js/{$script}\"></script>\n";
            }
        }
        elseif(is_array($script))
        {
            echo "\t\t<script type=\"text/javascript\">\n";
            foreach($script AS $line)
            {
                echo "\t\t\t".$line."\n";
            }
            echo "\t\t</script>\n";
        }
	}
}
?>
	</body>
</html>
<?php ob_end_flush(); ?>