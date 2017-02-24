<?php
/**
 * This file holds all main functions.
 *
 * @package Neostrada Tools
 */

use WHMCS\Database\Capsule;

// Stop loading this file if WHMCS isn't initialized.
if (!defined('WHMCS')) die('This file cannot be accessed directly.');

/**
 * Check if given domain exists in WHMCS and if it belongs
 * to the current user. It also check the account at Neostrada.
 */
function domainInSystem($key, $secret, $user_id, $domain_id) {
    $domain = Capsule::table('tbldomains')
        ->where('userid', '=', $user_id)
        ->where('id', '=', $domain_id)
        ->pluck('domain');

    if (empty($domain)) {
        return false;
    }

    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Execute the command.
    $API->prepare('domains');
    $API->execute();

    // Store the results.
    $results = $API->fetch();

    // Check if domain exists in account at Neostrada.
    if ($results['code'] == 200) {
        if (is_array($domain) && count($domain) == 1) {
            $domain = $domain[0];
        }
        
        if (!in_array($domain, $results['domains'])) {
            return false;
        }
    } else {
        return false;
    }

    return $domain;
}

/**
 * Get the records for the given domain.
 */
function getRecords($key, $secret, $domain) {
    list($domain, $ext) = explode('.', $domain);

    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Execute the command.
    $API->prepare('getdns', array(
        'domain'	=> $domain,
        'extension' => $ext
    ));
    $API->execute();
    
    // Store the results.
    $results = $API->fetch();

    // If any DNS records are found, store them in an array.
    if ($results['code'] == 200) {
        $records = array();
        foreach ($results['dns'] as $result) {
            list($rowid, $name, $type, $content) = explode(';', $result, 4);
            
            $content = explode(';', $content);
            $prio = array_pop($content);
            $ttl = array_pop($content);
            $content = implode(';', $content);

            $records[$rowid]['name']         = $name;
            $records[$rowid]['type']         = $type;
            $records[$rowid]['content']      = $content;
            $records[$rowid]['ttl']          = $ttl;
            $records[$rowid]['prio']         = $prio;
        }

        return $records;
    }

    return false;
}

/*
 * Edit records.
 *
 * This function also adds records when their fields are not empty.
 */
function editRecords($key, $secret, $domain) {
    global $types;

    list($domain, $ext) = explode('.', $domain);

    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Check if a record should be added.
    // Only add a record when no fields are empty.
    foreach ($_POST['add'] as $record) {
        if (!empty($record['name']) && !empty($record['type']) && !empty($record['content']) && !empty($record['ttl'])) {
            if (!in_array($record['type'], $types)) {
                return array('error' => 'Een van de geselecteerde record types wordt niet ondersteund.');
            }

            // Add the host name to the record name if it's not there yet.
            if (!endsWithHostname($record['name'], $domain . '.' . $ext)) {
                $record['name'] = $record['name'] . '.' . $domain . '.' . $ext;
            }

            // Check if the content is a valid IPv4 or IPv6 address.
            switch ($record['type']) {
                case 'A':
                    if (!validIPv4($record['content'])) {
                        return array('error' => 'Een van de IP-adressen is geen geldig IPv4-adres.');
                    }
                    break;

                case 'AAAA':
                    if (!validIPv6($record['content'])) {
                        return array('error' => 'Een van de IP-adressen is geen geldig IPv6-adres.');
                    }
                    break;
            }

            // Default to 0 if no priority is given.
            $record['prio'] = (empty($record['prio']) ? 0 : $record['prio']);

            // Execute the command.
            $API->prepare('adddns', array(
                'domain'	=> $domain,
                'extension' => $ext,
                'name'		=> $record['name'],
                'type'		=> $record['type'],
                'content'	=> $record['content'],
                'prio'		=> $record['prio'],
                'ttl'		=> $record['ttl']
            ));
            $API->execute();

            // Store the results.
            $results = $API->fetch();

            // If the record wasn't added, return false.
            if ($results['code'] != 200) {
                return array('error' => 'De records konden niet toegevoegd worden.');
            }
        }
    }

    // Reorder the array for use with the API and check if the selected type is accepted.
    $records = array();
    foreach ($_POST['records'] as $id => $record) {
        if (empty($record['name']) || empty($record['type']) || empty($record['content'])) {
            return array('error' => 'Voer een naam, een type en de inhoud in.');
        }
        
        if (empty($record['ttl']) || !is_numeric($record['ttl'])) {
            return array('error' => 'De TTL mag niet lager zijn dan 1.');
        }

        if (!in_array($record['type'], $types)) {
            return array('error' => 'Een van de geselecteerde record types wordt niet ondersteund.');
        }

        // Add the host name to the record name if it's not there yet.
        if (!endsWithHostname($record['name'], $domain . '.' . $ext)) {
            $record['name'] = $record['name'] . '.' . $domain . '.' . $ext;
        }

        // Check if the content is a valid IPv4 or IPv6 address.
        switch ($record['type']) {
            case 'A':
                if (!validIPv4($record['content'])) {
                    return array('error' => 'Een van de IP-adressen is geen geldig IPv4-adres.');
                }
                break;

            case 'AAAA':
                if (!validIPv6($record['content'])) {
                    return array('error' => 'Een van de IP-adressen is geen geldig IPv6-adres.');
                }
                break;
        }

        // Default to 0 if no priority is given.
        $records['prio'] = (empty($record['prio']) ? 0 : $record['prio']);

        $records[$id]['name']       = $record['name'];
        $records[$id]['type']       = $record['type'];
        $records[$id]['content']    = $record['content'];
        $records[$id]['ttl']        = $record['ttl'];
        $records[$id]['prio']       = $record['prio'];
    }

    // Execute the command.
    $API->prepare('dns', array(
        'domain'	=> $domain,
        'extension' => $ext,
        'dnsdata'	=> serialize($records)
    ));
    $API->execute();

    // Store the results.
    $results = $API->fetch();

    if ($results['code'] == 200) {
        return array('success' => 'De records zijn succesvol bijgewerkt.');
    }

    return array('error' => 'De records konden niet bijgewerkt worden.');
}

/**
 * Delete a record.
 */
function deleteRecord($key, $secret, $domain, $record_id) {
    list($domain, $ext) = explode('.', $domain);

    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Execute the command.
    $API->prepare('dns', array(
        'domain'	=> $domain,
        'extension' => $ext,
        'dnsdata'	=> serialize(array(
            $record_id => array(
                'delete' => true
            )
        ))
    ));
    $API->execute();

    // Store the results.
    $results = $API->fetch();

    if ($results['code'] == 200) {
        return true;
    }

    return false;
}

/**
 * Set or delete redirect.
 */
function setRedirect($key, $secret, $domain) {
    if (empty($_POST['redirect_url']) || empty($_POST['redirect_type'])) {
        return array('error' => 'Voer a.u.b. alle velden in.');
    }

    if ((int) $_POST['redirect_type'] < 1 || (int) $_POST['redirect_type'] > 3) {
        return array('error' => 'Het geselecteerde redirect type wordt niet ondersteund.');
    }

    if (isset($_POST['delete_redirect']) && (int) $_POST['delete_redirect'] != 1) {
        return array('error' => 'Er is iets fout gegaan.');
    }

    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Execute the command.
    $API->prepare('redirect', array(
        'domain'	    => $domain,
        'domainurl'     => $_POST['redirect_url'],
        'domaintype'    => $_POST['redirect_type'],
        'delete'        => (isset($_POST['delete_redirect']) ? 1 : 0)
    ));
    $API->execute();

    // Store the results.
    $results = $API->fetch();

    if ($results['code'] == 200) {
        if (isset($_POST['delete_redirect'])) {
            return array('success' => 'De redirect is succesvol verwijderd.<br><strong>LET OP:</strong> het duurt tot 2 uur voordat de wijziging effectief is.');
        } else {
            return array('success' => 'De redirect is succesvol bijgewerkt.<br><strong>LET OP:</strong> het duurt tot 2 uur voordat de wijziging effectief is.');
        }
    }

    return array('error' => 'De redirect kon niet bijgewerkt worden.');
}

/*
 * Get the redirect status for the given domain.
 */
function getRedirect($key, $secret, $domain) {
    // Initialize the client.
    $API = Neostrada::GetInstance();
    $API->SetAPIKey($key);
    $API->SetAPISecret($secret);

    // Execute the command.
    $API->prepare('getredirect', array(
        'domain' => $domain
    ));
    $API->execute();

    // Store the results.
    $results = $API->fetch();

    if ($results['code'] == 200) {
        return $results;
    }

    return false;
}

/**
 * Check if hostname ends with the domain.
 */
function endsWithHostname($name, $domain) {
    $parts = explode('.', $name);
    $last = array_slice($parts, -2);
    $last = $last[0] . '.' . $last[1];

    if ($last !== $domain) {
        return false;
    } else {
        return true;
    }
}

/**
 * Check if IP address is a valid IPv4 address.
 */
function validIPv4($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check if IP address is a valid IPv6 address.
 */
function validIPv6($ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
        return true;
    } else {
        return false;
    }
}