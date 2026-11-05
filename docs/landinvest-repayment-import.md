# LandInvest property repayment import

Admin Actions > Import from LandInvest accepts a complete schema_version 1 JSON snapshot with generated_at (UTC YYYY-MM-DDTHH:MM:SSZ) and properties entries containing plan_number and status. Allowed statuses: paid, partial, unpaid, not_funded. LandInvest owns all repayment calculations.

Each valid save atomically replaces storage/app/private/landinvest/repayment-status.json (outside public/). Invalid imports preserve the previous file. Empty properties explicitly clears the snapshot. Unmatched properties are retained and reported. The import does not update plans or payment records.

Only auth:web administrators may import or see the indicators, in the admin dashboard and plan detail heading. No portal views or public endpoints expose the snapshot. Green = paid, yellow = partial, red = unpaid, solid gray = no investor funding recorded, outlined gray with slash = absent from snapshot. Matching follows property-tax normalization, removing trailing numeric parenthetical admin markers or (#), ignoring punctuation/case, preserving letters and leading zeros.

A future authenticated HTTPS receiver should call RepaymentSnapshot::import with the same JSON. No receiver is enabled now. Deploy the changed PHP/Blade files and rebuild route/view caches using the usual deployment process; no migration or frontend build is required.
