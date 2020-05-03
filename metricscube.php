<?php

$appKey = '9c81a2f80dde24c71cdc4aec05599826';
if (isset($_GET['testConnection'])) {
    echo sprintf('%s|%s', $_GET['testConnection'], $appKey);
    die();
}
session_start();
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR);
$MCWarnings = [];
$MCmessages = [
    1  => 'MCWHI001|The installation cannot be performed because the script cannot find WHMCS\Database\Capsule class. It is a required class included by default in WHMCS since version 6.3. Your system may require an update. MetricsCube supports WHMCS V6.3 and later only.|Please make sure that you are using a compatible with MetricsCube version of WHMCS to proceed.',
    2  => 'MCWHI002|The script cannot find the cURL library, which is required by the MetricsCube Connector to work.|Please install the cURL library and run the installation again. <a href="https://stackoverflow.com/questions/38800606">https://stackoverflow.com/questions/38800606</a>',
    3  => 'MCWHI003|The script cannot connect to the MetricsCube server or it has returned an unexpected data format. There is also a chance that MetricsCube server may be temporarily down.|Please try again in a few minutes and make sure that your server can connect to %s.',
    4  => 'MCWHI004|The script cannot connect to the MetricsCube server: %s. There is also a chance that MetricsCube server may be temporarily down.|Please try again in a few minutes and make sure that your server can connect to %s.',
    5  => 'MCWHI005|The script cannot find the WHMCS files in %s directory. Most probably it has been copied to the wrong place.|Please make sure that you have uploaded the metricscube.php file to the main directory of your WHMCS (e.g. /var/www/domain.com/whmcs/)',
    6  => 'MCWHI006|The script cannot find the addon directory in %s path. Most probably the script has been copied to the wrong place.|Please make sure that you have uploaded the metricscube.php file to the main directory of your WHMCS (e.g. /var/www/domain.com/whmcs/).',
    7  => 'MCWHI007|The script has no permissions to create files in your WHMCS directory %s. It is possible that the PHP on your server does not have the right permissions to manage the files structure due to a wrong configuration or restrictive security rules.|If you are an experienced server administrator, please make sure that the script and PHP have the correct permissions to create files on your server. <strong>Otherwise, please go back to the MetricsCube Wizard and try the Standard Installation Method.</strong>',
    8  => 'MCWHI008|The script has no permissions to create files in your WHMCS Addons directory %s. It is possible that the PHP on your server does not have the required permissions set to manage the files structure due to a wrong configuration or restrictive security rules.|If you are an experienced server administrator, please make sure that the script and PHP have the correct permissions to create files on %s. <strong>Otherwise, please go back to the MetricsCube Wizard and try the Standard Installation Method.</strong>',
    9  => 'MCWHI009|The script cannot find the ZIP library for PHP on your server which is required to install the MetricsCube Connector module.|Please install the ZIP library and try the installation again. <a href="https://stackoverflow.com/questions/38104348">https://stackoverflow.com/questions/38104348</a>',
    10 => 'MCWHI010|The script has no permissions to create directories and files in your WHMCS directory (%s). It is possible that the PHP on your server does not have the right permissions to manage the files structure due to a wrong configuration or restrictive security rules.|If you are an experienced server administrator, please make sure that the script and PHP have the correct permissions to create files and directories on your server.|<strong>Otherwise, please go back to the MetricsCube Wizard and try the Standard Installation Method.</strong>',
    11 => 'MCWHI011|The script cannot download the MetricsCube Connector files. There may be a problem with the connection to %s from your server or there may be insufficient storage space (2 MB) on your server. There is also a chance that the MetricsCube server may be temporarily down.|Check if you have enough free space on your disk. Please try again in a few minutes and make sure that your server can connect to %s.',
    12 => 'MCWHI012|An error occurred when extracting MetricsCube Connector files (%s). There may be insufficient storage space (2 MB) or the ZipArchive PHP library has crashed.|Please check if you have enough free space on your disk. Try to run the MetricsCube Connector Installer again (refresh the page). <strong>If the problem still persists, please go back to the MetricsCube Wizard and try the Standard Installation Method.</strong>',
    30 => 'You can now go to your <a href="%s%s/%s">WHMCS admin area</a> to activate the MetricsCube Connector and proceed with the next steps to analyze your business!',
    31 => 'You can now go to your <strong>WHMCS admin area</strong> to activate the MetricsCube Connector in the "Addon Modules" section and proceed with the next steps to analyze your business!',
    40 => 'MetricsCube Connector Installer was unable to delete the addon archive file automatically.|Please delete the %s file from your server manually.',
    41 => 'MetricsCube Connector Installer was unable to delete its files automatically.|Please delete the %s file from your server manually.'
];

$apiUrl = 'https://api.metricscube.io/';

$MCsystemDetails = function() use ($appKey, $MCmessages) {
    $server    = [];
    $variables = ['HTTP_HOST', 'HTTP_X_FORWARDED_FOR', 'SERVER_SOFTWARE', 'SERVER_NAME', 'SERVER_ADDR', 'REMOTE_ADDR', 'DOCUMENT_ROOT', 'REQUEST_SCHEME', 'SCRIPT_FILENAME'];
    foreach ($variables as $variable) {
        if (isset($_SERVER[$variable])) {
            $server[$variable] = $_SERVER[$variable];
        }
    }
    $server['METRICSCUBE_INSTALLER_VERSION'] = '1.0.1';
    $server['METRICSCUBE_APP_KEY']           = $appKey;
    $server['ZIP_SUPPORTED']                 = class_exists('ZipArchive');
    $server['CURL_SUPPORTED']                = function_exists('curl_version');
    $server['CONNECTOR_TYPE']                = 'WHMCS';
    $server['PHP_VERSION']                   = phpversion();
    $server['PHP_TIMEZONE']                  = date_default_timezone_get();
    $server['PHP_TIMEZONE_DIFF']             = round(date('Z') / 3600, 2);
    $server['PHP_FORMAT_TIME']               = date('Y-m-d H:i:s');
    $server['PHP_MICROTIME']                 = microtime();
    $server['PHP_TIME']                      = time();
    $server['PHP_GMT_TIME']                  = gmdate('Y-m-d H:i:s');
    if (defined('WHMCS_LICENSE_DOMAIN')) {
        $server['WHMCS_LICENSE_DOMAIN'] = WHMCS_LICENSE_DOMAIN;
    }
    if (defined('WHMCS_LICENSE_IP')) {
        $server['WHMCS_LICENSE_IP'] = WHMCS_LICENSE_IP;
    }
    if (defined('WHMCS_LICENSE_DIR')) {
        $server['WHMCS_LICENSE_DIR'] = WHMCS_LICENSE_DIR;
    }
    if (defined('ROOTDIR')) {
        $server['WHMCS_ROOTDIR'] = ROOTDIR;
    }
    if (defined('WHMCS')) {
        $server['WHMCS'] = WHMCS;
    }
    $version = \WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'Version')->first();
    if (isset($version->value)) {
        $server['WHMCS_VERSION'] = $version->value;
    } else {
        $server['WHMCS_VERSION'] = 'undefined';
    }
    $name = \WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'CompanyName')->first();
    if (isset($name->value)) {
        $server['WHMCS_COMPANY_NAME'] = $name->value;
    }
    $logo = \WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'LogoURL')->first();
    if (isset($logo->value)) {
        $server['WHMCS_LOGO_URL'] = $logo->value;
    }
    $url = \WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->first();
    if (isset($url->value)) {
        $server['WHMCS_SYSTEM_URL'] = $url->value;
    }
    return $server;
};

$MCsend = function($url, $data = array()) use($apiUrl, $appKey, $MCsystemDetails, $MCmessages) {
    $data = array_merge($MCsystemDetails($appKey), $data);
    $url  = sprintf('%s/%s', rtrim($apiUrl, '/'), $url);
    if (!extension_loaded('curl')) {
        throw new Exception($MCmessages[2]);
    }
    $curl     = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_HTTPHEADER     => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        CURLOPT_POSTFIELDS     => http_build_query($data)
    ));
    $response = curl_exec($curl);
    if (curl_error($curl)) {
        $errorMessage = curl_error($curl);
        curl_close($curl);
        throw new Exception(sprintf($MCmessages[4], $errorMessage, $url));
    }
    curl_close($curl);
    $jsonData = json_decode($response, true);
    if (!is_array($jsonData) || !isset($jsonData['status']) || ($jsonData['status'] == 'error' && !isset($jsonData['message']))) {
        throw new Exception(sprintf($MCmessages[3], $url));
    }
    if ($jsonData['status'] == 'error' && isset($jsonData['message'])) {
        throw new Exception($jsonData['message']);
    }
    if (isset($jsonData['data'])) {
        return $jsonData;
    }
    throw new Exception(sprintf($MCmessages[3], $url));
};

$MCactivateAddon = function () use ($appKey, $MCmessages) {
    $addons = WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'ActiveAddonModules')->first();
    if (isset($addons->value)) {
        if (strpos($addons->value, 'MetricsCube') === false) {
            if (strlen($addons->value) > 0) {
                WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'ActiveAddonModules')->update(array('value' => $addons->value . ',MetricsCube'));
            } else {
                WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'ActiveAddonModules')->update(array('value' => 'MetricsCube'));
            }
        }
    } else {
        WHMCS\Database\Capsule::table('tblconfiguration')->insert(array('setting' => 'ActiveAddonModules', 'value' => 'MetricsCube'));
    }
    $appKeyQuery = WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MetricsCubeAppKey')->first();
    if (isset($appKeyQuery->value)) {
        WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MetricsCubeAppKey')->update(array('value' => $appKey));
    } else {
        WHMCS\Database\Capsule::table('tblconfiguration')->insert(array('setting' => 'MetricsCubeAppKey', 'value' => $appKey));
    }
    WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MetricsCubeConnKey')->delete();
    WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MetricsCubeSyncMethod')->delete();
    WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MetricsCubeError')->delete();
    WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'MCLastSynchronizationTime')->delete();

    $groups     = WHMCS\Database\Capsule::table('tbladminroles')->get();
    $addonPerms = WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'AddonModulesPerms')->first();
    if (isset($addonPerms->value)) {
        $addonPerms = unserialize($addonPerms->value);
        foreach ($groups as $group) {
            if (!isset($addonPerms[$group->id])) {
                $addonPerms[$group->id] = [];
            }
            if (!isset($addonPerms[$group->id]['MetricsCube'])) {
                $addonPerms[$group->id]['MetricsCube'] = 'MetricsCube Connector';
            }
        }
        WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'AddonModulesPerms')->update(array('value' => serialize($addonPerms)));
    } else {
        $addonPerms = [];
        foreach ($groups as $group) {
            $addonPerms[$group->id] = [
                'MetricsCube' => 'MetricsCube Connector'
            ];
        }
        WHMCS\Database\Capsule::table('tblconfiguration')->insert(array('setting' => 'AddonModulesPerms', 'value' => serialize($addonPerms)));
    }
    $addonAccess = WHMCS\Database\Capsule::table('tbladdonmodules')->where('setting', 'access')->where('module', 'MetricsCube')->first();
    $groupIds    = [];
    foreach ($groups as $group) {
        $groupIds[] = $group->id;
    }
    if (isset($addonAccess->value)) {
        WHMCS\Database\Capsule::table('tbladdonmodules')->where('setting', 'access')->where('module', 'MetricsCube')->update(array('value' => implode(',', $groupIds)));
    } else {
        WHMCS\Database\Capsule::table('tbladdonmodules')->insert(array('setting' => 'access', 'module' => 'MetricsCube', 'value' => implode(',', $groupIds)));
    }
};

$MCadminPanel = function ($MCWarnings) use($MCmessages) {
    $content   = '';
    $warnings  = '';
    $button    = '';
    $baseUrl   = str_replace('metricscube.php', '', $_SERVER['REQUEST_URI']);
    $moduleUrl = 'addonmodules.php?module=MetricsCube';
    $dirs      = array_filter(glob('*'), 'is_dir');
    $files     = ['addonmodules.php', 'clients.php', 'clientsadd.php', 'clientscontacts.php', 'configaddons.php', 'configdomains.php', 'invoices.php', 'supporttickets.php'];
    foreach ($dirs as $dir) {
        $dirFiles = scandir(__DIR__ . DS . $dir);
        if (count(array_intersect($dirFiles, $files)) == count($files)) {
            $desc    = sprintf($MCmessages[30], $baseUrl, $dir, $moduleUrl);
            $content = <<<EOF
<div class="main-container__desc">
    {$desc}
</div>
EOF;
            $button  = <<<EOF
<div class="main-container__actions">
    <a href="{$baseUrl}{$dir}/{$moduleUrl}" class="btn btn--success btn--outline">
        Go To The Module
    </a>
</div>
EOF;
        }
    }

    if (empty($content)) {
        $content = <<<EOF
<div class="main-container__desc">
    {$MCmessages[31]}
</div>
EOF;
    }

    if (!empty($MCWarnings)) {
        $warnings = <<<EOF
<div class="main-container__alert-container">
    <div class="alert alert--faded alert--info alert--border-left alert--sections m-b-0x">
        
EOF;
        foreach ($MCWarnings as $warning) {
            $desc     = explode('|', $warning);
            $warnings .= <<<EOF
        <div class="alert__section">
            <div class="alert__title">
                <div class="alert__title h6">
                    {$desc[0]}
                </div>
            </div>
            <div class="alert__body">
                <p>{$desc[1]}</p>
            </div>
        </div>
EOF;
        }
        $warnings .= <<<EOF
                
    </div>
</div>
EOF;
    }

    return <<<EOF
<div class="main-container__loader main-loader  is-completed">
    <div class="main-container__logo logo">
        <img src="https://api.metricscube.io/img/logo-solo-sign.svg" alt="logo">
    </div>
    <div class="main-loader__circle">
    </div>
    <div class="main-loader__checkmark"></div>
</div>  
<div class="main-container__title h3">
    MetricsCube Connector has been installed successfully. It's time to rock!
</div>
{$content}
{$warnings}
{$button}
EOF;

    return $MCmessages[31];
};

$MCRender = function ($html = '', $refresh = false) use($MCmessages) {
    $content = <<<EOF
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>MetricsCube Wizard</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta name="theme-color" content="#ffffff">
EOF;
    if ($refresh) {
        $content .= <<<EOF
                
        <meta http-equiv="refresh" content="3">
EOF;
    }
    $content .= <<<EOF
            
        <link href="https://fonts.googleapis.com/css?family=Nunito+Sans:00,300,400,700,800" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="https://api.metricscube.io/css/whmcs-quick-installation.css">
    </head>
    <body>
        <div class="main-container">
            {$html}
        </div>
    </body>
</html>
EOF;
    return $content;
};
if (!isset($_SESSION['MetricsCubeInstallation'])) {
    $_SESSION['MetricsCubeInstallation'] = true;
    $html                                = <<<EOF
<div class="main-container__loader main-loader">
    <div class="main-container__logo logo">
        <img src="https://api.metricscube.io/img/logo-solo-sign.svg" alt="logo">
    </div>
    <div class="main-loader__circle">
    </div>
    <div class="main-loader__checkmark"></div>
</div>  
<div class="main-container__title h3">
    MetricsCube Connector is being installed for you. Please stand by...
</div>
EOF;
    echo $MCRender($html, true);
    die;
}
unset($_SESSION['MetricsCubeInstallation']);

try {
    if (!file_exists(__DIR__ . DS . 'init.php')) {
        throw new Exception(sprintf($MCmessages[5], __DIR__));
    }
    require __DIR__ . DS . 'init.php';
    if (!class_exists('WHMCS\Database\Capsule')) {
        throw new Exception($MCmessages[1]);
    }
    $addonDir = __DIR__ . DS . 'modules' . DS . 'addons';
    if (!file_exists($addonDir)) {
        throw new Exception(sprintf($MCmessages[6], __DIR__));
    }
    if (!is_writable(__DIR__)) {
        throw new Exception(sprintf($MCmessages[7], __DIR__));
    }
    if (!is_writable($addonDir)) {
        throw new Exception(sprintf($MCmessages[8], $addonDir, $addonDir));
    }
    if (!class_exists('ZipArchive')) {
        throw new Exception($MCmessages[9]);
    }
    if (file_exists($addonDir . DS . 'MetricsCube')) {
        if (!is_writable($addonDir . DS . 'MetricsCube')) {
            throw new Exception(sprintf($MCmessages[10], $addonDir . DS . 'MetricsCube'));
        }
    } else if (!mkdir($addonDir . DS . 'MetricsCube', 0755)) {
        throw new Exception(sprintf($MCmessages[10], $addonDir));
    }
    $addonZipFileName = 'MetricsCube.zip';
    $apiResponse      = $MCsend('installation/install');
    $addonZipFile     = file_put_contents($addonZipFileName, base64_decode($apiResponse['data']['file']), LOCK_EX);
    if (FALSE === $addonZipFile) {
        throw new Exception(sprintf($MCmessages[11], $apiUrl . 'installation/install', $apiUrl . 'installation/install'));
    }
    $zip     = new ZipArchive;
    $archive = $zip->open($addonZipFileName);
    if ($archive === TRUE) {
        $zip->extractTo($addonDir . DS . 'MetricsCube');
        $zip->close();
        if (!unlink($addonZipFileName)) {
            $MCWarnings[] = sprintf($MCmessages[40], __DIR__ . '/' . $addonZipFileName);
        }
        if (!unlink('metricscube.php')) {
            $MCWarnings[] = sprintf($MCmessages[41], __DIR__ . '/metricscube.php');
        }
    } else {
        throw new Exception(sprintf($MCmessages[12], __DIR__ . '/' . $addonZipFileName));
    }
    $MCactivateAddon();
    echo $MCRender($MCadminPanel($MCWarnings));
} catch (Exception $exc) {
    $message  = $exc->getMessage();
    $msg      = explode('|', $message);
    $code     = isset($msg[1]) ? ' (' . $msg[0] . ')' : '';
    $content  = isset($msg[1]) ? $msg[1] : $msg[0];
    $solution = '';
    if (isset($msg[2])) {
        $solution = <<<EOF
            <div class="alert__section">
                <div class="alert__title">
                    How to solve it ?
                </div>
                <div class="alert__body">
                    <p>{$msg[2]}</p>
                </div>    
            </div>
EOF;
    }
    $html = <<<EOF
<div class="main-container__loader main-loader">
    <div class="main-container__logo logo">
        <img src="https://api.metricscube.io/img/logo-solo-sign.svg" alt="logo">
    </div>
    <div class="main-loader__circle"></div>
    <div class="main-loader__checkmark"></div>
</div>  
<div class="main-container__title h3">
    Oops! MetricsCube Connector Installer has faced a problem. Let's see...
</div>
<div class="main-container__alert-container">
    <div class="alert alert--faded alert--danger alert--border-left alert--sections m-b-0x">
        <div class="alert__section">
            <div class="alert__title has-actions">
                An error occurred {$code}
                <a class="btn btn--sm btn--danger btn--outline" href="mailto:support@metricscube.io">
                    <span class="btn__text">
                        Contact Support
                    </span>
                </a>
            </div>
            <div class="alert__body">
                <p>{$content}</p>
            </div>
        </div>
        {$solution}
    </div>
</div>                    
EOF;
    echo $MCRender($html);
    die();
}

