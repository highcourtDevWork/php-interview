<?php

/*
================================================================================
 TOPIC 03 - EXAMPLE 4 : REFACTORING AWAY FROM global  (project-style code)
================================================================================

 WHAT THIS DEMONSTRATES
   The exact refactor interviewers ask about when they show you legacy code:
   turning "global $config" into an injected dependency.

   You will see the BEFORE version, the AFTER version, and then the concrete
   proof of why the second is better - something the first version simply
   cannot do.

   This is the solution to Exercise 3 in 00-NOTES.txt.

 HOW TO RUN
   php 04-refactor-global.php

================================================================================
*/

declare(strict_types=1);

// #############################################################################
// #  BEFORE : the legacy style found in almost every old PHP project
// #############################################################################

$config = [
    'tax_rate'           => 0.18,
    'currency'           => 'INR',
    'discount_threshold' => 5000.0,
    'discount_percent'   => 10.0,
];

function calculateTaxLegacy(float $amount): float
{
    global $config;                                   // hidden dependency
    return round($amount * $config['tax_rate'], 2);
}

function applyDiscountLegacy(float $amount): float
{
    global $config;                                   // hidden dependency
    if ($amount >= $config['discount_threshold']) {
        return round($amount * (1 - $config['discount_percent'] / 100), 2);
    }
    return $amount;
}

function formatPriceLegacy(float $amount): string
{
    global $config;                                   // hidden dependency
    return $config['currency'] . ' ' . number_format($amount, 2);
}

// #############################################################################
// #  AFTER : explicit dependencies
// #############################################################################

/**
 * An immutable configuration object.
 *
 * readonly promoted properties mean the values are set once in the constructor
 * and can never be changed afterwards - so no distant code can modify pricing
 * rules half way through a request.
 */
final class PricingConfig
{
    public function __construct(
        public readonly float $taxRate,
        public readonly string $currency,
        public readonly float $discountThreshold,
        public readonly float $discountPercent,
    ) {
    }
}

/**
 * The service depends on its configuration EXPLICITLY, through the constructor.
 *
 * Read the constructor signature and you know everything this class needs.
 * That is the entire benefit - nothing is hidden in the method bodies.
 */
final class PricingService
{
    public function __construct(
        private readonly PricingConfig $config
    ) {
    }

    /**
     * @return array{subtotal: float, discount: float, taxable: float, tax: float, total: float}
     */
    public function priceBreakdown(float $subtotal): array
    {
        $discount = $this->discountOn($subtotal);
        $taxable  = round($subtotal - $discount, 2);
        $tax      = round($taxable * $this->config->taxRate, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxable'  => $taxable,
            'tax'      => $tax,
            'total'    => round($taxable + $tax, 2),
        ];
    }

    public function format(float $amount): string
    {
        return $this->config->currency . ' ' . number_format($amount, 2);
    }

    private function discountOn(float $amount): float
    {
        if ($amount < $this->config->discountThreshold) {
            return 0.0;
        }

        return round($amount * $this->config->discountPercent / 100, 2);
    }
}

// #############################################################################
// #  RUNNING BOTH VERSIONS
// #############################################################################

echo '=============== BEFORE (global) ===============' . PHP_EOL;

$amount = 10_000.00;

echo '  subtotal : ' . formatPriceLegacy($amount) . PHP_EOL;
$discounted = applyDiscountLegacy($amount);
echo '  after 10%: ' . formatPriceLegacy($discounted) . PHP_EOL;
echo '  tax      : ' . formatPriceLegacy(calculateTaxLegacy($discounted)) . PHP_EOL;
echo '  total    : ' . formatPriceLegacy($discounted + calculateTaxLegacy($discounted)) . PHP_EOL;

echo PHP_EOL . '=============== AFTER (injected) ===============' . PHP_EOL;

$indiaConfig = new PricingConfig(
    taxRate:           0.18,
    currency:          'INR',
    discountThreshold: 5000.0,
    discountPercent:   10.0,
);

$pricing   = new PricingService($indiaConfig);
$breakdown = $pricing->priceBreakdown($amount);

foreach ($breakdown as $label => $value) {
    printf('  %-9s: %s%s', $label, $pricing->format($value), PHP_EOL);
}

// #############################################################################
// #  THE PROOF : something the global version CANNOT do
// #############################################################################

echo PHP_EOL . '=============== WHY THE REFACTOR MATTERS ===============' . PHP_EOL;

// Two configurations alive AT THE SAME TIME, in the same request.
$uaeConfig = new PricingConfig(
    taxRate:           0.05,      // UAE VAT
    currency:          'AED',
    discountThreshold: 200.0,
    discountPercent:   5.0,
);

$uaePricing = new PricingService($uaeConfig);

$india = $pricing->priceBreakdown(10_000.00);
$uae   = $uaePricing->priceBreakdown(10_000.00);

printf('  India total : %s%s', $pricing->format($india['total']), PHP_EOL);
printf('  UAE total   : %s%s', $uaePricing->format($uae['total']), PHP_EOL);

echo PHP_EOL . '  With "global $config" this is IMPOSSIBLE without overwriting the' . PHP_EOL;
echo '  global, running the calculation, and putting it back - which breaks as' . PHP_EOL;
echo '  soon as anything else reads the config in between.' . PHP_EOL;

echo PHP_EOL . '=============== TESTABILITY ===============' . PHP_EOL;

// A "test" written without any framework, to make the point concretely.
$zeroTax = new PricingService(new PricingConfig(0.0, 'USD', 999_999.0, 0.0));
$result  = $zeroTax->priceBreakdown(100.00);

$passed = $result['tax'] === 0.0 && $result['total'] === 100.00;

echo '  Test "zero tax config produces no tax": ' . ($passed ? 'PASSED' : 'FAILED') . PHP_EOL;
echo '  Setting up that scenario took ONE line - no globals to set or reset,' . PHP_EOL;
echo '  and no risk of leaking state into the next test.' . PHP_EOL;

/*
================================================================================
 CODE EXPLANATION
================================================================================

 THE PROBLEM WITH THE "BEFORE" VERSION

   function calculateTaxLegacy(float $amount): float
   {
       global $config;
       ...
   }

   Read the signature: it says the function needs an amount. That is a lie - it
   also needs a fully populated global $config with a 'tax_rate' key. Five
   concrete costs follow:

     1. HIDDEN DEPENDENCY - you must read the body to know what it needs.
     2. UNTESTABLE - a test has to create a global before calling it, and clean
        it up afterwards, or the next test is affected.
     3. UNSAFE - any file anywhere can write to $config, so a wrong tax rate
        can come from anywhere in the codebase.
     4. NO TOOLING - the IDE and PHPStan know nothing about $config's shape, so
        a typo like $config['tax_rat'] is only discovered at runtime.
     5. ONE INSTANCE ONLY - you cannot have two tax rates at the same time,
        which the demo above proves.

 THE "AFTER" VERSION, PIECE BY PIECE

   public function __construct(public readonly float $taxRate, ...)
       CONSTRUCTOR PROPERTY PROMOTION (8.0) + readonly (8.1). Each parameter
       declares a property, types it, assigns it, and locks it. Four lines
       replace about twelve. readonly means the object is IMMUTABLE - no code
       can change the tax rate after construction.

   private readonly PricingConfig $config
       The service holds its configuration as a typed property. "private"
       because nothing outside needs it; readonly because it is never replaced.

   new PricingConfig(taxRate: 0.18, currency: 'INR', ...)
       NAMED ARGUMENTS (8.0). At the call site you can see what each value
       means. Compare with new PricingConfig(0.18, 'INR', 5000.0, 10.0) - four
       numbers with no clue which is which. Named arguments are also
       order-independent and skip-friendly. See file 32.

   @return array{subtotal: float, discount: float, ...}
       A PHPStan ARRAY SHAPE. It documents exactly which keys the returned array
       has and their types, so the IDE autocompletes $breakdown['subtotal'] and
       static analysis catches a typo. (In a bigger project this return value
       would itself be a readonly DTO class rather than an array.)

   private function discountOn(...)
       Extracted so priceBreakdown() reads as a sequence of business steps.
       Private because it is an internal detail, not part of the public API.

   10_000.00
       Numeric literal separator (7.4+). PHP ignores the underscore; it exists
       so a human can read the magnitude instantly.

 THE TESTABILITY SECTION
     $zeroTax = new PricingService(new PricingConfig(0.0, 'USD', 999_999.0, 0.0));
     One line creates a completely different pricing world. With globals this
     test would need to save $config, overwrite it, run, and restore it - and
     if the test failed half way through, the global would stay corrupted and
     break every later test. That fragility is the single most practical reason
     teams abandon globals.

================================================================================
 EXPECTED OUTPUT
================================================================================

   =============== BEFORE (global) ===============
     subtotal : INR 10,000.00
     after 10%: INR 9,000.00
     tax      : INR 1,620.00
     total    : INR 10,620.00

   =============== AFTER (injected) ===============
     subtotal : INR 10,000.00
     discount : INR 1,000.00
     taxable  : INR 9,000.00
     tax      : INR 1,620.00
     total    : INR 10,620.00

   =============== WHY THE REFACTOR MATTERS ===============
     India total : INR 10,620.00
     UAE total   : AED 9,975.00

   =============== TESTABILITY ===============
     Test "zero tax config produces no tax": PASSED

================================================================================
 KEY TAKEAWAYS
================================================================================

  1. Replace "global $x" with a constructor-injected dependency.
  2. The constructor signature becomes the documentation of what a class needs.
  3. Injection lets several configurations exist at once - impossible with a
     global, and required by tests.
  4. readonly + promotion makes configuration objects immutable and short.
  5. Named arguments make multi-parameter constructors readable at the call site.
  6. In legacy code, do this incrementally: write NEW code this way, and convert
     old code when you touch it. A full rewrite is rarely justified.

 INTERVIEW LINK
   "Why are global variables bad?"
   "What would you refactor in this legacy code?"      -> exactly this
   "What is dependency injection and why use it?"
   "How do you test a function that reads global config?"

 TRY THIS
   Write a THIRD config (say Singapore: 9% GST, SGD) and print all three
   breakdowns in one run. Then try to do the same with the legacy functions -
   the attempt itself is the lesson.
================================================================================
*/
