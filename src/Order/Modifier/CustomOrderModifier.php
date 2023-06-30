<?php

namespace App\Order\Modifier;

use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;

final class CustomOrderModifier implements OrderModifierInterface
{
    private $decoratedOrderModifier;
    private $orderItemQuantityModifier;

    public function __construct(
        OrderModifierInterface $decoratedOrderModifier,
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        OrderProcessorInterface $orderProcessor
    ) {
        $this->decoratedOrderModifier = $decoratedOrderModifier;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->orderProcessor = $orderProcessor;
    }

    public function addToOrder(OrderInterface $order, OrderItemInterface $item): void
    {
        if (!$this->resolveOrderItem($order, $item)) {
            $order->addItem($item);
        }
        // Ensuite, utilisez l'OrderProcessor pour recalculer les prix
        $this->orderProcessor->process($order);
    }

    public function removeFromOrder(OrderInterface $order, OrderItemInterface $item): void
    {
        $this->decoratedOrderModifier->removeFromOrder($order, $item);
    }

    private function resolveOrderItem(OrderInterface $cart, OrderItemInterface $item): bool
    {
        foreach ($cart->getItems() as $existingItem) {
            if ($item->equals($existingItem) && $item->getStartReservationDate() == $existingItem->getStartReservationDate() && $item->getEndReservationDate() == $existingItem->getEndReservationDate()) {
                $this->orderItemQuantityModifier->modify(
                    $existingItem,
                    $existingItem->getQuantity() + $item->getQuantity()
                );

                return true;
            }
        }

        return false;
    }

}
