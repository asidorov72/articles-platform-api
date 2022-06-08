<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait SecurityTrait
{
    private function generateBasicToken(): string
    {
        $usr = $this->getParameter('auth_username');
        $psw = $this->getParameter('auth_password');

        return base64_encode($usr . ":" . $psw);
    }

    private function extractBasicCreds(string $token): array
    {
        $credStr = base64_decode($token);
        $credsArr = explode(':', $credStr, 2);

        return [
            'username' => $credsArr[0],
            'password' => $credsArr[1],
        ];
    }

    protected function validatePayload(Request $request)
    {
        $params = $request->toArray();

        if (!is_array($params) || !isset($params['data']['email']) || !isset($params['data']['password'])) {
            $msg = 'Login method. Invalid payload.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_BAD_REQUEST);
        }

        if (!isset($params['action']) || $params['action'] !== self::ACTION_NAME) {
            $msg = 'Could not register a new user.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_BAD_REQUEST);
        }
    }

    protected function isBasicAuthenticated(Request $request)
    {
        $basicAuthParams = $request->headers->get('Authorization');

        $this->monologLogger->info('REQUEST HEADERS PARAMETERS: ' . json_encode($basicAuthParams));

        if (empty($basicAuthParams)) {
            $msg = 'Basic Authentication is required.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_UNAUTHORIZED);
        }

        $basicTokenValue = explode(' ', $basicAuthParams, 2);

        if (empty($basicTokenValue) || empty($basicTokenValue[1])) {
            $msg = 'Basic Authentication token is missing.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_UNAUTHORIZED);
        }

        $token = $basicTokenValue[1];

        $generatedToken = $this->generateBasicToken();

        if ($generatedToken !== trim($token)) {
            $msg = 'Token is invalid.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function decryptAndSaveUserData(
        Request $request,
        Encryptor $encryptor,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        User &$user
    )
    {
        $params = $request->toArray();

        $decryptedPassword = $encryptor->decrypt($params['data']['password']);

        if (empty($decryptedPassword)) {
            $msg = 'Could not decrypt data.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $decryptedPassword
            )
        );

        $decryptedEmail = $encryptor->decrypt($params['data']['email']);

        if (empty($decryptedEmail)) {
            $msg = 'Could not decrypt data.';
            $this->monologLogger->error($msg);

            throw new \Exception($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user->setRoles($params['data']['roles']);
        $user->setEmail($decryptedEmail);

        $entityManager->persist($user);
        $entityManager->flush();
    }
}
