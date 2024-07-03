<?php

namespace App\Http\Controllers;

use App\Models\PaymentConfiguration;
use App\Models\PaymentTransaction;
use App\Repositories\PaymentTransaction\PaymentTransactionInterface;
use App\Services\CachingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class SubscriptionWebhookController extends Controller
{
    //
    private CachingService $cache;
    private PaymentTransactionInterface $paymentTransaction;

    public function __construct(CachingService $cachingService, PaymentTransactionInterface $paymentTransaction)
    {
        $this->cache = $cachingService;
        $this->paymentTransaction = $paymentTransaction;
    }

    public function stripe(Request $request)
    {

        $systemSettings = PaymentConfiguration::where('school_id',NULL)->first();
        $endpoint_secret = $systemSettings->webhook_secret_key;
        
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        
        // try {
        //     $event = \Stripe\Event::constructFrom(
        //         json_decode($payload, true)
        //     );
        // } catch(\UnexpectedValueException $e) {
        //     // Invalid payload
        //     http_response_code(400);
        //     exit();
        // }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            Log::error("Signature Verified");
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            Log::error("Payload Mismatch");
            http_response_code(400);
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error("Signature Verification Failed");
            http_response_code(400);
            exit();
        }
        
        $transaction_id = $event->data->object->id;
        $paymentTransaction = PaymentTransaction::where('order_id',$transaction_id)->first();
        $transaction_id = $paymentTransaction->id;

        switch ($event->type) {
            case 'payment_intent.succeeded':
                Log::error($transaction_id);
                $paymentTransactionData = $this->paymentTransaction->findById($transaction_id);
                if (!empty($paymentTransactionData)) {
                    if ($paymentTransactionData->status != 1) {
                        $school_id = $paymentTransactionData->school_id;

                        $this->paymentTransaction->update($transaction_id,['payment_status' => "succeed",'school_id' => $school_id]);
                        Log::error("Payment Success");
                    }else{
                        Log::error("Transaction Already Successes --->");
                        break;
                    }
                }else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }
                http_response_code(200);
                break;

            case 'payment_intent.payment_failed':
                $paymentTransactionData = $this->paymentTransaction->findById($transaction_id);
                if (!empty($paymentTransactionData)) {
                    if ($paymentTransactionData->status != 1) {
                        $school_id = $paymentTransactionData->school_id;

                        $this->paymentTransaction->update($transaction_id,['payment_status' => "failed",'school_id' => $school_id]);
                        http_response_code(400);
                        break;
                    }
                }else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }
            case 'charge.succeeded':
                Log::error($transaction_id);
                $paymentTransactionData = $this->paymentTransaction->findById($transaction_id);
                if (!empty($paymentTransactionData)) {
                    if ($paymentTransactionData->status != 1) {
                        $school_id = $paymentTransactionData->school_id;

                        $this->paymentTransaction->update($transaction_id,['payment_status' => "succeed",'school_id' => $school_id]);
                    }else{
                        Log::error("Transaction Already Successes --->");
                        break;
                    }
                }else {
                    Log::error("Payment Transaction id not found --->");
                    break;
                }
                http_response_code(200);
                break;
            default:
                // Unexpected event type
                Log::error('Received unknown event type');
        }

        // End Stripe
    }
}
