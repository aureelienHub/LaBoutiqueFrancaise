<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session")
     */
    public function index(EntityManagerInterface $entityManager,Cart $cart, $reference): Response
    {
        Stripe::setApiKey('sk_test_51Jx8gYAO7IIu0NIx85tCS3Tm2DTw0abOKoolJJUeZHHUrs0ZEYqWHCIKkvL8V3xiUAJsdGXBj1XvougbumMdkptL006M6YJNJG');

        $productsForStripe = [];

        $YOUR_DOMAIN = 'http://127.0.0.1:8000/';

        $order = $entityManager->getRepository(Order::class)->findOneBy(array('reference' => $reference));

        if (!$order) {
            new JsonResponse(['error' => 'order']);
        }

        foreach ($cart->getFull() as $product) {

            $stripe = new StripeClient('sk_test_51Jx8gYAO7IIu0NIx85tCS3Tm2DTw0abOKoolJJUeZHHUrs0ZEYqWHCIKkvL8V3xiUAJsdGXBj1XvougbumMdkptL006M6YJNJG');

                $stripe->products->create(['name' => $product['product']->getName()]);

                $idPrice = $stripe->products->create(['name' => $product['product']->getName()])->id;
                
                $price = \Stripe\Price::create([
                    'product' =>  $idPrice,
                    'unit_amount' => $product['product']->getPrice(),
                    'currency' => 'eur',
                  ]);
                
                $productsForStripe[] = [

                    'price' => $price,

                    'quantity' => $product['quantity'],
                ];
        }
        
        $stripe = new StripeClient('sk_test_51Jx8gYAO7IIu0NIx85tCS3Tm2DTw0abOKoolJJUeZHHUrs0ZEYqWHCIKkvL8V3xiUAJsdGXBj1XvougbumMdkptL006M6YJNJG');

                $stripe->products->create(['name' => $order->getCarrierName()]);

                $idPriceCarrier = $stripe->products->create(['name' => $order->getCarrierName()])->id;

                $price = \Stripe\Price::create([
                    'product' =>  $idPriceCarrier,
                    'unit_amount' => $order->getCarrierPrice() * 100,
                    'currency' => 'eur',
                  ]);
                
                $productsForStripe[] = [

                    'price' => $price,

                    'quantity' => $product['quantity'],

                ];

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'line_items' => [
                $productsForStripe
            ],

            'mode' => 'payment',

            'success_url' => $YOUR_DOMAIN . '/success.html',

            'cancel_url' => $YOUR_DOMAIN . '/cancel.html',

        ]);

        // header("HTTP/1.1 303 See Other");

        // header("Location: " . $checkout_session->url);

        return $this->redirect($checkout_session->url);
    }
}
