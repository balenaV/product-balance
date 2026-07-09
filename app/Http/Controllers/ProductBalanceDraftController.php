<?php

namespace App\Http\Controllers;

use App\Models\ProductBalance;
use App\Services\ProductBalanceDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ProductBalanceDraftController extends Controller
{
    public function __construct(
        private readonly ProductBalanceDraftService $draftService
    ) {
    }

    /**
     * Retorna o rascunho atual do balanco.
     *
     * Nesta fase ainda nao temos autenticacao/multiempresa real.
     * Por isso usamos business_id e user_id fixos como dados de laboratorio.
     */
    public function show(ProductBalance $balance): JsonResponse
    {
        $businessId = $this->currentBusinessId();

        $this->ensureBalanceBelongsToBusiness($balance, $businessId);

        return response()->json(
            $this->draftService->getDraft($businessId, $balance->id)
        );
    }

    /**
     * Adiciona um item ao draft ou soma quantidade se ele ja existir.
     *
     * O controller valida dados de entrada e delega a regra do Redis
     * para o ProductBalanceDraftService.
     */
    public function storeItem(Request $request, ProductBalance $balance): JsonResponse
    {
        $businessId = $this->currentBusinessId();
        $userId = $this->currentUserId();

        $this->ensureBalanceBelongsToBusiness($balance, $businessId);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'variation_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $draft = $this->draftService->addItem(
                businessId: $businessId,
                balanceId: $balance->id,
                productId: (int) $data['product_id'],
                variationId: (int) $data['variation_id'],
                quantity: (float) $data['quantity'],
                userId: $userId
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json($draft);
    }

    /**
     * Remove um item do draft usando product_id + variation_id.
     *
     * O business_id nao vem na URL porque ele vem do contexto do usuario.
     */
    public function destroyItem(
        ProductBalance $balance,
        int $product,
        int $variation
    ): JsonResponse {
        $businessId = $this->currentBusinessId();
        $userId = $this->currentUserId();

        $this->ensureBalanceBelongsToBusiness($balance, $businessId);

        $draft = $this->draftService->removeItem(
            businessId: $businessId,
            balanceId: $balance->id,
            productId: $product,
            variationId: $variation,
            userId: $userId
        );

        return response()->json($draft);
    }

    /**
     * Garante que o balanco acessado pertence a empresa atual.
     */
    private function ensureBalanceBelongsToBusiness(
        ProductBalance $balance,
        int $businessId
    ): void {
        abort_if((int) $balance->business_id !== $businessId, 404);
    }

    /**
     * Atalho temporario para simular a empresa atual.
     *
     * Depois, quando adicionarmos autenticacao, isso deve vir do usuario logado.
     */
    private function currentBusinessId(): int
    {
        return 1;
    }

    /**
     * Atalho temporario para simular o usuario atual.
     *
     * O seeder cria o usuario de ID 1 para usarmos durante o laboratorio.
     */
    private function currentUserId(): int
    {
        return 1;
    }
}
