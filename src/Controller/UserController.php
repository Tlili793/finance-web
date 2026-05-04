<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Email;
use App\Entity\Phone;
use App\Entity\Role;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Services\AiUserSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly AiUserSearchService $aiSearchService
    ) {}

    #[Route(name: 'admin_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q          = $request->query->get('q');
        $mode       = $request->query->get('mode', 'basic');
        $info       = null;
        $aiSuccess  = true;

        if ($q && $mode === 'ai') {
            $parsed     = $this->aiSearchService->parseSearchQuery($q);
            $users      = $userRepository->findByAiFilters($parsed['filters']);
            $info       = $parsed['explanation'];
            $aiSuccess  = $parsed['success'] ?? true;
        } elseif ($q) {
            $users = $userRepository->searchByNameOrEmail($q);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('admin/user/index.html.twig', [
            'users'        => $users,
            'explanation'  => $info,
            'search_term'  => $q,
            'search_mode'  => $mode,
            'ai_success'   => $aiSuccess,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $name     = $request->request->get('name');
        $emailStr = $request->request->get('email');
        $phoneStr = $request->request->get('phone');
        $password = $request->request->get('password');
        $roleId   = (int) $request->request->get('roleId', 2);

        if (!$name || !$emailStr || !$password) {
            $this->addFlash('danger', 'Please fill in all required fields.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail(new Email($emailStr));
        if ($phoneStr) {
            $user->setPhone(new Phone($phoneStr));
        }
        $role = $entityManager->find(Role::class, $roleId);
        if ($role) {
            $user->setRole($role);
        }

        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setIsActive(true);
        $user->setIsVerified(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'User ' . $name . ' created successfully.');
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($request->isMethod('POST')) {
            $name     = $request->request->get('name');
            $emailStr = $request->request->get('email');
            $phoneStr = $request->request->get('phone');
            $password = $request->request->get('password');
            $roleId   = (int) $request->request->get('roleId');

            $user->setName($name);
            $user->setEmail(new Email($emailStr));
            if ($phoneStr) {
                $user->setPhone(new Phone($phoneStr));
            } else {
                $user->setPhone(null);
            }

            $role = $entityManager->find(Role::class, $roleId);
            if ($role) {
                $user->setRole($role);
            }

            if ($password) {
                $user->setPassword($hasher->hashPassword($user, $password));
            }

            $entityManager->flush();
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('toggle-' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();

            $status = $user->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', 'User ' . $user->getName() . ' has been ' . $status . '.');
        }

        return $this->redirectToRoute('admin_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/reset-password', name: 'admin_user_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        if ($this->isCsrfTokenValid('reset-pwd-' . $user->getId(), $request->request->get('_token'))) {
            $newPassword = $request->request->get('new_password');
            if ($newPassword) {
                $user->setPassword($hasher->hashPassword($user, $newPassword));
                $entityManager->flush();
                $this->addFlash('success', 'Password for ' . $user->getName() . ' has been reset.');
            }
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
