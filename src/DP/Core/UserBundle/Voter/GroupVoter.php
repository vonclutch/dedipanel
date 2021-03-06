<?php

namespace DP\Core\UserBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use DP\Core\UserBundle\Entity\GroupRepository;

class GroupVoter implements VoterInterface
{
    protected $repo;
    
    public function __construct(GroupRepository $repo)
    {
        $this->repo = $repo;
    }
    
    public function supportsAttribute($attribute)
    {
        return preg_match('#^ROLE_DP_#', $attribute);
    }
    
    public function supportsClass($class)
    {
        return $class == 'DP\Core\UserBundle\Entity\Group';
    }
    
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if ($this->supportsClass(get_class($object))) {
            if (in_array($object, $this->repo->getAccessibleGroups($token->getUser()->getGroups()))) {
                return VoterInterface::ACCESS_GRANTED;
            }
            else {
                return VoterInterface::ACCESS_DENIED;
            }
        }
        
        return VoterInterface::ACCESS_ABSTAIN;
    }
}
