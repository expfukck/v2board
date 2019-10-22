<?php

namespace App\Http\Controllers\Guest;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function stripeNotify (Request $request) {
        Log::info('stripeNotifyData: ' . json_encode($request->input()));

        \Stripe\Stripe::setApiKey(config('v2panel.stripe_sk_live'));
        try {
            $event = \Stripe\Webhook::constructEvent(
                file_get_contents('php://input'),
                $_SERVER['HTTP_STRIPE_SIGNATURE'],
                config('v2panel.stripe_webhook_key')
            );
        } catch (\Stripe\Error\SignatureVerification $e) {
            abort(400);
        }

        $obj = $event->data->object;
        if ($obj['status'] == 'succeeded') {
            $order = Order::where('callback_no', $obj['id'])->first();
            if (!$order) {
                die('ERROR');
            }
            if ($order->status !== 0) {
                die('SUCCESS');
            }
            $order->status = 1;
            if (!$order->save()) {
                die('ERROR');
            }
            die('SUCCESS');
        }
    }

    public function stripeReturn (Request $request) {
        header('Location:' . '/#/dashboard');
    }
}
