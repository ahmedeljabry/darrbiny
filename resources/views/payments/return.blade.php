@php
    $normalizedResult = strtolower((string) $result);
    $isFailure = in_array($normalizedResult, ['cancel', 'cancelled', 'canceled', 'failure', 'failed'], true);
    $isSuccess = in_array($normalizedResult, ['success', 'succeeded', 'paid', 'approved'], true);
    $title = $isFailure ? 'تعذر إكمال الدفع' : ($isSuccess ? 'تم استلام نتيجة الدفع' : 'حالة الدفع');
    $message = $isFailure
        ? 'تم إلغاء العملية أو فشل الدفع. يمكنك الرجوع إلى التطبيق والمحاولة مرة أخرى.'
        : 'تم الرجوع من بوابة الدفع. سيتم تحديث حالة الطلب تلقائياً بعد تأكيد البوابة.';
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Tahoma, Arial, sans-serif;
            background: #f6f7fb;
            color: #172033;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        main {
            width: min(100%, 420px);
            text-align: center;
            background: #fff;
            border: 1px solid #e6e8ef;
            border-radius: 16px;
            padding: 28px 22px;
            box-shadow: 0 18px 40px rgba(23, 32, 51, 0.08);
        }

        .icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: {{ $isFailure ? '#fee2e2' : '#dcfce7' }};
            color: {{ $isFailure ? '#b91c1c' : '#15803d' }};
            font-size: 28px;
            font-weight: 700;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 22px;
            line-height: 1.35;
        }

        p {
            margin: 0 0 18px;
            color: #5f6b7a;
            line-height: 1.7;
            font-size: 15px;
        }

        dl {
            margin: 0;
            display: grid;
            gap: 8px;
            text-align: start;
            font-size: 14px;
            color: #344054;
        }

        div.row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid #edf0f5;
            padding-top: 8px;
        }

        dt {
            color: #667085;
        }

        dd {
            margin: 0;
            direction: ltr;
            text-align: left;
            word-break: break-word;
        }
    </style>
</head>
<body>
    <main>
        <div class="icon">{{ $isFailure ? '!' : '✓' }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <dl>
            <div class="row">
                <dt>البوابة</dt>
                <dd>{{ strtoupper((string) $gateway) }}</dd>
            </div>
            <div class="row">
                <dt>النتيجة</dt>
                <dd>{{ $result }}</dd>
            </div>
            <div class="row">
                <dt>حالة العملية</dt>
                <dd>{{ $payment?->status ?? 'pending' }}</dd>
            </div>
        </dl>
    </main>
    <script>
        (function () {
            var payload = {!! $payloadJson ?: '{}' !!};
            var message = JSON.stringify({ type: 'payment_return', payload: payload });

            if (window.ReactNativeWebView && typeof window.ReactNativeWebView.postMessage === 'function') {
                window.ReactNativeWebView.postMessage(message);
            }
        })();
    </script>
</body>
</html>
