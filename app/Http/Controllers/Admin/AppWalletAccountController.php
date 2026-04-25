<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\AppWalletAccountExport;
use App\Models\AppWalletTransaction;
use App\Services\Admin\AppWalletAccountService;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class AppWalletAccountController extends BaseController
{
    public function __construct(
        private readonly AppWalletAccountService $service
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'search' => $this->parseSearch($request->query('search')),
            'direction' => $this->parseOption($request->query('direction'), ['in', 'out']),
            'source' => $this->parseOption($request->query('source'), array_keys($this->service->sourceOptions())),
            'from' => $this->parseDate($request->query('from')),
            'to' => $this->parseDate($request->query('to'), true),
        ];

        $entriesCollection = $this->service->ledgerEntries($filters);
        $summary = $this->service->summary($filters);

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new AppWalletAccountExport($entriesCollection),
                'app-wallet-account-' . now()->format('Y-m-d') . '.xlsx'
            );
        }

        $incomingMinor = $summary['incoming_minor'];
        $outgoingMinor = $summary['outgoing_minor'];
        $netMinor = $summary['net_minor'];
        $sourceTotalsMinor = $entriesCollection
            ->groupBy('source_key')
            ->map(fn (Collection $items) => (int) $items->sum('report_amount_minor'));

        $entries = $this->paginate($entriesCollection, $request);
        $sourceOptions = $this->service->sourceOptions();
        $manualWithdrawalOptions = AppWalletTransaction::withdrawalSourceLabels();
        $directionOptions = [
            'in' => 'وارد',
            'out' => 'صادر',
        ];

        return view('admin.app-wallet-account.index', compact(
            'entries',
            'filters',
            'incomingMinor',
            'outgoingMinor',
            'netMinor',
            'sourceTotalsMinor',
            'sourceOptions',
            'manualWithdrawalOptions',
            'directionOptions'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'string', 'in:' . implode(',', [
                AppWalletTransaction::DIRECTION_IN,
                AppWalletTransaction::DIRECTION_OUT,
            ])],
            'source' => ['nullable', 'string', 'in:' . implode(',', array_keys(AppWalletTransaction::sourceLabels()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $direction = (string) $data['direction'];
        $source = $direction === AppWalletTransaction::DIRECTION_IN
            ? AppWalletTransaction::SOURCE_MANUAL_DEPOSIT
            : (string) ($data['source'] ?? '');

        abort_unless(
            array_key_exists($source, AppWalletTransaction::sourceLabels()),
            422,
            'نوع الحركة غير صحيح'
        );

        abort_if(
            $direction === AppWalletTransaction::DIRECTION_OUT
            && ! array_key_exists($source, AppWalletTransaction::withdrawalSourceLabels()),
            422,
            'نوع السحب غير صحيح'
        );

        AppWalletTransaction::query()->create([
            'direction' => $direction,
            'source' => $source,
            'amount_minor' => WalletAmount::majorToMinor($data['amount']),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', $direction === AppWalletTransaction::DIRECTION_IN
            ? 'تم تسجيل إيداع محفظة التطبيق بنجاح'
            : 'تم تسجيل سحب محفظة التطبيق بنجاح'
        );
    }

    private function paginate(Collection $items, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'page']
        );

        return $paginator->appends($request->query());
    }

    private function parseDate(?string $value, bool $endOfDay = false): ?\Carbon\CarbonImmutable
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === '' || $value === null) {
            return null;
        }

        try {
            $date = \Carbon\CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function parseSearch(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' || $value === null ? null : $value;
    }

    private function parseOption(?string $value, array $allowed): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === '' || $value === null) {
            return null;
        }

        return in_array($value, $allowed, true) ? $value : null;
    }
}
