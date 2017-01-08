<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}


require_once(__DIR__ . '/lib/shipyard.php');

/**
 * Basic data about the module
 *
 *
 *
 * @see http://docs.whmcs.com/Provisioning_Module_Meta_Data_Parameters
 *
 * @return array
 */
function sinusdocker_MetaData() {
    return array(
        'DisplayName' => 'SinusBot docker',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '8080', // Default Non-SSL Connection Port
    );
}

/**
 * Additional fields for product configuration
 *
 *
 * Maximum 24 parameter supports the following types:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * 
 *
 * @return array
 */
function sinusdocker_ConfigOptions() {
    return array(
        'images' => array(
            'Type' => 'text',
            'Size' => '120',
            'Default' => 'images docker sinusbot',
            'Description' => '<br>A maximum of 120 characters',
        ), 'HTTP proto' => array(
            'Type' => 'dropdown',
            'Options' => array(
                'http' => 'http',
                'https' => 'https',
            ),
            'Description' => '<br>Choose HTTPS if you use an SSL certificate for bot',
        ), 'Префикс контейнера' => array(
            'Type' => 'text',
            'Size' => '30',
            'Default' => 'billing_',
            'Description' => 'Default containers will have a name: billing_ID services',
        ), 'Задержка' => array(
            'Type' => 'text',
            'Size' => '1',
            'Default' => '1',
            'Description' => 'By default, after the launch of the container script will wait 1 second before it will be to extract the password (this is necessary because the bot will start longer than executed php script)',
    ));
}

/**
 * Actions to create an instance of the new service / product
 *

 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function sinusdocker_CreateAccount(array $params) {
    try {
        $serviceid = $params['serviceid'];
        $template = $params['configoption1'];
        $prefix = $params['configoption3'];
        $pause = $params['configoption4'];
        $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];
        $repasswd = "/(?>33m)(.*)(?=\\[)/";
        $serverusername = $params['serverusername'];
        $serverpassword = $params['serverpassword'];

        $shipyard = new shipyard();

        //  we get token
        $token = $shipyard->autn($serverusername, $serverpassword, $url);

        // create a container
        $idcontainer = $shipyard->containerscreate($serviceid, $template, $serverusername, $token, $url, $prefix);

        //run container
        $shipyard->containersrestart($idcontainer, $serverusername, $token, $url);
        sleep($pause);
        //to receive a password for log retrieval
        $rawdata = $shipyard->containerslogs($idcontainer, $serverusername, $token, $url);

        //Get the password from the log
        preg_match($repasswd, $rawdata, $matches); // passwd sinusbot:  $matches[1]
        // We obtain information about the container
        $data = $shipyard->containersjson($idcontainer, $serverusername, $token, $url);

        // Here magic crutch offering a class name 8087 / tcp
        // we transform the object into an array
        $data = get_object_vars($data->NetworkSettings->Ports);
        $HostPort = $data['8087/tcp']['0']->HostPort;


        // We update the information
        $command = "updateclientproduct";
        $values["serviceid"] = $params['serviceid'];
        $values["serviceusername"] = 'admin';
        $values["servicepassword"] = $matches[1];
        $values["domain"] = $params['configoption2'] . '://' . $params['serverhostname'] . ':' . $HostPort . '/';
        $values["customfields"] = base64_encode(serialize(array("port" => $HostPort, "id container" => "$idcontainer")));

        $results = localAPI($command, $values);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Suspension of a product / service
 *
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function sinusdocker_SuspendAccount(array $params) {
    try {
        $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];

        $shipyard = new shipyard();

        //  we get token
        $token = $shipyard->autn($params['serverusername'], $params['serverpassword'], $url);

        // stop the container
        $shipyard->containersstop($params['customfields']['id container'], $params['serverusername'], $token, $url);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Un-suspend instance of a product/service.
 *
 * Called when an un-suspension is requested. This is invoked
 * automatically upon payment of an overdue invoice for a product, or
 * can be called manually by admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function sinusdocker_UnsuspendAccount(array $params) {
    try {

        $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];

        $shipyard = new shipyard();

        //  we get token
        $token = $shipyard->autn($params['serverusername'], $params['serverpassword'], $url);

        //run container
        $shipyard->containersrestart($params['customfields']['id container'], $params['serverusername'], $token, $url);

        // We obtain information about the container
        $data = $shipyard->containersjson($params['customfields']['id container'], $params['serverusername'], $token, $url);

        // Here magic crutch offering a class name 8087 / tcp
        // we transform the object into an array
        $data = get_object_vars($data->NetworkSettings->Ports);
        $HostPort = $data['8087/tcp']['0']->HostPort;


        // We update the information
        $command = "updateclientproduct";
        $values["serviceid"] = $params['serviceid'];
        $values["domain"] = $params['configoption2'] . '://' . $params['serverhostname'] . ':' . $HostPort . '/';
        $values["customfields"] = base64_encode(serialize(array("port" => $HostPort)));

        $results = localAPI($command, $values);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Terminate instance of a product/service.
 *
 * Called when a termination is requested. This can be invoked automatically for
 * overdue products if enabled, or requested manually by an admin user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return string "success" or an error message
 */
function sinusdocker_TerminateAccount(array $params) {
    try {

        $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];

        $shipyard = new shipyard();

        //  We get token
        $token = $shipyard->autn($params['serverusername'], $params['serverpassword'], $url);

        // remove
        $shipyard->containersdelete($params['customfields']['id container'], $params['serverusername'], $token, $url);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

/**
 * Checking the connection to the server.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function sinusdocker_TestConnection(array $params) {
    try {
        $success = true;
        $errorMsg = '';

        $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];
        $shipyard = new shipyard();

        $data = $shipyard->autn($params['serverusername'], $params['serverpassword'], $url);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

/**
 * Client area output logic handling.
 *
 * This function is used to define module specific client area output. It should
 * return an array consisting of a template file and optional additional
 * template variables to make available to that template.
 *
 * The template file you return can be one of two types:
 *
 * * tabOverviewModuleOutputTemplate - The output of the template provided here
 *   will be displayed as part of the default product/service client area
 *   product overview page.
 *
 * * tabOverviewReplacementTemplate - Alternatively using this option allows you
 *   to entirely take control of the product/service overview page within the
 *   client area.
 *
 * Whichever option you choose, extra template variables are defined in the same
 * way. This demonstrates the use of the full replacement.
 *
 * Please Note: Using tabOverviewReplacementTemplate means you should display
 * the standard information such as pricing and billing details in your custom
 * template or they will not be visible to the end user.
 *
 * @param array $params common module parameters
 *
 * @see http://docs.whmcs.com/Provisioning_Module_SDK_Parameters
 *
 * @return array
 */
function sinusdocker_ClientArea(array $params) {
    // Determine the requested action and set service call parameters based on
    // the action.
    $requestedAction = isset($_REQUEST['customAction']) ? $_REQUEST['customAction'] : '';

    $serverusername = $params['serverusername'];
    $serverpassword = $params['serverpassword'];
    $url = $params['serverhttpprefix'] . '://' . $params['serverhostname'] . ':' . $params['serverport'];

    $serviceAction = 'get_stats';
    $templateFile = 'templates/overview.tpl';

    try {

        $response = array();

        $shipyard = new shipyard();

        //  We get token
        $token = $shipyard->autn($serverusername, $serverpassword, $url);


        //to receive a password for log retrieval
        $logs = $shipyard->containerslogs($params['customfields']['id container'], $serverusername, $token, $url);
        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
                'logs' => $logs,
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
                'sinusdocker', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString()
        );

        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}
