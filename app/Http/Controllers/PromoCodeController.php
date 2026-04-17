<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PromoCode;
use Auth;
class PromoCodeController extends Controller
{
    public function verifyPromo(Request $request)
    {

        if (Auth::user()->promo_used == 1) {

            return response()->json(['message' => "You already have used your promo code.", "error_code" => "promo_code_already_used"], 400);
        }

        $data = PromoCode::where('promo_code', $request->promo_code)->where('status','!=', 0)->first();

         if ($data->status == 2) {

            return response()->json(['message' => "Promo code already used ".$data->status, "error_code" => "promo_code_already_used"], 400);
        }

        if (!$data) {
            return response()->json(['message' => "Promo code not available", "error_code" => "invalid_promo_code"], 400);
        } else {

            return response()->json(['message' => "Promo code verified", 'data' => $data]);

        }

    }

    public function index()
    {
        return response()->json(PromoCode::with(['generatedBy'])->latest()->get());
    }

    // POST /promo-codes
    public function store(Request $request)
    {
        
        $request->validate([
            'status' => 'integer',
            'plan_id' => 'nullable|integer',
            'duration_months' => 'integer',

            'is_bb_promo' => 'boolean',
            'bb_user_email' => 'nullable|email',
            'bb_user_id' => 'nullable|integer',
        ]);

        // generate unique promo code
        $promoCode = $this->generatePromoCode();

        $promo = PromoCode::create([
            'promo_code' => $promoCode,
            'status' => $request->status ?? 1,
            'plan_id' => $request->plan_id,
            'duration_months' => $request->duration_months,
           // 'is_bb_promo' => $request->is_bb_promo ?? false,
           // 'bb_user_email' => $request->bb_user_email,
           // 'bb_user_id' => $request->bb_user_id,
        ]);

        return response()->json([
            'message' => 'Promo code created successfully',
            'data' => $promo
        ]);
    }


    public function requestPromoCode(Request $request)
    {
        
        $request->validate([
            'status' => 'integer',
            'plan_id' => 'nullable|integer',
            'duration_months' => 'integer',

            'is_bb_promo' => 'boolean',
            'bb_user_email' => 'nullable|email',
            'bb_user_id' => 'nullable|integer',
        ]);

        $user = $request->api_user;

        // generate unique promo code
        $promoCode = $this->generatePromoCode();

        $promo = PromoCode::create([
            'promo_code' => $promoCode,
            'status' => $request->status ?? 1,
            'plan_id' => $request->plan_id,
            'duration_months' => $request->duration_months,
            'is_bb_promo' => $request->is_bb_promo ?? 0,
            'bb_user_email' => $request->bb_user_email ?? null,
            'bb_user_id' => $request->bb_user_id ?? null,
            'generated_by' => $user->id
        ]);

        return response()->json([
            'message' => 'Promo code created successfully',
            'data' => $promo
        ]);
    }


    private function generatePromoCode(): string
    {
        do {
            $code = strtoupper(
                substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5) . '-' .
                substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5) . '-' .
                substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5) . '-' .
                substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5)
            );
        } while (PromoCode::where('promo_code', $code)->exists());

        return $code;
    }

    // GET /promo-codes/{id}
    public function show($id)
    {
        $promo = PromoCode::findOrFail($id);

        return response()->json($promo);
    }

    // PUT /promo-codes/{id}
    public function update(Request $request, $id)
    {
        $promo = PromoCode::findOrFail($id);

        $request->validate([
            'promo_code' => 'nullable|string',
            'status' => 'integer',
            'plan_id' => 'nullable|integer',
            'duration_months' => 'integer',

            'is_bb_promo' => 'boolean',
            'bb_user_email' => 'nullable|email',
            'bb_user_id' => 'nullable|integer',
        ]);

        $promo->update($request->all());

        return response()->json([
            'message' => 'Promo code updated successfully',
            'data' => $promo
        ]);
    }

    // DELETE /promo-codes/{id}
    public function destroy($id)
    {
        $promo = PromoCode::findOrFail($id);
        $promo->delete();

        return response()->json([
            'message' => 'Promo code deleted successfully'
        ]);
    }
}
