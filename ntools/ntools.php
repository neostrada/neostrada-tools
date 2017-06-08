<?php
/**
 * This file holds the module configuration and sets up the
 * client area page.
 */

// Stop loading this file if WHMCS isn't initialized.
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

/**
 * Setup the module configuration.
 */
function ntools_config()
{
    $configarray = [
        'name'        => 'Neostrada Tools',
        'description' => 'Met deze module kunnen jouw klanten de DNS-records van hun domeinnamen beheren. Ook kunnen zij gemakkelijk een doorverwijzing instellen.',
        'version'     => '1.0',
        'author'      => 'Neostrada',
        'fields'      => [
            'api_key' => [
                'FriendlyName'  => 'API key',
                'Type'          => 'text',
                'Size'          => '50', ],
            'api_secret' => [
                'FriendlyName'  => 'API secret',
                'Type'          => 'password',
                'Size'          => '50', ],
    ], ];

    return $configarray;
}

/**
 * Setup the client area page.
 */
function ntools_clientarea($params)
{
    global $_LANG, $types;

    // Get the API key and API secret.
    $api_key = $params['api_key'];
    $api_secret = $params['api_secret'];

    // Accepted record types.
    $types = ['A', 'AAAA', 'CNAME', 'MX', 'SOA', 'TXT', 'SRV'];

    // Load required files.
    require_once 'inc/functions.php';
    require_once 'inc/Neostrada.inc.php';

    // Initiate the client area class and get the current user ID.
    $ca = new WHMCS_ClientArea();
    $user_id = $ca->getUserID();

    // Manage DNS records.
    if ($_GET['a'] == 'dns') {
        if (!empty($_GET['d']) && empty($_GET['r'])) {
            $domain_id = $_GET['d'];

            if (!$domain = domainInSystem($api_key, $api_secret, $user_id, $domain_id)) {
                $message = ['type' => 'danger', 'content' => 'De opgegeven domeinaam staat niet in ons systeem.'];

                return [
                    'pagetitle'     => 'Domeinnaam niet gevonden',
                    'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'Domeinnaam niet gevonden'],
                    'templatefile'  => 'message',
                    'requirelogin'  => true,
                    'vars'          => ['message' => $message],
                ];
            } else {
                // Update or get records.
                if (isset($_POST['edit_records'])) {
                    if ($result = editRecords($api_key, $api_secret, $domain)) {
                        if (isset($result['error'])) {
                            $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => $result['error']];
                        } elseif (isset($result['success'])) {
                            $_SESSION['ntools_message'] = ['type' => 'success', 'content' => $result['success']];
                        } else {
                            $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => 'Er is iets fout gegaan.'];
                        }
                    } else {
                        $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => 'De records konden niet gewijzigd worden.'];
                    }

                    header('Location: index.php?m=ntools&a=dns&d='.$domain_id);
                    exit;
                }

                if (isset($_SESSION['ntools_message'])) {
                    $message = $_SESSION['ntools_message'];
                    unset($_SESSION['ntools_message']);
                } else {
                    $message = [];
                }

                $records = getRecords($api_key, $api_secret, $domain);
                $vars = ['message' => $message, 'domain' => $domain, 'records' => $records, 'types' => $types, 'domain_id' => $domain_id];

                return [
                    'pagetitle'     => 'DNS beheer',
                    'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'index.php?m=ntools&a=dns&d='.$domain_id => 'DNS beheer'],
                    'templatefile'  => 'manage_dns',
                    'requirelogin'  => true,
                    'vars'          => $vars,
                ];
            }
        } elseif (!empty($_GET['d']) && !empty($_GET['r'])) {
            $domain_id = $_GET['d'];
            $record_id = $_GET['r'];

            if (!$domain = domainInSystem($api_key, $api_secret, $user_id, $domain_id)) {
                $message = ['type' => 'danger', 'content' => 'De opgegeven domeinnaam staat niet in ons systeem.'];

                return [
                    'pagetitle'     => 'Domeinnaam niet gevonden',
                    'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'Domeinnaam niet gevonden'],
                    'templatefile'  => 'message',
                    'requirelogin'  => true,
                    'vars'          => ['message' => $message],
                ];
            } else {
                if (deleteRecord($api_key, $api_secret, $domain, $record_id)) {
                    echo json_encode(['success' => true, 'message' => 'Record successfully deleted.']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Record not deleted.']);
                    exit;
                }
            }
        }

    // Manage redirect.
    } elseif ($_GET['a'] == 'redirect') {
        if (!empty($_GET['d'])) {
            $domain_id = $_GET['d'];

            if (!$domain = domainInSystem($api_key, $api_secret, $user_id, $domain_id)) {
                $message = ['type' => 'danger', 'content' => 'De opgegeven domeinaam staat niet in ons systeem.'];

                return [
                    'pagetitle'     => 'Domeinnaam niet gevonden',
                    'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'Domeinnaam niet gevonden'],
                    'templatefile'  => 'message',
                    'requirelogin'  => true,
                    'vars'          => ['message' => $message],
                ];
            } else {
                if (isset($_POST['manage_redirect'])) {
                    if ($result = setRedirect($api_key, $api_secret, $domain)) {
                        if (isset($result['error'])) {
                            $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => $result['error']];
                        } elseif (isset($result['success'])) {
                            $_SESSION['ntools_message'] = ['type' => 'success', 'content' => $result['success']];
                        } else {
                            $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => 'Er is iets fout gegaan.'];
                        }
                    } else {
                        $_SESSION['ntools_message'] = ['type' => 'danger', 'content' => 'De redirect kon niet bijgewerkt worden.'];
                    }

                    header('Location: index.php?m=ntools&a=redirect&d='.$domain_id);
                    exit;
                }

                if (isset($_SESSION['ntools_message'])) {
                    $message = $_SESSION['ntools_message'];
                    unset($_SESSION['ntools_message']);
                } else {
                    $message = [];
                }

                $redirect = getRedirect($api_key, $api_secret, $domain);
                $delete = !empty($redirect['redirecturl']) ? true : false;
                $vars = ['message' => $message, 'domain' => $domain, 'domain_id' => $domain_id, 'redirect' => $redirect['redirecturl'], 'type' => $redirect['redirecttype'], 'delete' => $delete];

                return [
                    'pagetitle'     => 'Redirect',
                    'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'index.php?m=ntools&a=redirect&d='.$domain_id => 'Redirect'],
                    'templatefile'  => 'manage_redirect',
                    'requirelogin'  => true,
                    'vars'          => $vars,
                ];
            }
        }

    // Show message when no action is given.
    } elseif (empty($_GET['a'])) {
        $message = ['type' => 'info', 'content' => 'Er is geen actie opgegeven.'];

        return [
            'pagetitle'     => 'Actie niet opgegeven',
            'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'Actie niet opgegeven'],
            'templatefile'  => 'message',
            'requirelogin'  => true,
            'vars'          => ['message' => $message],
        ];

    // Show message when no valid action is given.
    } else {
        $message = ['type' => 'info', 'content' => 'De opgegeven actie wordt niet ondersteund.'];

        return [
            'pagetitle'     => 'Actie niet ondersteund',
            'breadcrumb'    => ['clientarea.php?action=domains' => $_LANG['clientareanavdomains'], 'Actie niet ondersteund'],
            'templatefile'  => 'message',
            'requirelogin'  => true,
            'vars'          => ['message' => $message],
        ];
    }
}
