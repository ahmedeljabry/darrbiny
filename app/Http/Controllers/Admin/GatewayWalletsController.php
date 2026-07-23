<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\GatewayWalletAccountExport;
use App\Models\GatewayWalletTransaction;
use App\Services\Admin\GatewayWalletAccountService;
use App\Support\WalletAmount;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GatewayWalletsController extends BaseController
{
    public function __construct(
        private readonly GatewayWalletAccountService $service
    ) {}

    public function show(Request $request, string $gateway)
    {
        $gatewayConfig = $this->service->gatewayConfig($gateway);
        $filters = [
            'search' => $this->parseSearch($request->query('search')),
            'direction' => $this->parseOption($request->query('direction'), array_keys($this->service->directionOptions())),
            'source' => $this->parseOption($request->query('source'), array_keys($this->service->sourceOptions())),
            'from' => $this->parseDate($request->query('from')),
            'to' => $this->parseDate($request->query('to'), true),
        ];
        $activeTab = $request->query('tab') === 'operations' ? 'operations' : 'main';

        $entriesCollection = $this->service->ledgerEntries($gateway, $filters);
        $summary = $this->service->summary($gateway, $filters);

        if ($request->query('export') === 'excel') {
            return Excel::download(
                new GatewayWalletAccountExport($gatewayConfig, $summary, $entriesCollection),
                'gateway-wallet-'.$gateway.'-'.now()->format('Y-m-d').'.xlsx'
            );
        }

        $entries = $this->paginate($entriesCollection, $request);
        $gateways = $this->service->gateways();
        $directionOptions = $this->service->directionOptions();
        $sourceOptions = $this->service->sourceOptions();
        $incomingSourceOptions = GatewayWalletTransaction::incomingSourceLabels();
        $outgoingSourceOptions = GatewayWalletTransaction::outgoingSourceLabels();

        return view('admin.gateway-wallets.show', compact(
            'gateway',
            'gatewayConfig',
            'gateways',
            'filters',
            'summary',
            'entries',
            'directionOptions',
            'sourceOptions',
            'incomingSourceOptions',
            'outgoingSourceOptions',
            'activeTab'
        ));
    }

    public function store(Request $request, string $gateway)
    {
        $this->service->gatewayConfig($gateway);

        $data = $request->validate([
            'direction' => ['required', 'string', Rule::in(array_keys(GatewayWalletTransaction::directionLabels()))],
            'source' => ['required', 'string', Rule::in(array_keys(GatewayWalletTransaction::sourceLabels()))],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $direction = (string) $data['direction'];
        $source = (string) $data['source'];
        $allowedSources = $direction === GatewayWalletTransaction::DIRECTION_IN
            ? GatewayWalletTransaction::incomingSourceLabels()
            : GatewayWalletTransaction::outgoingSourceLabels();

        abort_unless(array_key_exists($source, $allowedSources), 422, 'مصدر الحركة غير مناسب لنوعها');

        GatewayWalletTransaction::query()->create([
            'gateway' => $gateway,
            'direction' => $direction,
            'source' => $source,
            'amount_minor' => WalletAmount::majorToMinor($data['amount']),
            'notes' => $data['notes'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('status', 'تم تسجيل حركة محفظة البوابة بنجاح');
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
