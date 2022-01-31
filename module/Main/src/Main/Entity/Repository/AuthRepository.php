<?php
namespace Main\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class AuthRepository extends EntityRepository
{
    public function login(\Main\Entity\Auth $auth, $sm)
    {
        $authService = $sm->get('Zend\Authentication\AuthenticationService');
        $adapter = $authService->getAdapter();
        $adapter->setIdentityValue($auth->getUser());
        $adapter->setCredentialValue($auth->getPass());
        $authResult = $authService->authenticate();
        $identity =null;

        //if($authResult->isValid()){
            //$identity = $authResult->getIdentity();
            $authService->getStorage()->write($auth);
        //}

        return $authResult = 1;
    }
}