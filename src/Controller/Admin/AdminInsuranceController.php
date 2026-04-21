<?php
namespace App\Controller\Admin;

use App\Entity\InsurancePackage;
use App\Repository\ContractRequestRepository;
use App\Repository\InsurancePackageRepository;
use App\Repository\InsuredAssetRepository;
use App\Service\BoldSignService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/insurance', name: 'admin_insurance_')]
#[IsGranted('ROLE_ADMIN')]
class AdminInsuranceController extends AbstractController
{
    // ─── CONTRACT REQUESTS ────────────────────────────────────────────────────

    #[Route('/requests', name: 'requests', methods: ['GET'])]
    public function requests(ContractRequestRepository $repo): Response
    {
        return $this->render('admin/insurance/requests/index.html.twig', [
            'requests' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/requests/{id}/approve', name: 'request_approve', methods: ['POST'])]
    public function approveRequest(
        int $id,
        ContractRequestRepository $repo,
        EntityManagerInterface $em,
        BoldSignService $boldSign,
        LoggerInterface $logger
    ): Response {
        $req = $repo->find($id);
        if (!$req) {
            throw $this->createNotFoundException();
        }

        // Send the contract for e-signature via BoldSign
        try {
            $documentId = $boldSign->sendForSignature(
                userName:         $req->getUser()->getName(),
                userEmail:        $req->getUser()->getEmail(),
                assetReference:   $req->getAsset()->getReference(),
                assetType:        $req->getAsset()->getType(),
                insurancePackage: $req->getPackage()->getName(),
                coverageDetails:  $req->getPackage()->getCoverageDetails() ?? 'Standard coverage',
                approvedValue:    (string) $req->getCalculatedPremium(),
                contractDate:     new \DateTime(),
                signerEmail:      $req->getUser()->getEmail(),
                userId:           $req->getUser()->getId(),
                requestId:        $req->getId(),
            );

            $req->setBoldsignDocumentId($documentId);
            $req->setStatus('WAITING_FOR_SIGNING');
            $em->flush();

            $this->addFlash('success', "Request #{$id} approved — signature request sent to {$req->getUser()->getEmail()}.");
        } catch (\Throwable $e) {
            $logger->error('BoldSign error on request #{id}: {msg}', ['id' => $id, 'msg' => $e->getMessage()]);
            $this->addFlash('danger', "Could not send signature request: {$e->getMessage()}");
        }

        return $this->redirectToRoute('admin_insurance_requests');
    }

    #[Route('/requests/{id}/reject', name: 'request_reject', methods: ['POST'])]
    public function rejectRequest(int $id, ContractRequestRepository $repo, EntityManagerInterface $em): Response
    {
        $req = $repo->find($id);
        if (!$req) {
            throw $this->createNotFoundException();
        }

        $req->setStatus('REJECTED');
        $em->flush();

        $this->addFlash('danger', "Request #{$id} rejected.");
        return $this->redirectToRoute('admin_insurance_requests');
    }

    #[Route('/requests/{id}/edit', name: 'request_edit', methods: ['GET', 'POST'])]
    public function editRequest(
        int $id,
        ContractRequestRepository $repo,
        Request $request,
        InsuredAssetRepository $assetRepo,
        InsurancePackageRepository $packageRepo,
        EntityManagerInterface $em
    ): Response {
        $contractRequest = $repo->find($id);
        if (!$contractRequest) {
            throw $this->createNotFoundException();
        }

        // Admin can see all assets of this user
        $assets   = $assetRepo->findBy(['user' => $contractRequest->getUser()]);
        $packages = $packageRepo->findBy(['isActive' => true]);

        if ($request->isMethod('POST')) {
            $asset = $assetRepo->find($request->request->get('asset_id'));
            $package = $packageRepo->find($request->request->get('package_id'));

            if ($asset && $package) {
                $premium = round((float)$package->getBasePrice() * (float)$package->getRiskMultiplier(), 2);
                $contractRequest->setAsset($asset);
                $contractRequest->setPackage($package);
                $contractRequest->setCalculatedPremium((string)$premium);
                
                // Allow admin to also manually set status
                if ($status = $request->request->get('status')) {
                    $contractRequest->setStatus($status);
                }

                $em->flush();
                $this->addFlash('success', "Request #{$id} updated.");
                return $this->redirectToRoute('admin_insurance_requests');
            }
        }

        return $this->render('admin/insurance/requests/edit.html.twig', [
            'contractRequest' => $contractRequest,
            'assets' => $assets,
            'packages' => $packages,
        ]);
    }

    #[Route('/requests/{id}/delete', name: 'request_delete', methods: ['POST'])]
    public function deleteRequest(
        int $id,
        ContractRequestRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $req = $repo->find($id);
        if (!$req) {
            throw $this->createNotFoundException();
        }

        $em->remove($req);
        $em->flush();

        $this->addFlash('success', "Request #{$id} deleted.");
        return $this->redirectToRoute('admin_insurance_requests');
    }

    // ─── PACKAGES ─────────────────────────────────────────────────────────────

    #[Route('/packages', name: 'packages', methods: ['GET'])]
    public function packages(InsurancePackageRepository $repo): Response
    {
        return $this->render('admin/insurance/packages/index.html.twig', [
            'packages' => $repo->findAll(),
        ]);
    }

    #[Route('/packages/new', name: 'package_new', methods: ['GET', 'POST'])]
    public function newPackage(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $pkg = new InsurancePackage();
            $this->hydratePackage($pkg, $request);
            $em->persist($pkg);
            $em->flush();
            $this->addFlash('success', 'Package created.');
            return $this->redirectToRoute('admin_insurance_packages');
        }

        return $this->render('admin/insurance/packages/form.html.twig', [
            'package' => null,
            'action'  => $this->generateUrl('admin_insurance_package_new'),
        ]);
    }

    #[Route('/packages/{id}/edit', name: 'package_edit', methods: ['GET', 'POST'])]
    public function editPackage(InsurancePackage $package, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $this->hydratePackage($package, $request);
            $em->flush();
            $this->addFlash('success', 'Package updated.');
            return $this->redirectToRoute('admin_insurance_packages');
        }

        return $this->render('admin/insurance/packages/form.html.twig', [
            'package' => $package,
            'action'  => $this->generateUrl('admin_insurance_package_edit', ['id' => $package->getId()]),
        ]);
    }

    #[Route('/packages/{id}/toggle', name: 'package_toggle', methods: ['POST'])]
    public function togglePackage(InsurancePackage $package, EntityManagerInterface $em): Response
    {
        $package->setIsActive(!$package->isActive());
        $em->flush();
        $this->addFlash('success', 'Package status toggled.');
        return $this->redirectToRoute('admin_insurance_packages');
    }

    #[Route('/packages/{id}/delete', name: 'package_delete', methods: ['POST'])]
    public function deletePackage(InsurancePackage $package, EntityManagerInterface $em): Response
    {
        $em->remove($package);
        $em->flush();
        $this->addFlash('success', 'Package deleted.');
        return $this->redirectToRoute('admin_insurance_packages');
    }

    // ─── ASSETS (read-only) ───────────────────────────────────────────────────

    #[Route('/assets', name: 'assets', methods: ['GET'])]
    public function assets(InsuredAssetRepository $repo): Response
    {
        return $this->render('admin/insurance/assets/index.html.twig', [
            'assets' => $repo->findAll(),
        ]);
    }

    // ─── Private helper ───────────────────────────────────────────────────────

    private function hydratePackage(InsurancePackage $pkg, Request $request): void
    {
        $pkg->setName($request->request->get('name'));
        $pkg->setAssetType($request->request->get('asset_type'));
        $pkg->setDescription($request->request->get('description'));
        $pkg->setCoverageDetails($request->request->get('coverage_details'));
        $pkg->setBasePrice($request->request->get('base_price'));
        $pkg->setRiskMultiplier($request->request->get('risk_multiplier') ?: '1.00');
        $pkg->setDurationMonths((int)$request->request->get('duration_months'));
        $pkg->setIsActive((bool)$request->request->get('is_active', false));
    }
}
