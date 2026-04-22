<?php
namespace App\Service;

use App\Entity\ContractRequest;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class InsuranceMailerService
{
    private MailerInterface $mailer;
    private Environment $twig;
    private string $senderEmail;

    public function __construct(MailerInterface $mailer, Environment $twig, string $mailerSender)
    {
        $this->mailer      = $mailer;
        $this->twig        = $twig;
        $this->senderEmail = $mailerSender;
    }

    /**
     * Notify user that their request is waiting for signature.
     */
    public function sendSignatureRequestNotification(ContractRequest $request): void
    {
        $this->sendEmail(
            $request->getUser()->getEmail(),
            'Action Required: Your Insurance Contract is Ready for Signing',
            'email/insurance/signature_request.html.twig',
            ['request' => $request]
        );
    }

    /**
     * Notify user that their contract is fully signed and active.
     */
    public function sendContractSignedNotification(ContractRequest $request): void
    {
        $this->sendEmail(
            $request->getUser()->getEmail(),
            'Success: Your Insurance Contract is Signed!',
            'email/insurance/contract_signed.html.twig',
            ['request' => $request]
        );
    }

    /**
     * Notify user that their request was rejected.
     */
    public function sendRejectionNotification(ContractRequest $request): void
    {
        $this->sendEmail(
            $request->getUser()->getEmail(),
            'Update on your Insurance Request',
            'email/insurance/request_rejected.html.twig',
            ['request' => $request]
        );
    }

    /**
     * Send a support message from a user to the admin.
     */
    public function sendSupportEmail($user, string $subject, string $message): void
    {
        $this->sendEmail(
            'mohamedwassim.tlili@gmail.com', // Admin support email
            'Support Inquiry: ' . $subject,
            'email/insurance/support_inquiry.html.twig',
            [
                'user'    => $user,
                'subject' => $subject,
                'message' => $message,
            ]
        );
    }

    private function sendEmail(string $to, string $subject, string $template, array $context): void
    {
        try {
            $html = $this->twig->render($template, $context);

            $email = (new Email())
                ->from($this->senderEmail)
                ->to($to)
                ->subject($subject)
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            // Silently fail or log in production
        }
    }
}
