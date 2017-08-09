<?php
require_once("initialise.inc");
?>
<?php
//If an authentication cookie is presented, its token will be checked. If found and still valid, the corresponding user details and permissions will be retrieved.
$PAGETITLE = "400 Bad Request";
switch("/error400.php") {
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
    header('Location: https://'.$_SERVER['HTTP_HOST'].'/login.php?redirect=/error400.php'.(empty($_SERVER['QUERY_STRING']) ? "" : "&".$_SERVER['QUERY_STRING']), TRUE, 307);
    die();
}
?>
<!DOCTYPE html>
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="en-GB"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en-GB"> <!--<![endif]-->
	<head>
		<meta charset="UTF-8">    
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<title><?php echo (!empty($PAGETITLE) ? $PAGETITLE : "400 Bad Request"); ?></title>
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
		<!-- Error Container -->
		<div id="error-container">
			<div class="error-options">
				<h3><i class="fa fa-chevron-circle-left text-muted"></i> <a href="javascript:void(0)" onclick="history.back();">Go Back</a></h3>
			</div>
			<div class="row">
				<div class="col-sm-8 col-sm-offset-2 text-center">


                    <h1><i class="fa fa-exclamation-triangle text-info animation-pulse"></i> 400</h1>
                    <h2 class="h3">Oops, we are sorry but there was an error processing your request. Please contact the system administrator.</h2>

         

				</div>
			</div>
		</div><!-- END Error Container -->
        <!-- Modal Terms -->
<?php TermsAndConditions(2); ?>
        <!-- END Modal Terms -->

	</body>
</html>
<?php ob_end_flush(); ?>