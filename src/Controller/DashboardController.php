<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    /*#[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
        ]);
    }*/

    #[Route('/', name: 'app_home')]
    public function index(Connection $db): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\User) {
            return $this->render('dashboard/index.html.twig', [
                'requestCount' => 0,
                'assetCount'   => 0,
                'loanCount'    => 0,
                'paymentCount' => 0,
            ]);
        }

        // Single round-trip: 4 COUNT(*) subqueries in one SQL statement.
        // Avoids 4 separate Doctrine repository calls on each dashboard load.
        $row = $db->fetchAssociative(
            'SELECT
                (SELECT COUNT(*) FROM contract_request WHERE user_id     = :uid) AS requestCount,
                (SELECT COUNT(*) FROM insured_asset    WHERE user_id     = :uid) AS assetCount,
                (SELECT COUNT(*) FROM loan             WHERE borrower_id = :uid) AS loanCount,
                (SELECT COUNT(*) FROM app_transaction  WHERE user_id     = :uid) AS paymentCount',
            ['uid' => $user->getId()],
            ['uid' => 'uuid']
        );

        return $this->render('dashboard/index.html.twig', [
            'requestCount' => (int) ($row['requestCount'] ?? 0),
            'assetCount'   => (int) ($row['assetCount']   ?? 0),
            'loanCount'    => (int) ($row['loanCount']    ?? 0),
            'paymentCount' => (int) ($row['paymentCount'] ?? 0),
        ]);
    }
}
