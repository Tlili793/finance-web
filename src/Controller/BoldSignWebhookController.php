<?php
namespace App\Controller;

use App\Repository\ContractRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\InsuranceMailerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Receives BoldSign webhook events and updates ContractRequest status accordingly.
 *
 * BoldSign signs every webhook POST with an HMAC-SHA256 header:
 *   X-BoldSign-Signature: sha256=<hex>
 *
 * Set BOLDSIGN_WEBHOOK_SECRET in .env to the secret you configured in the
 * BoldSign dashboard under "Webhook Secret".
 *
 * Relevant events handled:
 *   - DocumentSigned      → one signer has signed (intermediate, logged only)
 *   - DocumentCompleted   → all signers have signed → status = APPROVED
 *   - DocumentDeclined    → signer declined        → status = REJECTED
 */
#[Route('/insurance/boldsign/webhook', name: 'boldsign_webhook_primary', methods: ['POST'])]
#[Route('/webhook/boldsign', name: 'boldsign_webhook_alias', methods: ['POST'])]
class BoldSignWebhookController extends AbstractController
{
    public function __construct(
        private ContractRequestRepository $repo,
        private EntityManagerInterface    $em,
        private InsuranceMailerService    $mailer,
        private string                    $webhookSecret
    ) {}

    public function __invoke(
        Request         $request,
        LoggerInterface $logger,
    ): Response {
        // ── 1. Verify HMAC signature ──────────────────────────────────────────
        $rawBody   = $request->getContent();
        $signature = $request->headers->get('X-BoldSign-Signature', '');

        if (!$this->isSignatureValid($rawBody, $signature)) {
            $logger->warning('BoldSign webhook: invalid signature', [
                'received' => $signature,
            ]);
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        // ── 2. Decode payload ─────────────────────────────────────────────────
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $logger->warning('BoldSign webhook: invalid JSON payload');
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        // BoldSign sends the event type inside event.eventType or sometimes directly in event or type
        $event      = $payload['event']['eventType'] ?? $payload['event'] ?? $payload['type'] ?? null;
        $documentId = $payload['documentId'] ?? $payload['data']['documentId'] ?? null;

        $logger->info('BoldSign webhook received', [
            'event'      => $event,
            'documentId' => $documentId,
        ]);

        if (!$documentId) {
            return new Response('OK — no documentId', Response::HTTP_OK);
        }

        // ── 3. Find the matching ContractRequest ──────────────────────────────
        $req = $this->repo->findOneByBoldsignDocumentId($documentId);
        if (!$req) {
            $logger->warning('BoldSign webhook: no ContractRequest for documentId', [
                'documentId' => $documentId,
            ]);
            // Return 200 so BoldSign doesn't keep retrying for unknown docs
            return new Response('OK — unknown document', Response::HTTP_OK);
        }

        // ── 4. Handle each event type ─────────────────────────────────────────
        switch ($event) {
            case 'Completed':
            case 'DocumentCompleted':
            case 'document_completed':
                $req->setStatus('SIGNED');
                $this->em->flush();
                $this->mailer->sendContractSignedNotification($req);
                $logger->info('BoldSign: contract fully signed → SIGNED', [
                    'requestId'  => $req->getId(),
                    'documentId' => $documentId,
                ]);
                break;

            case 'Declined':
            case 'DocumentDeclined':
            case 'document_declined':
                $req->setStatus('REJECTED');
                $this->em->flush();
                $logger->warning('BoldSign: signer declined → REJECTED', [
                    'requestId'  => $req->getId(),
                    'documentId' => $documentId,
                ]);
                break;

            case 'Signed':
            case 'DocumentSigned':
            case 'document_signed':
                $req->setStatus('SIGNED');
                $this->em->flush();
                $logger->info('BoldSign: signer signed → SIGNED', [
                    'requestId'  => $req->getId(),
                    'documentId' => $documentId,
                ]);
                break;

            default:
                $logger->info('BoldSign webhook: unhandled event', ['event' => $event]);
                break;
        }

        return new Response('OK', Response::HTTP_OK);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifies the BoldSign HMAC-SHA256 webhook signature.
     *
     * BoldSign sends the header as: "sha256=<hex-digest>"
     * We compute HMAC-SHA256(webhookSecret, rawBody) and compare.
     * We skip verification if no secret is configured (dev/test only).
     */
    private function isSignatureValid(string $rawBody, string $header): bool
    {
        // Allow unauthenticated webhooks when no secret is set (dev only)
        if ($this->webhookSecret === '' || $this->webhookSecret === 'your_boldsign_webhook_secret_here') {
            return true;
        }

        // Header format:  sha256=<hexdigest>
        if (!str_starts_with($header, 'sha256=')) {
            return false;
        }

        $receivedHash = substr($header, 7);
        $expectedHash = hash_hmac('sha256', $rawBody, $this->webhookSecret);

        return hash_equals($expectedHash, $receivedHash);
    }
}
