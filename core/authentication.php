<?php
// LDAP
function authenticate_ldap()
{
    $user = pv('user');
    $pass = pv('pass');
    if (!$user || !$pass) {
        return false;
    }
    ssv('user', NULL);
    $user = preg_replace('/^STAFF\\\/', '', $user);
    $suser = "STAFF\\$user";

    $ldaphosts = explode(';', Config::get('ldaphosts'));
    $ldaphost = current($ldaphosts);
    do {
        $ldap = @ldap_connect($ldaphost);
    } while (!$ldap && $ldaphost = next($ldaphosts));

    if (!$ldap) {
        ssv('message', 'No LDAP servers available.');
        redirect();
    }
    $bind = @ldap_bind($ldap, $suser, $pass);
    if ($bind) {
        require_once(Config::get('approot') . '/core/idboxapi.inc.php');
        $puid = idboxCall('GetPuid', array(
            'xp' => $user
        ));
        if (!$puid) {
            error_log("No puid for XP $user");
            return false;
        }
        if ($group = Config::get('idbox_allow_group')) {
            if (!idboxCall('InGroup', array(
                'puid' => $puid,
                'group_name' => $group
            ))
            ) {
                return false;
            }
        }
        $userinfo = idboxCall('PersonInfo', array(
            'puid' => $puid
        ));
        ssv('userinfo', $userinfo);
        ssv('user', $user);
        ssv('puid', $puid);
        ssv('firstname', $userinfo ['firstname']);
        ssv('lastname', $userinfo ['lastname']);
        ssv('message', 'Welcome, ' . $userinfo ['firstname'] . '!');

        $location = idboxCall('LibrarianBranches', array(
            'puid' => $puid
        ));

        if (isset($location) && count($location) > 0) {
            ssv('location', $location[0]);
        } else {
            ssv('location', '-1');
        }
        if (idboxCall('InGroup', array(
            'puid' => $puid,
            'group_name' => 'CR-Copyright'
        ))) {
            ssv('flash_redirect', '/home.copyright');
            ssv('isCopyright', true);
        } else {
            ssv('isCopyright', false);
        }

        return true;
    }
    ssv('message', 'Incorrect username or password');
    return false;
}

function authenticate_shibboleth()
{
    if (gv('token')) {
        $user = gv('user');
        $token = gv('token');
        $instant = gv('token');
        if ($user && $token && $instant) {
            $user = base64_decode($user);
            $instant = base64_decode($instant);
            $token = base64_decode($token);
            $ip = $_SERVER ['REMOTE_ADDR'];
            $hash = md5($user . 'banana' . $ip);
            if ($token == $hash) {
                require_once(Config::get('approot') . '/core/idboxapi.inc.php');
                $puid = idboxCall('GetPuid', array(
                    'cwl' => $user
                ));
                if ($group = Config::get('idbox_allow_group')) {
                    if (!$puid) {
                        return false;
                    }
                    if (!idboxCall('InGroup', array(
                        'puid' => $puid,
                        'group_name' => $group
                    ))
                    ) {
                        return false;
                    }
                }
                $userinfo = idboxCall('PersonInfo', array(
                    'puid' => $puid
                ));

                ssv('userinfo', $userinfo);
                ssv('user', $user);
                ssv('puid', $puid);
                ssv('firstname', $userinfo ['firstname']);
                ssv('lastname', $userinfo ['lastname']);
                ssv('message', 'Welcome, ' . $userinfo ['firstname'] . '!');

                $location = idboxCall('LibrarianBranches', array(
                    'puid' => $puid
                ));

                if (isset($location) && count($location) > 0) {
                    ssv('location', $location[0]);
                } else {
                    ssv('location', '-1');
                }
                if (idboxCall('InGroup', array(
                    'puid' => $puid,
                    'group_name' => 'CR-Copyright'
                ))) {
                    ssv('flash_redirect', '/home.copyright');
                    ssv('isCopyright', true);
                } else {
                    ssv('isCopyright', false);
                }

                return true;
            }
        }
    }
    return false;
}

function logged_in()
{
    if (sv('authentication') || Config::get('authentication') !== 'none') {
        return !is_null(sv('user'));
    }
    return false;
}

function require_login($type = 'ldap')
{
    ssv('authentication', $type);
    if (is_null(sv('user'))) {
        ssv('message', 'You must log in to access this feature.');
        redirect();
    }
}

function logout()
{
    ssv('user', NULL);
    ssv('location', NULL);
    redirect();
}
