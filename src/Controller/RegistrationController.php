<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use OpenApi\Annotations as OA;

class RegistrationController extends AbstractController
{
    const ACTION_NAME = 'register';
    /**
     * @Route("/register", name="app_register")
     */
    /**
     * Register new user.
     *
     * @Route("/api/register", methods={"POST"})
     * @OA\Response(
     *     response=200,
     *     description="Returns new user data."
     * )
     * @OA\Tag(name="register")
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        LoggerInterface $monologLogger,
        Encryptor $encryptor
    ): JsonResponse
    {
        $params = $request->toArray();
        $user = new User();

        if (!is_array($params) || !isset($params['data']['email']) || !isset($params['data']['password'])) {
            $monologLogger->error('Login method. Invalid payload.');

            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        if (!isset($params['action']) || $params['action'] !== self::ACTION_NAME) {
            $monologLogger->error('Could not register a new user.');

            return new JsonResponse(null, Response::HTTP_BAD_REQUEST);
        }

        $decryptedPassword = $encryptor->decrypt($params['data']['password']);
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                   $user,
                   $decryptedPassword
                )
            );

        $decryptedEmail = $encryptor->decrypt($params['data']['email']);
        $user->setRoles($params['data']['roles']);
        $user->setEmail($decryptedEmail);

        $entityManager->persist($user);
        $entityManager->flush();

        $msg = 'New user ' . $user->getEmail() . ' was registered successfully.';
        $monologLogger->info($msg);

        return new JsonResponse([
                'status' => 'success',
                'code' => Response::HTTP_OK,
                'response' => ['hashedEmail' => $encryptor->encrypt($user->getEmail())],
                'message' => $msg
            ], Response::HTTP_OK);
    }
}
