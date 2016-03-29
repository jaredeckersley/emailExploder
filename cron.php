<?php
/*
I have my crontab setup to run every 15 minutes

15    *    *   *    *  php /path/to/emailexploder/cron.php >/dev/null 2>&1

*     *     *   *    *        command to be executed
-     -     -   -    -
|     |     |   |    |
|     |     |   |    +----- day of week (0 - 6) (Sunday=0)
|     |     |   +------- month (1 - 12)
|     |     +--------- day of        month (1 - 31)
|     +----------- hour (0 - 23)
+------------- min (0 - 59)

*/
  $start = microtime(true);

  // mysql settings
  $host = 'mysql.yourdomain.com';
  $db = 'database';
  $dbuser = 'youruser';
  $dbpass = 'yourpass';

  // Google settings
  // Also be sure to set the credentials to those of your servie account on line 56 below
  $client_id = 'yourclientid.apps.googleusercontent.com'; //Client ID
  $service_account_name = 'yourserviceaccount@developer.gserviceaccount.com'; //Email Address
  $key_file_location = 'yourkeyfile.p12'; //key.p12
  $domain = 'yourdomain.com';

  // AD settings
  // Also, have a look at the preg_replace on line 191 to make sure this will work for your AD setup.
  $ad = 'ad.yourdomain.com';
  $dn = "DC=yourdomain,DC=com";
  $aduser = 'youruser@yourdomain.com';
  $adpass = 'pass';


  try {
    $db = new PDO("mysql:host={$host};dbname={$db};charset=utf8", $dbuser, $dbpass);
  } catch (PDOException $ex) {
    print "NO DB CONNECTION";
    exit;
  }

  require_once('google-api-php-client/src/Google/autoload.php');
  $client = new Google_Client();
  $client->setApplicationName("Client_Library_Examples");
  $dir = new Google_Service_Directory($client);

  if (isset($_SESSION['service_token'])) {
    $client->setAccessToken($_SESSION['service_token']);
  }

  $key = file_get_contents($key_file_location);
  $cred = new Google_Auth_AssertionCredentials(
    $service_account_name,
    array('https://www.googleapis.com/auth/admin.directory.group.readonly'),
    $key
  );

  $cred->sub = "googleapps@yourdomain.com";
  $client->setAssertionCredentials($cred);
  if ($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion($cred);
  }

  $_SESSION['service_token'] = $client->getAccessToken();

  // Get all of our domain groups
  $googleGroups = $dir->groups->listGroups(array('domain'=>$domain));
  $x = $googleGroups->getGroups();
  while (isset($googleGroups->nextPageToken) && ($googleGroups->nextPageToken != '')) {
    $googleGroups = $dir->groups->listGroups(array('domain'=>$domain,'pageToken'=>$googleGroups->nextPageToken));
    $x = array_merge($x, $googleGroups->getGroups());
  }

  // Get existing lists from DB so we can compare against fresh list from Google and delete stale lists and insert new lists
  $sql = "SELECT * FROM exploderLists";
  $res = $db->query($sql);
  $lists = array();

  $sql = "SELECT MAIL FROM listMembers WHERE EID = :ID";
  $stmt = $db->prepare($sql);

  while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $lists[$row['ID']] = array();
    $lists[$row['ID']]['MEMBER'] = array();
    $lists[$row['ID']]['DATA'] = array(':ID'=>$row['ID'],':NAME'=>$row['NAME'],':DESCRIPTION'=>$row['DESCRIPTION'],':MAIL'=>$row['MAIL']);
    $stmt->execute(array('ID'=>$row['ID']));
    while ($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $lists[$row['ID']]['MEMBER'][$member['MAIL']] = $member['MAIL'];
    }
  }

  $sql = "INSERT INTO exploderLists VALUES(:ID,:NAME,:DESCRIPTION,:MAIL,'GOOGLE')";
  $stmt1 = $db->prepare($sql);

  $sql = "REPLACE INTO exploderLists VALUES(:ID,:NAME,:DESCRIPTION,:MAIL,'GOOGLE')";
  $stmtX = $db->prepare($sql);

  // Doing a batch query for all members of the groups greatly reduces execution time
  $client->setUseBatch(true);
  $batch = new Google_Http_Batch($client);
  foreach ($x as $k=>$v) {
    if (!isset($lists[$v->id])) {
      $stmt1->execute(array(':ID'=>$v->id,':NAME'=>$v->name,':DESCRIPTION'=>$v->description,':MAIL'=>$v->email));
    } else {
      $g = serialize(array(':ID'=>$v->id,':NAME'=>$v->name,':DESCRIPTION'=>$v->description,':MAIL'=>$v->email));
      $a = serialize($lists[$v->id]['DATA']);
      if ($a != $g) {
        $stmtX->execute(array(':ID'=>$v->id,':NAME'=>$v->name,':DESCRIPTION'=>$v->description,':MAIL'=>$v->email));
        unset($lists[$v->id]);
      }
    }

    $members = $dir->members->listMembers($v->email);
    $batch->add($members, $v->id);
  }
  // get all of our groups memberships in one query
  $results = $batch->execute();

  $sql = "DELETE FROM listMembers WHERE EID = :EID AND MAIL = :MAIL";
  $stmt1 = $db->prepare($sql);

  $sql = "INSERT INTO listMembers VALUES(:EID,:MAIL)";
  $stmt2 = $db->prepare($sql);
  $client->setUseBatch(False);
  foreach ($results as $k=>$v) {

    $all = $v->getMembers();
    $id = substr($k, 9);

    while (isset($v->nextPageToken) && ($v->nextPageToken != '')) {
      $members = $dir->members->listMembers($lists[$id]['DATA'][':MAIL'],array("pageToken"=>$v->nextPageToken));
      $v->nextPageToken = $members->nextPageToken;
      $all = array_merge($all, $members->getMembers());
    }

    foreach ($all as $key=>$val) {
      if (!isset($lists[$id]['MEMBER'][$val['email']])) {
        // insert new member
        $stmt2->execute(array(':EID'=>$id,':MAIL'=>$val['email']));
      } else {
        unset($lists[$id]['MEMBER'][$val['email']]);
      }
    }

    if (isset($lists[$id]) && count($lists[$id]['MEMBER']) > 0) {
      foreach ($lists[$id]['MEMBER'] as $key=>$val) {
        $stmt1->execute(array(':EID'=>$id,':MAIL'=>$key));
      }
    }

    if (isset($lists[$id])) {
      unset($lists[$id]);
    }

  }

  // Delete lists from the DB that might have existed before but were not present in this report from Google
  if (count($lists) > 0) {
    $sql = "DELETE FROM exploderLists WHERE ID = :ID";
    $stmt = $db->prepare($sql);
    foreach ($lists as $key=>$val) {
      $stmt->execute(array(':ID'=>$key));
    }
  }

  $sql = "SELECT * FROM exploderLists WHERE MAIL = :MAIL";
  $stmt1 = $db->prepare($sql);

  $sql = "UPDATE exploderLists SET TYPE = 'AD' WHERE MAIL = :MAIL";
  $stmt2 = $db->prepare($sql);

  $conn = ldap_connect($ad,'3268');
  ldap_bind($conn,$aduser,$adpass) or die("Active Directory Authentication Failed.");

  $groups = array();

  // this will build an array of all mail enabled groups
  $sql    = array('cn','member','description','mail','dn','objectguid');
  $filter = "(&(objectclass=Group) (mail=*))";
  $res = ldap_search($conn, $dn, $filter, $sql);
  $row = ldap_get_entries($conn, $res);

  foreach ($row as $key=>$val) {
    if (isset($val['mail'])) {
      if (!preg_match("/OU=Listserver Data,DC={$yourdomain},DC=com/",$val['dn'])) { // this list is controlled by AD - not sure if this is too specific to my AD setup.
        $mail = strtolower($val['mail'][0]);
        $stmt1->execute(array(':MAIL'=>$mail));
        if ($stmt1->rowCount() > 0) {
          $stmt2->execute(array(':MAIL'=>$mail));
        }
      }
    }
  }

  $time_elapsed_secs = microtime(true) - $start;
  print "\n\n" . $time_elapsed_secs . "\n\n";