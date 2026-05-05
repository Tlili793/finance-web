<?php
namespace App\Controller;

use App\Entity\ContractRequest;
use App\Entity\InsuredAsset;
use App\Repository\ContractRequestRepository;
use App\Repository\InsurancePackageRepository;
use App\Repository\InsuredAssetRepository;
use App\Service\InsuranceMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/insurance', name: 'insurance_')]
class InsuranceController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        ContractRequestRepository $requestRepo,
        InsuredAssetRepository $assetRepo
    ): Response {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $requests = $requestRepo->findBy(['user' => $user]);
        $assets   = $assetRepo->findBy(['user' => $user]);

        // 1. Requests by Status (for Pie Chart)
        $statusCounts = [];
        foreach ($requests as $req) {
            $s = $req->getStatus();
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }

        // 2. Assets by Type (for Bar Chart)
        $typeCounts = [];
        foreach ($assets as $asset) {
            $t = $asset->getType();
            $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
        }

        // 3. Requests over time (last 6 months)
        $timeline = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = (new \DateTime())->modify("-$i months")->format('M Y');
            $timeline[$date] = 0;
        }
        foreach ($requests as $req) {
            $date = $req->getCreatedAt() ? $req->getCreatedAt()->format('M Y') : null;
            if ($date && isset($timeline[$date])) {
                $timeline[$date]++;
            }
        }

        // 4. Total Value & Premium
        $totalPremium = 0;
        $totalAssetValue = 0;
        foreach ($requests as $req) {
            if ($req->getStatus() === 'APPROVED' || $req->getStatus() === 'SIGNED') {
                $totalPremium += (float)$req->getCalculatedPremium();
            }
        }
        foreach ($assets as $asset) {
            $totalAssetValue += (float)$asset->getDeclaredValue();
        }

        return $this->render('insurance/dashboard.html.twig', [
            'statusLabels'  => array_keys($statusCounts),
            'statusValues'  => array_values($statusCounts),
            'typeLabels'    => array_keys($typeCounts),
            'typeValues'    => array_values($typeCounts),
            'timelineLabels'=> array_keys($timeline),
            'timelineValues'=> array_values($timeline),
            'totalPremium'  => $totalPremium,
            'totalAssets'   => count($assets),
            'totalValue'    => $totalAssetValue,
            'recentRequests'=> array_slice(array_reverse($requests), 0, 5),
            'statusData'    => $statusCounts, // Still needed for the legend list
        ]);
    }

    #[Route('/dashboard/pdf', name: 'dashboard_pdf', methods: ['GET'])]
    public function downloadPdf(
        ContractRequestRepository $requestRepo,
        InsuredAssetRepository $assetRepo
    ): Response {
        $user = $this->getUser();
        if (!$user) throw $this->createAccessDeniedException();

        $requests = $requestRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $assets   = $assetRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $totalPremium = 0;
        foreach ($requests as $req) {
            if ($req->getStatus() === 'APPROVED' || $req->getStatus() === 'SIGNED') {
                $totalPremium += (float)$req->getCalculatedPremium();
            }
        }

        $html = $this->renderView('insurance/pdf_summary.html.twig', [
            'user'         => $user,
            'requests'     => $requests,
            'assets'       => $assets,
            'totalPremium' => $totalPremium,
            'date'         => new \DateTime(),
        ]);

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="insurance_summary.pdf"',
            ]
        );
    }

    #[Route('/contact', name: 'contact', methods: ['POST'])]
    public function contact(Request $request, InsuranceMailerService $mailer): Response
    {
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('error', 'Please login to send a support message.');
            return $this->redirectToRoute('app_login');
        }

        $mailer->sendSupportEmail($user, $subject, $message);

        $this->addFlash('success', 'Your message has been sent to our insurance support team. We will get back to you shortly.');
        return $this->redirectToRoute('insurance_dashboard');
    }

    // ─── ASSETS ───────────────────────────────────────────────────────────────

    #[Route('/assets', name: 'assets', methods: ['GET'])]
    public function assets(Request $request, InsuredAssetRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_login');
        }

        $q       = trim((string) $request->query->get('q', ''));
        $type    = (string) $request->query->get('type', '');
        $orderBy = (string) $request->query->get('order', 'a.createdAt');
        $dir     = strtoupper((string) $request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $assets = $repo->search($this->getUser(), $q ?: null, $type ?: null, $orderBy, $dir);
        // Debugging: uncomment to see count in error page
        // throw new \Exception("Found " . count($assets) . " assets for user " . $this->getUser()->getUserIdentifier());

        return $this->render('insurance/assets/index.html.twig', [
            'assets'  => $assets,
            'q'       => $q,
            'type'    => $type,
            'orderBy' => $orderBy,
            'dir'     => $dir,
        ]);
    }

    #[Route('/assets/new', name: 'asset_new', methods: ['GET', 'POST'])]
    public function newAsset(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $asset = new InsuredAsset();
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $asset->setUser($user);
            $asset->setReference($request->request->get('reference'));
            $asset->setType($request->request->get('type'));
            $asset->setDescription($request->request->get('description'));
            $asset->setLocation($request->request->get('location'));
            $asset->setBrand($request->request->get('brand'));
            $asset->setDeclaredValue($request->request->get('declared_value'));
            $asset->setManufactureDate(new \DateTime($request->request->get('manufacture_date')));

            $em->persist($asset);
            $em->flush();

            $this->addFlash('success', 'Asset registered successfully.');
            return $this->redirectToRoute('insurance_assets');
        }

        return $this->render('insurance/assets/new.html.twig');
    }

    #[Route('/assets/{id}/edit', name: 'asset_edit', methods: ['GET', 'POST'])]
    public function editAsset(InsuredAsset $asset, Request $request, EntityManagerInterface $em): Response
    {
        if ($asset->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $asset->setReference($request->request->get('reference'));
            $asset->setType($request->request->get('type'));
            $asset->setDescription($request->request->get('description'));
            $asset->setLocation($request->request->get('location'));
            $asset->setBrand($request->request->get('brand'));
            $asset->setDeclaredValue($request->request->get('declared_value'));
            $asset->setManufactureDate(new \DateTime($request->request->get('manufacture_date')));

            $em->flush();

            $this->addFlash('success', 'Asset updated successfully.');
            return $this->redirectToRoute('insurance_assets');
        }

        return $this->render('insurance/assets/edit.html.twig', ['asset' => $asset]);
    }

    #[Route('/assets/{id}/delete', name: 'asset_delete', methods: ['POST'])]
    public function deleteAsset(InsuredAsset $asset, EntityManagerInterface $em): Response
    {
        if ($asset->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($asset);
        $em->flush();

        $this->addFlash('success', 'Asset deleted.');
        return $this->redirectToRoute('insurance_assets');
    }

    // ─── PACKAGES ─────────────────────────────────────────────────────────────

    #[Route('/packages', name: 'packages', methods: ['GET'])]
    public function packages(Request $request, InsurancePackageRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) return $this->redirectToRoute('app_login');

        $q         = trim((string) $request->query->get('q', ''));
        $assetType = (string) $request->query->get('asset_type', '');
        $orderBy   = (string) $request->query->get('order', 'p.name');
        $dir       = strtoupper((string) $request->query->get('dir', 'ASC')) === 'ASC' ? 'ASC' : 'DESC';

        $packages = $repo->search($q ?: null, $assetType ?: null, $orderBy, $dir);

        return $this->render('insurance/packages/index.html.twig', [
            'packages'  => $packages,
            'q'         => $q,
            'assetType' => $assetType,
            'orderBy'   => $orderBy,
            'dir'       => $dir,
        ]);
    }

    // ─── CONTRACT REQUESTS ────────────────────────────────────────────────────

    #[Route('/requests', name: 'requests', methods: ['GET'])]
    public function requests(Request $request, ContractRequestRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $q       = trim((string) $request->query->get('q', ''));
        $status  = (string) $request->query->get('status', '');
        $orderBy = (string) $request->query->get('order', 'r.createdAt');
        $dir     = strtoupper((string) $request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $requests = $repo->search($this->getUser(), $q ?: null, $status ?: null, $orderBy, $dir);

        return $this->render('insurance/requests/index.html.twig', [
            'requests' => $requests,
            'q'        => $q,
            'status'   => $status,
            'orderBy'  => $orderBy,
            'dir'      => $dir,
        ]);
    }

    #[Route('/requests/new', name: 'request_new', methods: ['GET', 'POST'])]
    public function newRequest(
        Request $request,
        InsuredAssetRepository $assetRepo,
        InsurancePackageRepository $packageRepo,
        EntityManagerInterface $em
    ): Response {
        $assets   = $assetRepo->findBy(['user' => $this->getUser()]);
        $packages = $packageRepo->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $asset   = $assetRepo->find($request->request->get('asset_id'));
            $package = $packageRepo->find($request->request->get('package_id'));

            if (!$asset || $asset->getUser() !== $this->getUser()) {
                $this->addFlash('danger', 'Invalid asset selected.');
                return $this->redirectToRoute('insurance_request_new');
            }

            // Simple premium calculation: base_price * risk_multiplier
            $premium = round((float)$package->getBasePrice() * (float)$package->getRiskMultiplier(), 2);

            $contractRequest = new ContractRequest();
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $contractRequest->setUser($user);
            $contractRequest->setAsset($asset);
            $contractRequest->setPackage($package);
            $contractRequest->setCalculatedPremium((string)$premium);
            $contractRequest->setStatus('PENDING');

            $em->persist($contractRequest);
            $em->flush();

            $this->addFlash('success', 'Contract request submitted successfully.');
            return $this->redirectToRoute('insurance_requests');
        }

        return $this->render('insurance/requests/new.html.twig', [
            'assets'   => $assets,
            'packages' => $packages,
        ]);
    }

    #[Route('/requests/{id}/cancel', name: 'request_cancel', methods: ['POST'])]
    public function cancelRequest(ContractRequest $contractRequest, EntityManagerInterface $em): Response
    {
        if ($contractRequest->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($contractRequest->getStatus() === 'PENDING') {
            $contractRequest->setStatus('CANCELLED');
            $em->flush();
            $this->addFlash('success', 'Request cancelled.');
        } else {
            $this->addFlash('warning', 'Only pending requests can be cancelled.');
        }

        return $this->redirectToRoute('insurance_requests');
    }

    // ─── EDIT REQUEST (PENDING only) ──────────────────────────────────────────

    #[Route('/requests/{id}/edit', name: 'request_edit', methods: ['GET', 'POST'])]
    public function editRequest(
        ContractRequest $contractRequest,
        Request $request,
        InsuredAssetRepository $assetRepo,
        InsurancePackageRepository $packageRepo,
        EntityManagerInterface $em
    ): Response {
        if ($contractRequest->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($contractRequest->getStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Only pending requests can be edited.');
            return $this->redirectToRoute('insurance_requests');
        }

        $assets   = $assetRepo->findBy(['user' => $this->getUser()]);
        $packages = $packageRepo->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $asset   = $assetRepo->find($request->request->get('asset_id'));
            $package = $packageRepo->find($request->request->get('package_id'));

            if (!$asset || $asset->getUser() !== $this->getUser()) {
                $this->addFlash('danger', 'Invalid asset selected.');
                return $this->redirectToRoute('insurance_request_edit', ['id' => $contractRequest->getId()]);
            }

            if (!$package) {
                $this->addFlash('danger', 'Invalid package selected.');
                return $this->redirectToRoute('insurance_request_edit', ['id' => $contractRequest->getId()]);
            }

            // Recalculate premium with the new package
            $premium = round((float)$package->getBasePrice() * (float)$package->getRiskMultiplier(), 2);

            $contractRequest->setAsset($asset);
            $contractRequest->setPackage($package);
            $contractRequest->setCalculatedPremium((string)$premium);

            $em->flush();

            $this->addFlash('success', 'Contract request updated successfully.');
            return $this->redirectToRoute('insurance_requests');
        }

        return $this->render('insurance/requests/edit.html.twig', [
            'contractRequest' => $contractRequest,
            'assets'          => $assets,
            'packages'        => $packages,
        ]);
    }

    // ─── DELETE REQUEST ───────────────────────────────────────────────────────

    #[Route('/requests/{id}/delete', name: 'request_delete', methods: ['POST'])]
    public function deleteRequest(
        ContractRequest $contractRequest,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($contractRequest->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete-req-' . $contractRequest->getId(), $request->request->get('_token'))) {
            $em->remove($contractRequest);
            $em->flush();
            $this->addFlash('success', 'Request deleted successfully.');
        }

        return $this->redirectToRoute('insurance_requests');
    }
}
