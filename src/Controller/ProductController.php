<?php

declare(strict_types=1);

namespace App\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Resource\ResourceActions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\OrderItemRepository;
use App\Entity\Order\OrderItem;

class ProductController extends ResourceController
{
    public function showAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, ResourceActions::SHOW);
        $product = $this->findOr404($configuration);

        $productId = $product->getId();

        $cartContext = $this->get('sylius.context.cart');
        $cart = $cartContext->getCart();

        // some custom provider service to retrieve all reserved products
        $orderItemRepository = $this->get('app.repository.order_item');
        $itemsForProduct = $orderItemRepository->findByProduct($product, $cart->getId());

        // Get the product's stock
        $productVariant = $product->getVariants()->first();
        $productStock = $productVariant ? $productVariant->getOnHand() : 0;

        $this->eventDispatcher->dispatch(ResourceActions::SHOW, $configuration, $product);

        // Create the inventory calendar
        $inventoryCalendar = $this->createInventoryCalendar($itemsForProduct, $productStock);

        if ($configuration->isHtmlRequest()) {
            return $this->render($configuration->getTemplate(ResourceActions::SHOW . '.html'), [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'resource' => $product,
                'itemsForProduct' => $itemsForProduct,
                'inventoryCalendar' => json_encode($inventoryCalendar),
                'id' => $productId,
                $this->metadata->getName() => $product,
                'productStock' => $productStock,
            ]);
        }

        return $this->createRestView($configuration, $product);
    }

    private function createInventoryCalendar($itemsForProduct, $initialStock): array
    {
        $inventoryCalendar = [];
        $today = new \DateTime();
        $HundredMonthsLater = (clone $today)->modify('+100 days');

        // Initiate the inventory calendar with the initial stock
        $period = new \DatePeriod($today, new \DateInterval('P1D'), $HundredMonthsLater);
        foreach ($period as $date) {
            $inventoryCalendar[$date->format('Y-m-d')] = $initialStock;
        }

        // Update the inventory calendar based on reservations
        foreach ($itemsForProduct as $item) {
            foreach ($item->getDaysReservation() as $dateString) {
                $date = new \DateTime($dateString);
                $formattedDate = $date->format('Y-m-d');
                if (isset($inventoryCalendar[$formattedDate])) {
                    $inventoryCalendar[$formattedDate] -= $item->getQuantity();
                }
            }
        }

        return $inventoryCalendar;
    }
}
