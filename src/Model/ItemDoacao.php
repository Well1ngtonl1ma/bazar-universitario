<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Item oferecido GRATUITAMENTE.
 * Herda tudo de Item e só define o que é específico de uma doação.
 */
class ItemDoacao extends Item
{
    /** Sobrescreve o atributo herdado: todo objeto desta classe é uma doação. */
    protected string $tipo = 'doacao';

    public function getBadgeTipo(): array
    {
        return [
            'rotulo' => 'Doação',
            'classe' => 'badge-ouro',   // amarelo ouro
            'icone'  => 'bi-gift',
        ];
    }

    public function getMensagemAcao(): string
    {
        return 'Tenho interesse em receber';
    }

    public function getDescricaoTipo(): string
    {
        return 'Este item está sendo doado: quem receber não paga nada.';
    }

    public function getRegras(): array
    {
        return [
            'O item é gratuito: nenhuma forma de pagamento pode ser cobrada.',
            'O doador escolhe quem recebe entre os interessados.',
            'A retirada é combinada por e-mail, de preferência no campus.',
        ];
    }

    protected function getPlaceholder(): string
    {
        return 'assets/img/placeholder-doacao.svg';
    }

    public function getRotuloConclusao(): string
    {
        return 'Marcar como doado';
    }
}
