<?php

namespace App\Http\Controllers;

use App\Variation;
use Carbon\Carbon;
use App\OnlineOrder;
use App\OnlineOrderLine;
use App\ProductVariation;
use App\Utils\AddressUtill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OnlineOrderController extends Controller
{
    protected $business_id;
    
    protected $addressUtill;

    public function __construct(AddressUtill $addressUtill)
    {
        $this->business_id = env('BUSINESS_ID', 1);
        $this->addressUtill = $addressUtill;
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
            DB::beginTransaction();

            $customet_id = Auth::id();

            // Create order
            $order = OnlineOrder::create([
                'business_id' => $this->business_id,
                'ordered_date' => Carbon::now(),
                'created_by' => $customet_id,
                'status' => 'draft'
            ]);
    
            $order_total_amount = 0;
            $orderTDAmount = 0;
            $final_total = 0;
    
            // Line Items
            $items = $request->input('items', []);
            foreach($items as $item) {
                $variation = Variation::find($item['variation_id']);
                if($variation) {
                    $price = $variation->sell_price_inc_tax;
                    $total_amount = $price * $item['qty'];
                    $order_total_amount += $total_amount;
        
                    OnlineOrderLine::create([
                        'online_order_id' => $order->id,
                        'product_id' => $variation->product_id,
                        'variation_id' => $variation->id,
                        'quantity' => $item['qty'],
                        'price' => $price,
                        'total_discount_amount' => 0,
                        'total_amount' => $total_amount
                    ]);
                }
            }
    
            $final_total = $order_total_amount - $orderTDAmount;
    
            $order->total_amount = $order_total_amount;
            $order->total_discount_amount = $orderTDAmount;
            $order->final_total = $final_total;
            $order->status = 'pending';
            $order->order_name = "#" . $order->id;
            $order->save();
            
    
            $billingData = $request->input('billing', []);
            $shippingData = $request->input('shipping', []);
            $use_for_shipping = $request->input('billing.use_for_shipping');
    
            if(isset($billingData['use_for_shipping'])) {
                unset($billingData['use_for_shipping']);
            }
    
            if($use_for_shipping) {
                $shippingData = $billingData;
                $shippingData['is_save_or_update'] = false;
            }
    
            // Order Billing Address
            $billingData['address_type'] = 'order_billing';
            $billingData['category'] = 'sender';
            $billingData['customer_id'] = $customet_id;
            $billingData['order_id'] = $order->id;
            $this->addressUtill->createOrder($billingData);
    
            // Order Shipping Address
            $shippingData['address_type'] = 'order_shipping';
            $shippingData['category'] = 'receiver';        
            $shippingData['customer_id'] = $customet_id;        
            $shippingData['order_id'] = $order->id;        
            $this->addressUtill->createOrder($shippingData);    

            DB::commit();
            
            $order = OnlineOrder::find($order->id);
            $data['order'] = $this->orderResponse($order);
            
            return $this->respondSuccess(null, $data);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return $this->respondWentWrong();
        }
        
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
}
