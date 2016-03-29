<?php
/**
 * File.........: index.php
 * Author.......: Jared Eckersley
 * Description..: Controller for template application
 *
 * $Header$
 */

include('includes/emailExploder.class.php');
include('includes/tbs_class.php');
$tbs  = new clsTinyButStrong;
$list = new emailExploder;

$template = 'html/noside/';
$tbs->LoadTemplate($template . 'index.html');

$data = $list->getDB();
$lists = $data['lists'];
$membership = $data['users'];

$tbs->MergeBlock('drop,x',$lists);
$tbs->MergeBLock('list','array','lists[%p1%][info]');
$tbs->MergeBlock('user','array',"lists[%p1%][member]");
$tbs->MergeBlock('membership',$membership);

$tbs->Show();

?>