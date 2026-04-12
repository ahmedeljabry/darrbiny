<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\AppExpensesExport;
use App\Models\AppExpense;
use App\Models\Payment;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class AppExpensesController extends BaseController
{
    public function index(Request $request)
    {
        $filters = [
            'type' => $this->resolveType($request->query('type')),
        ];

        $query = $this->expensesQuery($filters);

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new AppExpensesExport((clone $query)->latest()->get()),
                'app-expenses-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $expenses = (clone $query)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $grossProfitMinor = $this->grossProfitMinor();
        $totalExpensesMinor = (int) AppExpense::query()->sum('amount_minor');
        $netProfitMinor = $grossProfitMinor - $totalExpensesMinor;
        $categoryTotalsMinor = AppExpense::query()
            ->selectRaw('type, COALESCE(SUM(amount_minor), 0) as total_minor')
            ->groupBy('type')
            ->pluck('total_minor', 'type');
        $typeOptions = AppExpense::typeLabels();

        return view('admin.app-expenses.index', compact(
            'expenses',
            'filters',
            'grossProfitMinor',
            'totalExpensesMinor',
            'netProfitMinor',
            'categoryTotalsMinor',
            'typeOptions'
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);

        AppExpense::query()->create([
            'type' => $data['type'],
            'amount_minor' => WalletAmount::majorToMinor($data['amount']),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'تمت إضافة مصروف التطبيق بنجاح');
    }

    public function update(Request $request, string $id)
    {
        $expense = AppExpense::query()->findOrFail($id);
        $data = $this->validatedPayload($request);

        $expense->update([
            'type' => $data['type'],
            'amount_minor' => WalletAmount::majorToMinor($data['amount']),
            'notes' => $data['notes'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'تم تحديث مصروف التطبيق بنجاح');
    }

    public function destroy(string $id)
    {
        $expense = AppExpense::query()->findOrFail($id);
        $expense->delete();

        return back()->with('status', 'تم حذف مصروف التطبيق بنجاح');
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(AppExpense::typeLabels()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function resolveType(null|string|int $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return array_key_exists($value, AppExpense::typeLabels()) ? $value : null;
    }

    private function grossProfitMinor(): int
    {
        $retainedFeesMinor = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->whereIn('type', [
                Payment::TYPE_RESERVATION_FEE,
                Payment::TYPE_PLAN_PARTIAL,
            ])
            ->sum('amount_minor');

        $fullPaymentAppFeesMinor = (int) Payment::query()
            ->where('status', Payment::STATUS_SUCCEEDED)
            ->where('type', Payment::TYPE_PLAN_FULL)
            ->sum('app_fee_minor');

        return $retainedFeesMinor + $fullPaymentAppFeesMinor;
    }

    private function expensesQuery(array $filters)
    {
        return AppExpense::query()
            ->with(['creator', 'updater'])
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type));
    }
}
