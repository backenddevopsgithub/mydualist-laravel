<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Checkout - {{ config('mydualist.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/billing-checkout.js'])
    </head>
    <body class="bg-[#fbfaf5] text-lg font-sans text-stone-950 antialiased">
        @include('partials.marketing-header')

        <main
            class="mx-auto max-w-lg px-4 py-10 sm:py-14"
            id="billing-checkout-root"
            data-purchase-id="{{ $purchaseId }}"
            data-stripe-key="{{ $stripeKey }}"
            data-return-url="{{ $returnUrl }}"
            data-success-url="{{ $successUrl }}"
            data-failure-url="{{ $failureUrl }}"
            data-continue-label="{{ $continueLabel }}"
            data-api-base="{{ url('/api/v1/billing/purchases/'.$purchaseId) }}"
        >
            <div id="checkout-loading" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                <p class="text-sm font-semibold text-stone-600">Loading checkout...</p>
            </div>

            <div id="checkout-error" class="hidden rounded-[2rem] border border-red-200 bg-red-50 p-8 shadow-sm">
                <h1 class="text-2xl font-extrabold text-red-950">Unable to complete checkout</h1>
                <p id="checkout-error-message" class="mt-3 text-sm leading-6 text-red-900"></p>
                <a id="checkout-failure-link" href="{{ $failureUrl }}" class="mt-8 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">Try again</a>
            </div>

            <div id="checkout-completed" class="hidden rounded-[2rem] border border-emerald-100 bg-white p-8 shadow-sm">
                <h1 class="text-3xl font-extrabold text-emerald-950">Payment complete</h1>
                <p class="mt-4 text-sm leading-6 text-stone-700">
                    Your payment was successful. Your entitlements will refresh when you continue.
                </p>
                <p id="checkout-completed-product" class="mt-3 text-sm font-semibold text-stone-800"></p>
                <a id="checkout-continue-link" href="{{ $successUrl }}" class="mt-8 inline-flex rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white">{{ $continueLabel }}</a>
            </div>

            <div id="checkout-form" class="hidden space-y-6">
                <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                    <h1 class="text-3xl font-extrabold tracking-tight text-emerald-950">Complete payment</h1>
                    <p id="checkout-product-name" class="mt-2 text-sm font-semibold text-stone-800"></p>
                    <p id="checkout-amount" class="mt-1 text-sm text-stone-600"></p>
                </div>

                <form id="payment-form" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                    <div id="payment-element" class="min-h-[12rem]"></div>
                    <p id="payment-message" class="mt-4 hidden text-sm font-semibold text-red-700"></p>
                    <button
                        type="submit"
                        id="submit-button"
                        class="mt-6 w-full rounded-2xl bg-emerald-900 px-5 py-3 text-sm font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Pay now
                    </button>
                </form>
            </div>
        </main>
    </body>
</html>
