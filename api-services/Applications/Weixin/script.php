<?php
// 自动加载类

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/Services/Member.php";


$Member = new Member();
$s = $Member->registerMember(json_encode(array('dkh_mobile' => 1762031120, 'dkh_address' => '长沙理工', 'dkh_real_name' => '志佳2', 'dkh_sex' => 1, 'dkh_birth' => time(), 'dkh_email' => '278119289@qq.com')));
//$s = $Member->modifyVipInfo(json_encode(array('dkh_member_id' => 'VIP201704110006','dkh_address'=>'测试','dkh_real_name'=>'名字','dkh_sex'=>1)));
//$s = $Member->getMemberInfo(json_encode(array('dkh_member_id' => 'VIP201704110005', 'dkh_mobile' => "17620386952",'dkh_address'=>'测试','dkh_real_name'=>'名字','dkh_sex'=>1)));
//$s = $Member->getModifyVipInfoList([]);
var_dump($s);
die;