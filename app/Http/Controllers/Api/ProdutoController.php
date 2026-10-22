<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Http\Request;

class ProdutoController extends Controller
{

    public function __construct(private ProdutoService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProdutoResource::collection(Produto::latest()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => 'nullable|string',
            'preco_custo' => ['required', 'numeric', 'min:0'],
            'preco' => ['required', 'numeric', 'min:0'],
            'quantidade' => 'sometimes|integer|min:0',
        ]);

        // Cria o Produto (Model)
        $produto = $this->service->create($dados);

        // Retorna um novo Recurso a cada requisição
        return new ProdutoResource($produto);
    }

    /**
     * Display the specified resource.
     */
    public function show(Produto $produto)
    {
        return new ProdutoResource($produto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Produto $produto)
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'string', 'max:255'],
            'descricao' => 'nullable|string',
            'preco_custo' => ['sometimes', 'numeric', 'min:0'],
            'preco' => ['sometimes', 'numeric', 'min:0'],
            'quantidade' => 'sometimes|integer|min:0',
        ]);

        $produto = $this->service->update($produto, $dados);

        return new ProdutoResource($produto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
