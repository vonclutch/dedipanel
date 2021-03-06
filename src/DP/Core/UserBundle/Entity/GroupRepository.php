<?php

namespace DP\Core\UserBundle\Entity;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use DP\Core\UserBundle\Entity\Group;

class GroupRepository extends NestedTreeRepository
{
    public function getQBFindIsNot(Group $group)
    {
        $qb = $this->getQueryBuilder();
        $id = $group->getId();
        
        if (!empty($id)) {
            $qb->andWhere($this->getAlias().'.id != '.intval($id));
        }
        
        return $qb;
    }
    
    public function getAccessibleGroups($groups = array(), $retrieveAll = false)
    {
        $accessibleGroups = array();
                
        if ($retrieveAll) {
            return $this->getChildren(null, false, null, "asc", true);
        }
        
        foreach ($groups AS $group) {
            $children = $this->getChildren($group, false, null, "asc", true);
            
            $accessibleGroups = array_merge($accessibleGroups, $children);
        }
        
        return array_unique($accessibleGroups);
    }
}
