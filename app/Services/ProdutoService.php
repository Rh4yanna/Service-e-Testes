<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Validation\ValidationException;

class ProdutoService
{

    public function create(array $dados)
    {
        $this->verificarMargem($dados['preco'], $dados['preco_custo']);

        return Produto::create($dados);
    }

    public function update(Produto $produto, array $dados): Produto
    {
        $this->verificarMargem(
            $dados['preco'] ?? $produto->preco,
            $dados['preco_custo'] ?? $produto->preco_custo,
        );

        $produto->update($dados);

        return $produto;
    }

    public function reajustarPreco(Produto $produto, float $percentual): Produto
    {
        if (! is_finite($percentual) || $percentual <= 0 || $percentual > 50) {
            throw ValidationException::withMessages([
                'percentual' => 'O reajuste deve ser maior que zero e de no máximo 50 por cento.',
            ]);
        }

        return $this->update($produto, [
            'preco' => round($produto->preco * (1 + $percentual / 100), 2),
        ]);
    }

    private function verificarMargem(float $preco, float $custo)
    {
        $valor = $custo + (0.1 * $custo);
        $preco_minimo = round($valor, 2);

        if($preco < $preco_minimo){
            throw ValidationException::withMessages([
                'preco' => sprintf('O preço deve ser mais do que 10 por cento do valor de custo: %.2f', $preco_minimo),
            ]);
        }
    }
}
