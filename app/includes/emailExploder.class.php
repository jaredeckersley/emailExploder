<?php
/**
 * Filename.....: emailExploder.class.php
 * Class........: emailExploder
 * Author.......: Jared Eckersley
 * Description..: Pull data from emailExploder DB for browser consumption.
 *
 */

class emailExploder {

  private $db;
  private $host = 'mysql.yourdomain.com';
  private $dbuser = 'youruser';
  private $dbpass = 'yourpass';

  public function __construct() {
    try {
      $this->db = new PDO("mysql:host={$host};dbname=emailExploder;charset=utf8", $dbuser, $dbpass);
    } catch (PDOException $ex) {
      print "NO DB CONNECTION";
      exit;
    }
  }

  // query exploder tables and return an array of distribution lists and members
  // look at line 47 and change @yourdomain.com to your actual domain.
  public function getDB() {
    $data = array();

    $sql = "SELECT listMembers.MAIL AS EMAIL, exploderLists.MAIL AS LIST FROM emailExploder.listMembers LEFT JOIN exploderLists ON exploderLists.ID = listMembers.EID ORDER BY listMembers.MAIL";
    $res = $this->db->query($sql);
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
      if (!isset($data['users'][$row['EMAIL']]))
        $data['users'][$row['EMAIL']] = '';
      $data['users'][$row['EMAIL']] .= "<li class='comment odd'>" . $row['LIST'] . '</li>';
    }

    $sql = "SELECT * FROM exploderLists ORDER BY MAIL";
    $res = $this->db->query($sql);
    $sql = "SELECT * FROM listMembers WHERE EID = :ID ORDER BY MAIL";
    $stmt = $this->db->prepare($sql);

    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
      $link = "<a href='mailto:{$row['MAIL']}'>{$row['MAIL']}</a>";
      if ($row['TYPE'] == 'GOOGLE') 
        $link .= " - [ <a href='mailto:{$row['NAME']}+subscribe@yourdomain.com'>Subscribe</a> ]";
      $data['lists'][$row['NAME']]['info'][] = array('name'=>$row['NAME'],'description'=>$row['DESCRIPTION'],'mail'=>$link,'x'=>$row['MAIL']);
      $stmt->execute(array(':ID'=>$row['ID']));
      while ($member = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data['lists'][$row['NAME']]['member'][$member['MAIL']] = array('displayname'=>$member['MAIL'],'mail'=>$member['MAIL']);
      }
    }

    return $data;
  }

}
?>