<?php
namespace Common\Model;
use Think\Model;
class OrgPartyModel extends Model {
  public function getOrgidByParty($party){
		$map['party'] = $party;
		$orgid = D('OrgParty')->where($map)->getField('orgid');
		return $orgid;
	}
}
