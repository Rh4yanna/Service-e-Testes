<?php

namespace Tests\Unit;

use App\Models\Produto;
use App\Services\ProdutoService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProdutoServiceTest extends TestCase
{
    public function test_nao_cria_produto_com_preco_abaixo_da_margem(): void
    {
        $this->expectException(ValidationException::class);

        (new ProdutoService)->create([
            'nome' => 'Lajota',
            'preco_custo' => 100,
            'preco' => 109.99,
        ]);
    }

    public function test_atualiza_produto_com_dados_validos(): void
    {
        $produto = $this->produto();
        $dados = ['nome' => 'Lajota nova', 'preco_custo' => 120, 'preco' => 132];
        $produto->expects($this->once())->method('update')->with($dados)->willReturn(true);

        $this->assertSame($produto, (new ProdutoService)->update($produto, $dados));
    }

    public function test_rejeita_atualizacao_com_preco_abaixo_da_margem(): void
    {
        $produto = $this->produto();
        $produto->expects($this->never())->method('update');
        $this->expectException(ValidationException::class);

        (new ProdutoService)->update($produto, ['preco' => 109.99]);
    }

    public function test_rejeita_aumento_de_custo_que_invalida_a_margem(): void
    {
        $produto = $this->produto();
        $produto->expects($this->never())->method('update');
        $this->expectException(ValidationException::class);

        (new ProdutoService)->update($produto, ['preco_custo' => 110]);
    }

    public function test_atualiza_apenas_nome_usando_os_precos_atuais(): void
    {
        $produto = $this->produto();
        $dados = ['nome' => 'Lajota nova'];
        $produto->expects($this->once())->method('update')->with($dados)->willReturn(true);

        $this->assertSame($produto, (new ProdutoService)->update($produto, $dados));
    }

    public function test_reajusta_preco_com_arredondamento_para_centavos(): void
    {
        $produto = $this->produto();
        $produto->preco = 110.15;
        $produto->expects($this->once())->method('update')
            ->with(['preco' => 121.17])->willReturn(true);

        $this->assertSame($produto, (new ProdutoService)->reajustarPreco($produto, 10));
    }

    public function test_aceita_reajuste_no_limite_de_cinquenta_por_cento(): void
    {
        $produto = $this->produto();
        $produto->expects($this->once())->method('update')
            ->with(['preco' => 165.0])->willReturn(true);

        $this->assertSame($produto, (new ProdutoService)->reajustarPreco($produto, 50));
    }

    #[DataProvider('percentuaisInvalidos')]
    public function test_rejeita_percentual_de_reajuste_invalido(float $percentual): void
    {
        $produto = $this->produto();
        $produto->expects($this->never())->method('update');
        $this->expectException(ValidationException::class);

        (new ProdutoService)->reajustarPreco($produto, $percentual);
    }

    public static function percentuaisInvalidos(): array
    {
        return [
            'negativo' => [-10],
            'zero' => [0],
            'acima do limite' => [50.01],
            'infinito' => [INF],
            'nao numerico' => [NAN],
        ];
    }

    public function test_reajuste_tambem_respeita_a_margem_minima(): void
    {
        $produto = $this->produto();
        $produto->preco = 90;
        $produto->expects($this->never())->method('update');
        $this->expectException(ValidationException::class);

        (new ProdutoService)->reajustarPreco($produto, 10);
    }

    private function produto(): Produto
    {
        $produto = $this->getMockBuilder(Produto::class)->onlyMethods(['update'])->getMock();
        $produto->forceFill(['nome' => 'Lajota', 'preco_custo' => 100, 'preco' => 110]);

        return $produto;
    }
}
