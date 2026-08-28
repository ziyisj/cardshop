<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CardKey;
use App\Models\Product;
use Illuminate\Http\Request;

class CardKeyController extends Controller
{
    public function index(Request $request)
    {
        $query = CardKey::with(['product', 'user']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($pid = $request->query('product_id')) {
            $query->where('product_id', $pid);
        }

        $cards = $query->latest()->paginate(30)->withQueryString();
        $products = Product::orderBy('name')->get();

        return view('admin.cards.index', compact('cards', 'products'));
    }

    /** 批量生成卡密 */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'count'         => 'required|integer|min:1|max:5000',
            'duration_days' => 'nullable|integer|min:1',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $days = $data['duration_days'] ?? $product->duration_days;

        $rows = [];
        $now = now();
        for ($i = 0; $i < $data['count']; $i++) {
            $rows[] = [
                'code'          => CardKey::generateCode(),
                'product_id'    => $product->id,
                'duration_days' => $days,
                'status'        => 'unused',
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        CardKey::insert($rows);

        return back()->with('ok', "已生成 {$data['count']} 张卡密");
    }

    /** 作废单张卡密 */
    public function disable(CardKey $card)
    {
        if ($card->status === 'unused' || $card->status === 'sold') {
            $card->update(['status' => 'disabled']);
        }
        return back()->with('ok', '卡密已作废');
    }

    public function destroy(CardKey $card)
    {
        $card->delete();
        return back()->with('ok', '卡密已删除');
    }

    /** 导出未使用卡密为纯文本 */
    public function export(Request $request)
    {
        $codes = CardKey::where('status', 'unused')
            ->when($request->query('product_id'), fn ($q, $v) => $q->where('product_id', $v))
            ->pluck('code')
            ->implode("\n");

        return response($codes, 200, [
            'Content-Type'        => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cards.txt"',
        ]);
    }
}
