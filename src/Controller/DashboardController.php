<?php

namespace App\Controller;

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
    public function index(
        \App\Repository\ContractRequestRepository $requestRepo,
        \App\Repository\InsuredAssetRepository $assetRepo,
        \App\Repository\LoanRepository $loanRepo,
        \App\Repository\TransactionRepository $transactionRepo
    ): Response {
        $user = $this->getUser();
        
        return $this->render('dashboard/index.html.twig', [
            'requestCount' => $user ? count($requestRepo->findBy(['user' => $user])) : 0,
            'assetCount'   => $user ? count($assetRepo->findBy(['user' => $user])) : 0,
            'loanCount'    => $user ? count($loanRepo->findBy(['borrower' => $user])) : 0,
            'paymentCount' => $user ? count($transactionRepo->findBy(['user' => $user])) : 0,
        ]);
    }
}
