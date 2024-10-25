<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\TokenRepository;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class AuthController
 *
 * @package App\Http\Controllers
 * @author Vinícius Siqueira
 * @link https://github.com/ViniciusSCS
 * @date 2024-10-01 15:52:14
 * @copyright UniEVANGÉLICA
 */
class AuthController extends Controller
{
    private AuthenticationService $authService;

    public function __construct()
    {
        $this->authService = new AuthenticationService(new TokenRepository());
    }

    /**
     * Login endpoint
     */
    public function login(Request $request)
    {
        $loginRequest = new LoginRequest($request->all());
        
        if ($errors = $loginRequest->validateRequest()) {
            return $errors;
        }

        try {
            $result = $this->authService->authenticate([
                'email' => strtolower($loginRequest->email),
                'password' => $loginRequest->password
            ]);
            
            return $result['authenticated'] 
                ? $this->successfulLogin($result['user'])
                : $this->failedLogin();
                
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'message' => "Erro ao realizar login: " . $e->getMessage()
            ];
        }
    }

    /**
     * Logout endpoint
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->revokeToken($request->user());
            return [
                'status' => 200,
                'message' => "Usuário deslogado com sucesso!"
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'message' => "Erro ao realizar logout: " . $e->getMessage()
            ];
        }
    }

    private function successfulLogin($user)
    {
        return [
            'status' => 200,
            'message' => "Usuário logado com sucesso",
            'usuario' => $this->formatUserResponse($user)
        ];
    }

    private function failedLogin()
    {
        return [
            'status' => 404,
            'message' => "Usuário ou senha incorreto"
        ];
    }

    private function formatUserResponse($user)
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'token' => $user->token
        ];
    }
}

/**
 * Authentication Service Class
 */
class AuthenticationService
{
    private TokenRepository $tokenRepository;

    public function __construct(TokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    public function authenticate(array $credentials): array
    {
        if (Auth::attempt($credentials)) {
            $user = auth()->user();
            $user->token = $user->createToken($user->email)->accessToken;
            
            return [
                'authenticated' => true,
                'user' => $user
            ];
        }
        
        return [
            'authenticated' => false,
            'user' => null
        ];
    }

    public function revokeToken($user): void
    {
        $tokenId = $user->token()->id;
        $this->tokenRepository->revokeAccessToken($tokenId);
    }
}

/**
 * Login Request Validation Class
 */
class LoginRequest
{
    public $email;
    public $password;

    public function __construct(array $data)
    {
        $this->email = $data['email'] ?? null;
        $this->password = $data['password'] ?? null;
    }

    public function validateRequest(): ?array
    {
        $errors = [];

        if (!$this->email) {
            $errors[] = 'O campo email é obrigatório';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'O email fornecido não é válido';
        }

        if (!$this->password) {
            $errors[] = 'O campo senha é obrigatório';
        }

        if (!empty($errors)) {
            return [
                'status' => 422,
                'message' => 'Erros de validação',
                'errors' => $errors
            ];
        }

        return null;
    }
}
