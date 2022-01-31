<?php
namespace Main\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function login(\Main\Entity\Users $auth, $sm)
    {
        $authService = $sm->get('Zend\Authentication\AuthenticationService');
        $adapter = $authService->getAdapter();
        $adapter->setIdentityValue($auth->getEmail());
        $adapter->setCredentialValue($auth->getPassword());
        $authResult = $authService->authenticate();
        $identity =null;

        //if($authResult->isValid()){
            //$identity = $authResult->getIdentity();
            $authService->getStorage()->write($auth);
        //}

        return $authResult = 1;
    }
}