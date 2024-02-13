<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Business;
use App\Variation;
use Carbon\Carbon;
use App\OnlineOrder;
use App\OnlineOrderLine;
use App\BusinessLocation;
use App\ProductVariation;
use App\Utils\ProductUtil;
use App\Utils\AddressUtill;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OnlineOrderController extends Controller
{
    protected $business_id;
    protected $location_id;
    
    protected $addressUtill;

    protected $transactionUtil;

    protected $productUtil;

    public function __construct(
        AddressUtill $addressUtill,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil
    ) {
        $this->business_id = env('BUSINESS_ID', 1);
        $this->location_id = env('LOCATION_ID', 1);

        $this->addressUtill = $addressUtill;
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 10;

        $orders = OnlineOrder::where('created_by', Auth::id())
            ->paginate($limit);

        $orders->transform(function($order) {
            return $this->orderResponse($order);
        });

        return response()->json($orders, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {        
        try {
            $user = Auth::user();
            $contact = $user->contact;

            $business_id = $this->business_id;
            $location_id = $this->location_id;

            $input = $request->only(['products', 'addresses']);
            $input['customer_id'] = $contact->id;

            //check if all stocks are available
            $variation_ids = [];
            foreach ($input['products'] as $product_data) {
                $variation_ids[] = $product_data['variation_id'];
            }

            $variations_details = $this->getVariationsDetails($business_id, $location_id, $variation_ids);
            $is_valid = true;
            $error_messages = [];
            $sell_lines = [];
            $final_total = 0;
            foreach ($variations_details as $variation_details) {
                if ($variation_details->product->enable_stock == 1) {
                    if (empty($variation_details->variation_location_details[0]) || $variation_details->variation_location_details[0]->qty_available < $input['products'][$variation_details->id]['quantity']) {
                        $is_valid = false;
                        $error_messages[] = 'Only '.$variation_details->variation_location_details[0]->qty_available.' '.$variation_details->product->unit->short_name.' of '.$variation_details->full_name.' available';
                    }
                }

                //Create product line array
                $sell_lines[] = [
                    'product_id' => $variation_details->product->id,
                    'unit_price_before_discount' => $variation_details->sell_price_inc_tax,
                    'unit_price' => $variation_details->sell_price_inc_tax,
                    'unit_price_inc_tax' => $variation_details->sell_price_inc_tax,
                    'variation_id' => $variation_details->id,
                    'quantity' => $input['products'][$variation_details->id]['quantity'],
                    'item_tax' => 0,
                    'enable_stock' => $variation_details->product->enable_stock,
                    'tax_id' => null,
                ];

                $final_total += ($input['products'][$variation_details->id]['quantity'] * $variation_details->sell_price_inc_tax);
            }

            if (! $is_valid) {
                return $this->respond([
                    'success' => false,
                    'error_messages' => $error_messages,
                ]);
            }

            $business = Business::find($business_id);
            $user_id = $business->owner_id;

            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $location_id,
            ];

            $customer = Contact::where('business_id', $business_id)
                            ->whereIn('type', ['customer', 'both'])
                            ->find($input['customer_id']);

            $order_data = [
                'type' => 'online_orders',
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $input['customer_id'],
                'final_total' => $final_total,
                'created_by' => $user_id,
                'status' => 'ordered',
                'payment_status' => 'due',
                'additional_notes' => '',
                'transaction_date' => \Carbon::now(),
                'customer_group_id' => $customer->customer_group_id,
                'tax_rate_id' => null,
                'sale_note' => null,
                'commission_agent' => null,
                'order_addresses' => json_encode($input['addresses']),
                'products' => $sell_lines,
                'is_created_from_api' => 1,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
                'shipping_status' => 'ordered',
                'shipping_charges' => $request->get('shipping_charges', 0),
                'is_direct_sale' => 1
            ];

            $invoice_total = [
                'total_before_tax' => $final_total,
                'tax' => 0,
            ];

            DB::beginTransaction();

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);

            //Create sell lines
            $this->transactionUtil->createOrUpdateSellLines($transaction, $order_data['products'], $order_data['location_id'], false, null, [], false);

            //update product stock
            foreach ($order_data['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $order_data['location_id'],
                        $product['quantity']
                    );
                }
            }

            // $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            //Auto send notification
            // $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);

            DB::commit();

            // $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id);

            $output = [
                'success' => 1,
                'transaction' => $transaction,
                // 'receipt' => $receipt,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'error_messages' => [$msg],
            ];
        }

        return $this->respond($output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = OnlineOrder::where('customer_id', Auth::id())
            ->where('id', $id)
            ->first();

        $data = $this->orderResponse($order);

        return response()->json($data, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = OnlineOrder::where('customer_id', Auth::id())
            ->where('id', $id)
            ->first();

        
    }

    public function cancel($id)
    {        
        $order = OnlineOrder::where('customer_id', Auth::id())
            ->where('id', $id)
            ->first();

        
    }

    public function orderResponse(OnlineOrder $order)
    {
        $order->items = $order->items;
        $order->billing_address = $order->billing_address;
        $order->shipping_address = $order->shipping_address;

        return $order;
    }

    private function getVariationsDetails($business_id, $location_id, $variation_ids)
    {
        $variation_details = Variation::whereIn('id', $variation_ids)
                            ->with([
                                'product' => function ($q) use ($business_id) {
                                    $q->where('business_id', $business_id);
                                },
                                'product.unit',
                                'variation_location_details' => function ($q) use ($location_id) {
                                    $q->where('location_id', $location_id);
                                },
                            ])->get();

        return $variation_details;
    }

}
