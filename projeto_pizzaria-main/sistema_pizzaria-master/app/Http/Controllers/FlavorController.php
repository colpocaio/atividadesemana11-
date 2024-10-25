<?php

namespace App\Http\Controllers;

use App\Http\Enums\TamanhoEnum;
use App\Models\Flavor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class FlavorController
 *
 * @package App\Http\Controllers
 * @author Vinícius Siqueira
 * @link https://github.com/ViniciusSCS
 * @date 2024-10-01 15:52:04
 * @copyright UniEVANGÉLICA
 */
class FlavorController extends Controller
{
    private FlavorService $flavorService;
    private FlavorValidator $validator;
    private ResponseFormatter $responseFormatter;

    public function __construct()
    {
        $this->flavorService = new FlavorService();
        $this->validator = new FlavorValidator();
        $this->responseFormatter = new ResponseFormatter();
    }

    public function index()
    {
        $flavors = $this->flavorService->getAllFlavors();
        return $this->responseFormatter->success('Sabores encontrados!!', $flavors);
    }

    public function store(Request $request)
    {
        $validation = $this->validator->validateCreate($request->all());
        if ($validation !== true) {
            return $this->responseFormatter->error('Erro de validação', $validation, 422);
        }

        try {
            $flavor = $this->flavorService->createFlavor($request->all());
            return $this->responseFormatter->success('Sabor cadastrado com sucesso!!', $flavor);
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao cadastrar sabor', null, 500);
        }
    }

    public function show(string $id)
    {
        $flavor = $this->flavorService->getFlavor($id);
        
        if (!$flavor) {
            return $this->responseFormatter->error('Sabor não encontrado! Que triste!', null, 404);
        }

        return $this->responseFormatter->success('Sabor encontrado com sucesso!!', $flavor);
    }

    public function update(Request $request, string $id)
    {
        $validation = $this->validator->validateUpdate($request->all());
        if ($validation !== true) {
            return $this->responseFormatter->error('Erro de validação', $validation, 422);
        }

        try {
            $flavor = $this->flavorService->updateFlavor($id, $request->all());
            
            if (!$flavor) {
                return $this->responseFormatter->error('Sabor não encontrado! Que triste!', null, 404);
            }

            return $this->responseFormatter->success('Sabor atualizado com sucesso!!', $flavor);
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao atualizar sabor', null, 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $result = $this->flavorService->deleteFlavor($id);
            
            if (!$result) {
                return $this->responseFormatter->error('Sabor não encontrado! Que triste!', null, 404);
            }

            return $this->responseFormatter->success('Sabor deletado com sucesso!!');
        } catch (\Exception $e) {
            return $this->responseFormatter->error('Erro ao deletar sabor', null, 500);
        }
    }
}

/**
 * Service class for handling flavor business logic
 */
class FlavorService
{
    public function getAllFlavors()
    {
        return Flavor::select('id', 'sabor', 'preco', 'tamanho')->paginate(10);
    }

    public function getFlavor(string $id)
    {
        return Flavor::find($id);
    }

    public function createFlavor(array $data)
    {
        return Flavor::create([
            'sabor' => $data['sabor'],
            'preco' => $data['preco'],
            'tamanho' => TamanhoEnum::from($data['tamanho']),
        ]);
    }

    public function updateFlavor(string $id, array $data)
    {
        $flavor = Flavor::find($id);
        if (!$flavor) {
            return null;
        }

        $flavor->update($data);
        return $flavor;
    }

    public function deleteFlavor(string $id)
    {
        $flavor = Flavor::find($id);
        if (!$flavor) {
            return false;
        }

        return $flavor->delete();
    }
}

/**
 * Validator class for flavor requests
 */
class FlavorValidator
{
    private array $createRules = [
        'sabor' => 'required|string|max:255',
        'preco' => 'required|numeric|min:0',
        'tamanho' => 'required|string'
    ];

    private array $updateRules = [
        'sabor' => 'string|max:255',
        'preco' => 'numeric|min:0',
        'tamanho' => 'string'
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
            $response['sabores'] = $data;
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
