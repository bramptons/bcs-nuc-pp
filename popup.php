<?php
require_once("initialise.inc");
require_once("bcswidgets.inc");

ob_start();
?>
<?php
//If an authentication cookie is presented, its token will be checked. If found and still valid, the corresponding user details and permissions will be retrieved.
$PAGETITLE = "Nucleus";
switch("/popup.php") {
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
    header('Location: https://'.$_SERVER['HTTP_HOST'].'/login.php?redirect=/popup.php'.(empty($_SERVER['QUERY_STRING']) ? "" : "&".$_SERVER['QUERY_STRING']), TRUE, 307);
    die();
}
?>
<!DOCTYPE html>
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en-GB"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en-GB"> <!--<![endif]-->
	<head>
		<meta charset="UTF-8">    
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<title><?php echo (!empty($PAGETITLE) ? $PAGETITLE : "Nucleus"); ?></title>
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
		<div id="page-container" class="sidebar-no-animations">


        <!-- Main Container -->
			<div id="main-container">
				
                
				<!-- Page content -->
				<div id="page-content">

					<!-- Page Header -->
					
                    
					
					
					<!-- END Page Header -->

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="block full">
<?php
Authenticate();
if ($AUTHENTICATION['Authenticated']) {
    $do = (!empty($_GET['do']) ? IdentifierStr($_GET['do']) : null);
    switch($do) {
        case 'export_table':
            Div(array('id' => 'divDPForm'), 8);
                SimpleHeading("Export", 3, "sub", 9);
                $url = "/syscall.php?do=exportDatatable";
                $str = $_SERVER['QUERY_STRING'];
                if (($pos = strpos($str, "&")) !== FALSE) {
                    $url .= substr($str, $pos);
                }
                $fields = array();
                $fields = array_merge($fields, array(
                    array('name' => 'Purpose', 'caption' => 'Purpose', 'kind' => 'control', 'type' => 'string', 'hint' => 'Explain the reason for exporting this data', 'required' => TRUE),
                    array('name' => 'HasThirdParty', 'caption' => 'Third Party?', 'kind' => 'control', 'type' => 'radios', 'options' => array('N' => 'No', 'Y' => 'Yes'), 'hint' => 'Indicate whether or not this data is handed over to a third party', 'required' => TRUE),
                    array('name' => 'ThirdPartyName', 'caption' => 'Name Third Party', 'kind' => 'control', 'type' => 'string', 'required' => TRUE),
                ));
                $fieldsets[] = array('fields' => $fields);
                $init = array(
/*                    "$('#divProgress').hide();",
                    "$('#divResult').hide();",
                    "$('#divError').hide();",*/
                    "$('#frmTableExport').find(\"input[type='radio']\").on('change', function() {",
                    "\t$('#frmTableExport\\\:ThirdPartyName').rules(($(this).val() == 'Y' ? 'add' : 'remove'), 'required');",
                    "});",
                );
                $formitem = array(
                    'id' => 'frmTableExport', 'style' => 'standard',
                    'datasource' => array('HasThirdParty' => 'Y'), 'buttons' => array(),
                    'fieldsets' => $fieldsets, 'borders' => FALSE,
                    'oninitialise' => $init,
                    'buttons' => array(
                        array('type' => 'button', 'id' => 'btnsubmit', 'colour' => 'success', 'icon' => 'fa-caret-right', 'iconalign' => 'left', 'caption' => 'Export',
                              'script' => "submitForm('frmTableExport', '$url', { defErrorDlg: false, "
                                         ."defSuccessDlg: false, parseJSON: true, modal: true, "
                                         ."cbSuccess: function( frmElement, jsonResponse ) { $('#divProgress').hide(); $('#spanSuccessText').text( jsonResponse.title ); $('#aSuccessLink').text( SinPlu(jsonResponse.exportcount, { noun: 'record' })+' written' ); $('#aSuccessLink').on('click', function() { DownloadDocument( jsonResponse.documentid ); }); $('#divResult').show(); }, "
                                         ."cbError: function( frmElement, jqxhr ) { $('#divProgress').hide(); $('#divError').show(); $('#spanErrorText').text('There was an error processing your request: '+jqxhr.statusText) },"
                                         ."cbPosted: function( frmElement ) { $('#divDPForm').hide(); $('#spinProgress').addClass('fa-spin'); $('#divProgress').show(); }"
                                         ." } );"
                        ),
                    ),
                );
                Form($formitem, 9);
                jsFormValidation($formitem['id'], TRUE, 9);                
//            ModalFooter("frmTableExport", $url, "function( frmElement, jsonResponse ) { LoadNotifications(); }", "Export", "function( frmElement ) { dlgBGProcessStarted('Your export request has started. You will be notified once it has been completed.'); }");
            Div(null, 8);
            Div(array('id' => 'divProgress', 'style' => 'display: none;'), 8);
            
?>
                                    <table class="table table-borderless remove-margin">
                                        <tbody>
                                            <tr>
                                                <td class="text-center">
                                                    <b>Please wait. Your request is being processed.</b> When the task is completed, the result will be shown here. You may close this window, you will be notified once the task is completed.
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-center text-primary">
                                                    <i id="spinProgress" class="fa fa-spinner fa-4x"></i>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
<?php
            Div(null, 8);
            Div(array('id' => 'divResult', 'style' => 'display: none;'), 8);
?>
                                    <div class="alert alert-success">
                                        <h4><i class="fa fa-check-circle"></i> Task Complete</h4>
                                        <span id="spanSuccessText">Your data is ready.</span> <a id="aSuccessLink" class="alert-link" href="javascript:void(0)">Click here to download.</a>
                                    </div>
<?php
            Div(null, 8);
            Div(array('id' => 'divError', 'style' => 'display: none;'), 8);
?>
                                    <div class="alert alert-danger">
                                        <h4><i class="fa fa-times-circle"></i> Error</h4>
                                        <span id="spanErrorText">There was an error processing your request.</span>
                                    </div>
<?php
            Div(null, 8);
            break;
    }
} elseif(defined('__DEBUGMODE') && __DEBUGMODE) {
    alertNoPermission();
}
?>
                            </div>
                        </div>                 
                    </div>
				</div><!-- END Page Content -->

				

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