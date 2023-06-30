<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use App\Repository\OrderItemRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\RouterInterface;

class VerificationListener
{
    private $orderItemRepository;
    private $requestStack;
    private $router;

    public function __construct(OrderItemRepository $orderItemRepository, RequestStack $requestStack, RouterInterface $router)
    {
        $this->orderItemRepository = $orderItemRepository;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onOrderUpdate(GenericEvent $event)
    {
        $order = $event->getSubject();
        $items = $order->getItems();
        $productsDateQuantityArray = [];
        $productStocks = [];

        $products = []; // New array to store the products

        $newReservationDates = [];

        foreach ($items as $item) {
            $product = $item->getProduct();
            $productId = $product->getId();
            $quantity = $item->getQuantity();
            $days = $item->getDaysReservation();
            $products[$productId] = $product; // Store the product in the array

            foreach ($days as $day) {
                if (array_key_exists($productId, $productsDateQuantityArray) && array_key_exists($day, $productsDateQuantityArray[$productId])) {
                    $productsDateQuantityArray[$productId][$day] += $quantity;
                } else {
                    $productsDateQuantityArray[$productId][$day] = $quantity;
                }
            }

            // Get the product's stock
            $productVariant = $product->getVariants()->first();
            $productStocks[$productId] = $productVariant ? $productVariant->getOnHand() : 0;

            $itemsForProduct = $this->orderItemRepository->findByProduct($product, $item->getId());
            dump($itemsForProduct);
            $newReservationDates = array_merge($newReservationDates, $item->getDaysReservation());
        }
        foreach ($itemsForProduct as $productItem) {
            $productId = $productItem->getProduct()->getId();
            $daysReservations = $productItem->getDaysReservation();
            //dump($daysReservations);
            foreach ($daysReservations as $dateString) {
                if (!isset($productsDateQuantityArray[$productId][$dateString])) {
                    $productsDateQuantityArray[$productId][$dateString] = $productItem->getQuantity();
                    //dump($productsDateQuantityArray);
                } else {
                    $productsDateQuantityArray[$productId][$dateString] += $productItem->getQuantity();
                    //dump($productsDateQuantityArray);
                }
            }
        }

        // Sort and verify the product stock
        $errors = [];
        foreach ($productsDateQuantityArray as $productId => $dateQuantityArray) {
            ksort($dateQuantityArray);
            dump($productsDateQuantityArray);
            dump($productId);
            dump($productStocks[$productId]);
            dump($dateQuantityArray);
            dump($newReservationDates);
            foreach ($dateQuantityArray as $date => $quantity) {
                // Si la date ne fait pas partie de la nouvelle réservation, passez à l'itération suivante
                if (!in_array($date, $newReservationDates)) {
                    continue;
                }
                if ($quantity > $productStocks[$productId]) {
                    $product = $products[$productId]; // Fetch the correct product using the productId
                    // Ajoutez l'erreur au tableau au lieu de lancer une exception
                    $errors[] = "Réservation impossible pour le produit id: " . $productId . " : " . $product . " sur la date " . $date;
                    //dump($dateQuantityArray);
                    //throw new HttpException(Response::HTTP_BAD_REQUEST, "Réservation impossible pour le produit id: " . $productId . " sur la date " . $date);
                    //$this->requestStack->getSession()->getFlashBag()->add('error', "Réservation impossible pour le produit id: " . $productId . " sur la date " . $date);
                    //echo 'hop hop hop du calme';die();
                    // Redirect to cart page
                    //$cartUrl = $this->router->generate('sylius_shop_cart_summary'); // change 'cart_route' with your actual cart route name
                    //return new RedirectResponse($cartUrl);
                    dump($errors);
                }
            }
            // S'il y a des erreurs, lancez une exception avec toutes les erreurs
            if (!empty($errors)) {
                throw new HttpException(Response::HTTP_FORBIDDEN, implode(' | ', $errors));
            }
        }
        //exit();
    }
}
