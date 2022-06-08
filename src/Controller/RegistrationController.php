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
use App\Security\SecurityTrait;

class RegistrationController extends AbstractController
{
    const ACTION_NAME = 'register';

    public $monologLogger;

    use SecurityTrait;

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
        $this->monologLogger = $monologLogger;
        $user = new User();

        try {
            $this->validatePayload($request);
            $this->isBasicAuthenticated($request);
            $this->decryptAndSaveUserData($request, $encryptor, $userPasswordHasher, $entityManager, $user);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ], $e->getCode());
        }

        $msg = 'New user was registered successfully.';
        $monologLogger->info($msg);

        return new JsonResponse([
                'status' => 'success',
                'code' => Response::HTTP_OK,
                'response' => ['hashedEmail' => $encryptor->encrypt($user->getEmail())],
                'message' => $msg
            ], Response::HTTP_OK);
    }
}
