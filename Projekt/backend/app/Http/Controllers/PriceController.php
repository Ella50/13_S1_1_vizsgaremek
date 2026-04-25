<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PriceController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = Price::query();
            
            if ($request->has('userType') && $request->userType !== '') {
                $query->where('userType', $request->userType);
            }
            
            if ($request->has('priceCategory') && $request->priceCategory !== '') {
                $query->where('priceCategory', $request->priceCategory);
            }
            
            if ($request->has('active') && $request->active === 'true') {
                $query->active();
            }
            
            $prices = $query->orderBy('userType')
                ->orderBy('priceCategory')
                ->orderBy('validFrom', 'desc')
                ->get();
            
            // Alakítsuk át a dátumokat string formátumba
            $prices->transform(function ($price) {
                return [
                    'id' => $price->id,
                    'userType' => $price->userType,
                    'priceCategory' => $price->priceCategory,
                    'amount' => $price->amount,
                    'validFrom' => $price->validFrom ? $price->validFrom->format('Y-m-d') : null,
                    'validTo' => $price->validTo ? $price->validTo->format('Y-m-d') : null,
                    'formatted_amount' => $price->formatted_amount,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $prices
            ]);
            
        } catch (\Exception $e) {
            Log::error('Price index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az árak betöltése során'
            ], 500);
        }
    }
    

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userType' => 'required|in:Tanuló,Tanár,Admin,Konyha,Dolgozó,Külsős',
                'priceCategory' => 'required|in:Normál,Kedvezményes',
                'amount' => 'required|numeric|min:0|max:1000000',
                'validFrom' => 'required|date',
                'validTo' => 'nullable|date|after_or_equal:validFrom'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $price = Price::create($request->all());
            
            Log::info('New price created', [
                'price_id' => $price->id,
                'userType' => $price->userType,
                'amount' => $price->amount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Ár sikeresen létrehozva',
                'data' => $price
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Price store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ár létrehozása során: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function show($id)
    {
        try {
            $price = Price::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $price->id,
                    'userType' => $price->userType,
                    'priceCategory' => $price->priceCategory,
                    'amount' => $price->amount,
                    'validFrom' => $price->validFrom ? $price->validFrom->format('Y-m-d') : null,
                    'validTo' => $price->validTo ? $price->validTo->format('Y-m-d') : null,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Price show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Ár nem található'
            ], 404);
        }
    }
    

    public function update(Request $request, $id)
    {
        try {
            $price = Price::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'userType' => 'sometimes|required|in:Tanuló,Tanár,Admin,Konyha,Dolgozó,Külsős',
                'priceCategory' => 'sometimes|required|in:Normál,Kedvezményes',
                'amount' => 'sometimes|required|numeric|min:0|max:1000000',
                'validFrom' => 'sometimes|required|date',
                'validTo' => 'nullable|date|after_or_equal:validFrom'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validációs hiba',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $price->update($request->all());
            
            Log::info('Price updated', [
                'price_id' => $price->id,
                'userType' => $price->userType,
                'amount' => $price->amount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Ár sikeresen frissítve',
                'data' => $price
            ]);
            
        } catch (\Exception $e) {
            Log::error('Price update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ár frissítése során: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function destroy($id)
    {
        try {
            $price = Price::findOrFail($id);
            
            
            $hasOrders = $price->orders()->exists();
            
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ez az ár nem törölhető, mert már vannak hozzá kapcsolódó rendelések',
                    'code' => 'HAS_ORDERS'
                ], 400);
            }
            
            $price->delete();
            
            Log::info('Price deleted', ['price_id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Ár sikeresen törölve'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Price destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az ár törlése során: ' . $e->getMessage()
            ], 500);
        }
    }

    

    public function getActivePrices()
    {
        try {
            $prices = Price::active()->get();
            
            $grouped = [];
            foreach ($prices as $price) {
                $grouped[$price->userType][$price->priceCategory] = [
                    'id' => $price->id,
                    'amount' => $price->amount,
                    'formatted_amount' => $price->formatted_amount
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $grouped,
                'list' => $prices
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get active prices error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Hiba történt az aktív árak betöltése során'
            ], 500);
        }
    }
    
    
}