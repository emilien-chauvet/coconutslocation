<?php

declare(strict_types=1);

namespace App\Repository;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderItemRepository as BaseOrderItemRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Order\OrderItem;

class OrderItemRepository extends BaseOrderItemRepository
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(OrderItem::class));
    }

    public function findByProduct(ProductInterface $product, $orderId): array
    {
        // Find the items with "new" and "fulfilled" status
        $items = $this->createQueryBuilder('o')
            ->innerJoin('o.variant', 'v')
            ->innerJoin('o.order', 'ord')
            ->where('v.product = :product')
            ->andWhere('ord.state IN (:states)')
            ->setParameter('product', $product)
            ->setParameter('states', ['new', 'fulfilled'])
            ->getQuery()
            ->getResult();

        // Find the items with "cart" status for the specific order
        $cartItems = $this->createQueryBuilder('o')
            ->innerJoin('o.variant', 'v')
            ->innerJoin('o.order', 'ord')
            ->where('v.product = :product')
            ->andWhere('ord.id = :order')
            ->andWhere('ord.state = :state')
            ->setParameter('product', $product)
            ->setParameter('order', $orderId)
            ->setParameter('state', 'cart')
            ->getQuery()
            ->getResult();

        // Merge the two arrays
        $allItems = array_merge($items, $cartItems);

        return $allItems;
    }
}

