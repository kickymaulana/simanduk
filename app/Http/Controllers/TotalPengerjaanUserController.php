<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PengerjaanProduk;
use App\Models\Departemen;
use Illuminate\Support\Facades\DB;

class TotalPengerjaanUserController extends Controller
{
    public function index(Request $request)
    {
        $isPrint = $request->has('print') && $request->input('print') !== 'false' && $request->input('print') !== '';

        $query = PengerjaanProduk::query()
            ->with(['user', 'user.departemen'])
            ->select(
                'user_id',
                DB::raw('count(*) as total_pengerjaan'),
                DB::raw("COUNT(IF(status_kondisi = 'OK', 1, NULL)) as total_ok"),
                DB::raw("COUNT(IF(status_kondisi = 'In Proses', 1, NULL)) as total_proses"),
                DB::raw("COUNT(IF(status_kondisi = 'Buang', 1, NULL)) as total_buang")
            )

            ->when($request->date_start && $request->date_end, function ($query) use ($request) {
                $query->whereBetween('created_at', [$request->date_start, $request->date_end]);
            })

            ->when($request->search, function ($query, $search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })

            ->when($request->departemen_id, function ($query, $id) {
                $query->whereHas('user', function ($q) use ($id) {
                    $q->where('departemen_id', $id);
                });
            })
            ->groupBy('user_id')
            ->orderBy('total_pengerjaan', 'desc');

        if ($isPrint) {
            $rekap = $query->get()->map(fn ($item) => [
                'user' => [
                    'id' => $item->user->id,
                    'name' => $item->user->name,
                    'departemen' => $item->user?->departemen?->departemen,
                ],
                'total_pengerjaan' => $item->total_pengerjaan,
                'total_ok' => $item->total_ok,
                'total_proses' => $item->total_proses,
                'total_buang' => $item->total_buang,
            ])->values();
        } else {
            $rekap = $query->paginate(15)
                ->withQueryString()
                ->through(fn ($item) => [
                    'user' => [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'departemen' => $item->user?->departemen?->departemen,
                    ],
                    'total_pengerjaan' => $item->total_pengerjaan,
                    'total_ok' => $item->total_ok,
                    'total_proses' => $item->total_proses,
                    'total_buang' => $item->total_buang,
                ]);
        }

        return Inertia::render('TotalPengerjaan/Index', [
            'rekap' => $rekap,
            'departemens' => Departemen::orderBy('departemen', 'asc')->get(),
            'filters' => $request->only(['search', 'date_start', 'date_end', 'departemen_id']),
            'isPrint' => $isPrint,
        ]);
    }
}