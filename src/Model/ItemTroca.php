<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Item disponível para TROCA por outro item.
 * Mesmos métodos de ItemDoacao, respostas diferentes: isso é polimorfismo.
 */
class ItemTroca extends Item
{
    /** Sobrescreve o atributo herdado: todo objeto desta classe é uma troca. */
    protected string $tipo = 'troca';

    public function getBadgeTipo(): array
    {
        return [
            'rotulo' => 'Troca',
            'classe' => 'badge-azul',   // azul marinho
            'icone'  => 'bi-arrow-left-right',
        ];
    }

    public function getMensagemAcao(): string
    {
        return 'Tenho interesse em trocar';
    }

    public function getDescricaoTipo(): string
    {
        return 'Este item é trocado por outro item, sem dinheiro envolvido.';
    }

    public function getRegras(): array
    {
        return [
            'Não há venda: o pagamento é outro item de interesse do anunciante.',
            'Ao manifestar interesse, prepare-se para dizer o que você oferece em troca.',
            'Os dois lados conferem os itens pessoalmente antes de concluir a troca.',
        ];
    }

    protected function getPlaceholder(): string
    {
        return 'assets/img/placeholder-troca.svg';
    }

    public function getRotuloConclusao(): string
    {
        return 'Marcar como trocado';
    }
}
