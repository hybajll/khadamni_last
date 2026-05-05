<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/_debug', name: 'app_debug_')]
class DevDebugController extends AbstractController
{
    #[Route('/db', name: 'db', methods: ['GET'])]
    public function db(Request $request, Connection $connection): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }

        $ip = (string) $request->getClientIp();
        if (!in_array($ip, ['127.0.0.1', '::1'], true)) {
            throw $this->createAccessDeniedException();
        }

        $dbName = $connection->getDatabase();

        $userCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM `user`');
        $societyCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM `society`');

        $adminCount = (int) $connection->fetchOne("SELECT COUNT(*) FROM `user` WHERE type IN ('admin','ADMIN')");

        return $this->json([
            'env' => (string) $this->getParameter('kernel.environment'),
            'client_ip' => $ip,
            'database' => $dbName,
            'counts' => [
                'users' => $userCount,
                'admins' => $adminCount,
                'societies' => $societyCount,
            ],
        ]);
    }
}

