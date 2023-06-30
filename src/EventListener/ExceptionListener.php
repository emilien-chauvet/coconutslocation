<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\RouterInterface;

class ExceptionListener
{
    private $router;
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpException && str_starts_with($exception->getMessage(), "Réservation impossible")) {
            $url = $this->router->generate('sylius_shop_cart_summary'); // Assume 'cart_page' is the route to your cart page

            // Get the session from the current request and add the exception message to flash messages
            $session = $this->requestStack->getCurrentRequest()->getSession();
            $errors = explode(' | ', $exception->getMessage());
            foreach ($errors as $error) {
                $session->getFlashBag()->add('error', $error);
            }


            $response = new RedirectResponse($url);

            $event->setResponse($response);
        }
    }
}
