<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use JsonException;
use RuntimeException;

class ProductBalanceDraftService
{
    /**
     * Tempo de vida do rascunho no Redis.
     *
     * 7200 segundos = 2 horas.
     * Toda vez que o draft e salvo novamente, esse tempo e renovado.
     */
    private int $ttlSeconds = 7200;

    /**
     * Busca o rascunho atual do balanco no Redis.
     *
     * O Redis devolve uma string ou null. Por isso precisamos transformar
     * o JSON salvo em array PHP usando json_decode(..., true).
     *
     * Se a chave nao existir ou se o JSON estiver invalido, retornamos
     * sempre a mesma estrutura vazia para simplificar o restante do fluxo.
     */
    public function getDraft(int $businessId, int $balanceId): array
    {
        $draft = Redis::get($this->draftKey($businessId, $balanceId));

        if (! $draft) {
            return $this->emptyDraft();
        }

        try {
            $decodedDraft = json_decode($draft, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->emptyDraft();
        }

        if (! is_array($decodedDraft)) {
            return $this->emptyDraft();
        }

        return [
            'items' => $decodedDraft['items'] ?? [],
            'logs' => $decodedDraft['logs'] ?? [],
        ];
    }

    /**
     * Adiciona um item ao rascunho ou soma quantidade se ele ja existir.
     *
     * A identidade do item no draft e:
     * business_id + product_id + variation_id.
     *
     * O metodo usa Redis Lock para impedir que duas alteracoes simultaneas
     * leiam o mesmo draft antigo e sobrescrevam uma a outra.
     */
    public function addItem(
        int $businessId,
        int $balanceId,
        int $productId,
        int $variationId,
        float $quantity,
        int $userId
    ): array {
        if ($quantity <= 0) {
            throw new RuntimeException('A quantidade deve ser maior que zero.');
        }

        $product = Product::query()
            ->where('business_id', $businessId)
            ->where('id', $productId)
            ->where('variation_id', $variationId)
            ->first();

        if (! $product) {
            throw new RuntimeException('Produto nao encontrado para esta empresa e variacao.');
        }

        $lock = Cache::lock($this->lockKey($businessId, $balanceId), 10);

        return $lock->block(5, function () use (
            $businessId,
            $balanceId,
            $product,
            $productId,
            $variationId,
            $quantity,
            $userId
        ) {
            $draft = $this->getDraft($businessId, $balanceId);

            $itemIndex = $this->findItemIndex(
                $draft['items'],
                $businessId,
                $productId,
                $variationId
            );

            if ($itemIndex === null) {
                $draft['items'][] = [
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'updated_by' => $userId,
                    'updated_at' => now()->toDateTimeString(),
                ];
            } else {
                $draft['items'][$itemIndex]['quantity'] += $quantity;
                $draft['items'][$itemIndex]['updated_by'] = $userId;
                $draft['items'][$itemIndex]['updated_at'] = now()->toDateTimeString();
            }

            $draft['logs'][] = $this->makeLog(
                businessId: $businessId,
                productId: $productId,
                variationId: $variationId,
                action: 'added',
                quantity: $quantity,
                userId: $userId
            );

            $this->saveDraft($businessId, $balanceId, $draft);

            return $draft;
        });
    }

    /**
     * Remove um item do rascunho no Redis.
     *
     * A remocao tambem usa a chave composta:
     * business_id + product_id + variation_id.
     *
     * Se o item nao existir, o metodo apenas retorna o draft atual sem erro.
     */
    public function removeItem(
        int $businessId,
        int $balanceId,
        int $productId,
        int $variationId,
        int $userId
    ): array {
        $lock = Cache::lock($this->lockKey($businessId, $balanceId), 10);

        return $lock->block(5, function () use (
            $businessId,
            $balanceId,
            $productId,
            $variationId,
            $userId
        ) {
            $draft = $this->getDraft($businessId, $balanceId);

            $itemIndex = $this->findItemIndex(
                $draft['items'],
                $businessId,
                $productId,
                $variationId
            );

            if ($itemIndex === null) {
                return $draft;
            }

            $removedItem = $draft['items'][$itemIndex];

            unset($draft['items'][$itemIndex]);

            // Reorganiza os indices do array para 0, 1, 2...
            $draft['items'] = array_values($draft['items']);

            $draft['logs'][] = $this->makeLog(
                businessId: $businessId,
                productId: $productId,
                variationId: $variationId,
                action: 'removed',
                quantity: (float) $removedItem['quantity'],
                userId: $userId
            );

            $this->saveDraft($businessId, $balanceId, $draft);

            return $draft;
        });
    }

    /**
     * Monta a chave onde o draft sera salvo no Redis.
     *
     * Exemplo: product_balance:1:10
     */
    private function draftKey(int $businessId, int $balanceId): string
    {
        return "product_balance:{$businessId}:{$balanceId}";
    }

    /**
     * Monta a chave de lock para o draft.
     *
     * Exemplo: lock:product_balance:1:10
     */
    private function lockKey(int $businessId, int $balanceId): string
    {
        return "lock:product_balance:{$businessId}:{$balanceId}";
    }

    /**
     * Estrutura padrao de um draft vazio.
     *
     * Assim o restante da aplicacao sempre trabalha com items e logs,
     * sem precisar tratar null em todo lugar.
     */
    private function emptyDraft(): array
    {
        return [
            'items' => [],
            'logs' => [],
        ];
    }

    /**
     * Salva o draft no Redis e renova o TTL.
     *
     * O array PHP vira JSON porque o Redis armazena esse valor como string.
     */
    private function saveDraft(int $businessId, int $balanceId, array $draft): void
    {
        Redis::setex(
            $this->draftKey($businessId, $balanceId),
            $this->ttlSeconds,
            json_encode($draft, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Procura um item dentro do array items do draft.
     *
     * Retorna o indice do item se encontrar.
     * Retorna null se nao encontrar.
     */
    private function findItemIndex(
        array $items,
        int $businessId,
        int $productId,
        int $variationId
    ): ?int {
        foreach ($items as $index => $item) {
            if (
                (int) $item['business_id'] === $businessId &&
                (int) $item['product_id'] === $productId &&
                (int) $item['variation_id'] === $variationId
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Cria um log temporario dentro do draft.
     *
     * Esse log ainda nao vai para o MySQL. Ele fica no Redis e sera
     * persistido definitivamente apenas quando salvarmos o balanco.
     */
    private function makeLog(
        int $businessId,
        int $productId,
        int $variationId,
        string $action,
        float $quantity,
        int $userId
    ): array {
        return [
            'business_id' => $businessId,
            'product_id' => $productId,
            'variation_id' => $variationId,
            'action' => $action,
            'quantity' => $quantity,
            'created_by' => $userId,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
