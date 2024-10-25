<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class UserController
 *
 * @package App\Http\Controllers
 * @author Vinícius Siqueira
 * @link https://github.com/ViniciusSCS
 * @date 2024-08-23 21:48:54
 * @copyright UniEVANGÉLICA
 */
class UserController extends Controller
{
    private UserService $userService;
    private UserValidator $validator;
    private ResponseFormatter $responseFormatter;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->validator = new UserValidator();
        $this->responseFormatter = new ResponseFormatter();
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();
        return $this->responseFormatter->success('Usuários encontrados!!', $users);
    }

    public function me()
    {
        $user = $this->userService->getCurrentUser();
        return $this->responseFormatter->success('Usuário logado!', $user);
    }

    public function store(Request $request)
    {
        $validation = $this->validator->validateCreate($request->all());
        if ($validation !== true) {
            return $this->responseFormatter->error('Erro de validação', $validation, 422);
        }

        try {
            $user = $this->userService->createUser($request->all());
            return $this->responseFormatter->success('Usuário cadastrado com sucesso!!', $user);
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao cadastrar usuário', null, 500);
        }
    }

    public function show(string $id)
    {
        $user = $this->userService->getUser($id);
        
        if (!$user) {
            return $this->responseFormatter->error('Usuário não encontrado! Que triste!', null, 404);
        }

        return $this->responseFormatter->success('Usuário encontrado com sucesso!!', $user);
    }

    public function update(Request $request, string $id)
    {
        $validation = $this->validator->validateUpdate($request->all());
        if ($validation !== true) {
            return $this->responseFormatter->error('Erro de validação', $validation, 422);
        }

        try {
            $user = $this->userService->updateUser($id, $request->all());
            
            if (!$user) {
                return $this->responseFormatter->error('Usuário não encontrado! Que triste!', null, 404);
            }

            return $this->responseFormatter->success('Usuário atualizado com sucesso!!', $user);
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao atualizar usuário', null, 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $result = $this->userService->deleteUser($id);
            
            if (!$result) {
                return $this->responseFormatter->error('Usuário não encontrado! Que triste!', null, 404);
            }

            return $this->responseFormatter->success('Usuário deletado com sucesso!!');
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao deletar usuário', null, 500);
        }
    }
}

/**
 * Service class for handling user business logic
 */
class UserService
{
    public function getAllUsers()
    {
        return User::select('id', 'name', 'email', 'created_at')->paginate(10);
    }

    public function getCurrentUser()
    {
        return Auth::user();
    }

    public function getUser(string $id)
    {
        return User::find($id);
    }

    public function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    public function updateUser(string $id, array $data)
    {
        $user = User::find($id);
        if (!$user) {
            return null;
        }

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function deleteUser(string $id)
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        return $user->delete();
    }
}

/**
 * Validator class for user requests
 */
class UserValidator
{
    private array $createRules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6'
    ];

    private array $updateRules = [
        'name' => 'string|max:255',
        'email' => 'email',
        'password' => 'min:6'
    ];

    public function validateCreate(array $data)
    {
        return $this->validate($data, $this->createRules);
    }

    public function validateUpdate(array $data)
    {
        return $this->validate($data, $this->updateRules);
    }

    private function validate(array $data, array $rules)
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        return true;
    }
}

/**
 * Response formatter class for standardizing API responses
 */
class ResponseFormatter
{
    public function success(string $message, $data = null, int $status = 200): array
    {
        $response = [
            'status' => $status,
            'message' => $message
        ];

        if ($data !== null) {
            $response['user'] = $data;
        }

        return $response;
    }

    public function error(string $message, $errors = null, int $status = 400): array
    {
        $response = [
            'status' => $status,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $response;
    }
}