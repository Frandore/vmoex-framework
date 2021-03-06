<?php

/**
 * This file is part of project vmoex-framework.
 *
 * Author: Jake
 * Create: 2019-03-27 22:04:25
 */

namespace Yeskn\Support\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Yeskn\Support\AbstractControllerListener;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Yeskn\Support\Exceptions\AccessDeniedHttpException;

class ControllerCheckForMaintenanceMode extends AbstractControllerListener
{
    private $varDir;
    private $checker;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $em
        , $projectDir
        , AuthorizationCheckerInterface $checker
    ) {
        parent::__construct($tokenStorage, $em);
        $this->varDir = rtrim($projectDir, '/') . '/var';
        $this->checker = $checker;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $fs = new Filesystem();

        $isSuperAdmin = $this->checker->isGranted('ROLE_SUPER_ADMIN', $this->getUser());

        if ($fs->exists($this->varDir . '/maintain') && $isSuperAdmin === false) {
            $maintain = file_get_contents($this->varDir . '/maintain');
            $maintain = json_decode($maintain, true);

            $ts = time();

            if ($ts >= strtotime($maintain['maintain_start'])
                && $ts <= strtotime($maintain['maintain_stop'])
            ) {
                throw new AccessDeniedHttpException('维护中...', 502);
            } else {
                $fs->remove($this->varDir . '/maintain');
            }
        }
    }
}
