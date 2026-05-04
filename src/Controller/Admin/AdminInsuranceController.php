<?php
namespace App\Controller\Admin;

use App\Entity\InsurancePackage;
use App\Entity\ContractRequest;
use App\Repository\ContractRequestRepository;
use App\Repository\InsurancePackageRepository;
use App\Repository\InsuredAssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\BoldSignService;
use App\Service\InsuranceMailerService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/insurance', name: 'admin_insurance_')]
#[IsGranted('ROLE_ADMIN')]
class AdminInsuranceController extends AbstractController
{
    public function __construct(
        private readonly BoldSignService        $boldSign,
        private readonly InsuranceMailerService $mailer
    ) {}

    // ─── CONTRACT REQUESTS ────────────────────────────────────────────────────

    #[Route('/requests', name: 'requests', methods: ['GET'])]
    public function requests(ContractRequestRepository $repo): Response
    {
        return $this->render('admin/insurance/requests/index.html.twig', [
            'requests' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/requests/{id}/approve', name: 'request_approve', methods: ['POST'])]
    public function approveRequest(ContractRequest $req, EntityManagerInterface $em): Response
    {
        if ($req->getStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Request is not in PENDING status.');
            return $this->redirectToRoute('admin_insurance_requests');
        }

        try {
            // 1. Send signature request via BoldSign
            $documentId = $this->boldSign->sendForSignature(
                $req->getUser()->getId(),
                $req->getId(),
                $req->getUser()->getName() ?? 'User',
                $req->getAsset()->getReference(),
                $req->getPackage()->getName(),
                $req->getCalculatedPremium() ?? '0.00',
                new \DateTime(),
                $req->getUser()->getEmail()
            );

            // 2. Update status and store document ID
            $req->setStatus('WAITING_FOR_SIGNING');
            $req->setBoldsignDocumentId($documentId);
            $em->flush();

            // 3. Send system notification email
            $this->mailer->sendSignatureRequestNotification($req);

            $this->addFlash('success', "Request #{$req->getId()} approved. Sent to BoldSign for signing.");
        } catch (\Exception $e) {
            $this->addFlash('danger', "Could not send signature request: " . $e->getMessage());
        }

        return $this->redirectToRoute('admin_insurance_requests');
    }

    #[Route('/requests/{id}/reject', name: 'request_reject', methods: ['POST'])]
    public function rejectRequest(ContractRequest $req, EntityManagerInterface $em): Response
    {
        if ($req->getStatus() !== 'PENDING') {
            $this->addFlash('warning', 'Request is not in PENDING status.');
            return $this->redirectToRoute('admin_insurance_requests');
        }

        $req->setStatus('REJECTED');
        $em->flush();

        // Send rejection notification email
        $this->mailer->sendRejectionNotification($req);

        $this->addFlash('danger', "Request #{$req->getId()} rejected.");
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
            $assetId   = $request->request->get('asset_id');
            $packageId = $request->request->get('package_id');

            // Fetch package to read its pricing data; use getReference() for asset
            // since we only need it for the FK association (admin skips ownership check).
            $package = $packageRepo->find($packageId);

            if ($assetId && $package) {
                $asset = $em->getReference(\App\Entity\InsuredAsset::class, $assetId);
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
        // Verify existence before using a proxy — getReference() skips the SELECT
        // since em->remove() only needs the PK, not the full entity state.
        if (!$repo->find($id)) {
            throw $this->createNotFoundException();
        }

        $ref = $em->getReference(\App\Entity\ContractRequest::class, $id);
        $em->remove($ref);
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
