# Automatic affiliate payouts — implementation and rollout

This change is local code for stellarafi.com, not an activated payment service.
No dependencies were installed, no production database was changed, and no
Revolut request was made while implementing it. Laravel integration tests and
an end-to-end sandbox run remain required before production activation.

## Payment rules

- EUR only; at least EUR 100 per partner in eligible, approved commissions.
- A global cycle starts on `AFFILIATE_PAYOUT_STARTS_ON`, a required UTC date.
  First cutoff: start + 30 days. Further cutoffs: +60, +90, etc. These are
  30-day intervals, not calendar months or separate periods per affiliate.
- Prepare only commissions recorded and approved by that cutoff whose existing
  `eligible_payout_at` has passed. Null eligibility dates remain ineligible.
- Reserve the commissions, wait seven days from actual preparation, then submit
  automatically. A late scheduler run still gives the full seven-day hold.
- Missed cycles use the latest cutoff and collect older eligible commissions;
  the system does not replay several historical payments at once.
- Below EUR 100, commissions remain unreserved for a later round. Six-decimal
  commission accounting is retained; transfers round down to cents and the
  sub-cent remainder carries into the next successful payout calculation.
- New commissions currently start as `pending`, normally with a 14-day refund
  window. This integration does **not** auto-approve them. Automatic approval
  needs a reliable refund/cancellation feed and a separately agreed policy.

## Partner bank details

The Payouts page collects personal/business account type, first and last name
or legal company name, address lines, city, region, postal code, residence or
company country, bank country, IBAN, and SWIFT/BIC. The partner confirms account
ownership/authority and their current portal password. Impersonation sessions
cannot edit bank details. Updates are authenticated, CSRF-protected and throttled.

Sensitive details are stored in `affiliate_payout_methods.encrypted_details`
using Laravel encryption. Keep APP_KEY secure and backed up; follow Laravel's
key-rotation procedure when rotating it. Bank details are not flashed into the
session on validation failure or returned through model serialization. The page
only displays the last four IBAN characters and a registration status.

The worker registers an EUR counterparty with Revolut and checks that its
returned account matches the IBAN. In production it first requires an exact
Revolut Verification of Payee (VoP) account-name match and links that validation
to the later transfer. `verified_at` means this automated registration and name
check passed; it is still not proof of legal ownership. Production also queries
Revolut's country-specific field requirements and stops if additional fields
are needed. Initial support is IBAN accounts; local account/routing
formats and extra tax/reason fields need further work for affected corridors.

Replacing details creates a new method, invalidates the old default and
requires fresh registration. A prepared payout switches to the new registered
destination and restarts its seven-day hold. A processing transfer prevents
bank changes. Old encrypted versions are retained as payment history; agree a
retention policy before rollout.

## Server configuration

Set these through the deployment secret manager, not Git or chat:

```dotenv
AFFILIATE_AUTOMATIC_PAYOUTS_ENABLED=false
AFFILIATE_PAYOUT_STARTS_ON=2026-09-01
REVOLUT_ENVIRONMENT=sandbox
REVOLUT_SOURCE_ACCOUNT_ID=
REVOLUT_CLIENT_ID=
REVOLUT_JWT_ISSUER=stellarafi.com
REVOLUT_PRIVATE_KEY_PATH=
REVOLUT_PRIVATE_KEY_BASE64=
REVOLUT_REFRESH_TOKEN=
```

Set exactly one private-key option. For Azure App Service, prefer
`REVOLUT_PRIVATE_KEY_BASE64`: base64-encode the PEM locally and save only the
encoded value as a secret App Setting. `REVOLUT_PRIVATE_KEY_PATH` remains
available for hosts that mount a secret file. Never commit either value.

For the agreed start date, the first period is `2026-09-01 00:00 UTC` through
`2026-10-01 00:00 UTC`, and its seven-day hold ends on `2026-10-08 00:00 UTC`.
Later cutoffs are October 31, November 30, and so on.

The issuer must match the domain of the OAuth redirect URI actually registered
with Revolut. Use a private key file outside the repository and web root with
access limited to the application user. Configure the OAuth application and
consent in Revolut Business, using READ, WRITE and PAY permissions as required.
The client signs short-lived assertions with PHP OpenSSL and refreshes access
tokens. Monitor refresh-token/certificate expiry and renew consent when needed.
Revolut account approval rules or transfer checks can still hold a payment.

Use an isolated sandbox database and synthetic recipient details; never point
a sandbox configuration at a production commission ledger. Methods and payouts
are bound to their Revolut environment and cannot be silently repurposed.

Once dependencies have passed the user's security policy and are available:

1. Run `php artisan test --filter=AutomaticAffiliatePayoutsTest` against an
   isolated test environment. Also test concurrent workers against the same
   database engine used in production; SQLite does not verify row-lock behavior.
2. Apply migrations in staging, then render/test the partner bank form, including
   password failure, validation errors, ownership isolation and impersonation.
3. In Revolut sandbox, verify bank creation, acceptance, pending/completed/failed
   transfers, lost HTTP responses and reconciliation. The dynamic requirements
   endpoints are production-only; verify the intended production corridors
   before enabling real transfers.
4. Configure the agreed start date. Preview readiness with
   `php artisan affiliate:process-payouts --preview`. This shows counts only,
   does not calculate a payment preview, and makes no bank requests or writes.
   Validate Production OAuth and the selected EUR source account with
   `php artisan affiliate:process-payouts --provider-check`; this authenticates
   and reads the configured account, but cannot create a counterparty or payment.
5. Deploy the additive migration before serving the new code and rebuild the
   configuration cache using the existing deployment process.
6. The deployment pipeline enables **Always On**, sets the health-check path to
   `/up`, and sets `WEBSITE_SKIP_RUNNING_KUDUAGENT=false`. The deployment includes the triggered
   WebJob `laravel-scheduler`, whose NCRONTAB schedule runs
   `php /home/site/wwwroot/artisan schedule:run --no-interaction` every minute.
   The code registers `affiliate:process-payouts` every five minutes with overlap
   protection. Set the App Service Health check path to `/up` and confirm the
   instance is healthy before enabling payments. No Azure resource, WebJob, or
   persistent process was created by this code change.
7. After staging verification and Revolut setup, set the production environment
   and enable automatic payouts. Monitor scheduler failures and the admin Payouts
   page. No alert email/Slack integration is included.

## Failure handling and accounting

Affiliate row locks serialize preparation, destination changes and submission.
Unique affiliate/cycle and request IDs prevent duplicate reservations. A durable
processing claim is committed before the single payment POST. A timeout or
process crash triggers lookup by the same request ID; the worker never blindly
retries the POST. A crash before sending can therefore leave an unsubmitted
payment requiring operator reconciliation. Do not clear request IDs, reset
statuses, or manually pay a reserved commission without checking Revolut.

Only Revolut `completed` marks commissions paid. Pending transactions are polled
every run; completed transfers are rechecked daily for returns. Returned or
failed transfers remain reserved and block subsequent payments until reconciled.
Unknown states remain processing. Errors expose IDs and fixed reason codes,
never raw bank responses or credentials. Monitor application/APM configuration
to ensure it does not independently record sensitive form or HTTP bodies.

Manual admin status edits are blocked for automated payouts. A reserved
commission can be rejected during its hold, which stops payment and flags it
for reconciliation. Other changes require resolving the payout first. Existing
repair commands now exclude reserved commissions and Revolut payout amounts.
There is intentionally no generic retry/reset button: an operator must establish
the actual bank outcome before any replacement payment or reservation release.

## Validation completed locally

- `php tests/payout-policy-check.php`: 24 checks passed (threshold boundaries,
  cutoff dates, UTC handling, sub-cent conservation and IBAN validation).
- PHP syntax checks and `git diff --check`.
- Feature tests supplied for delayed payment, duplicate prevention, refund-window
  exclusion, uncertain submission, rejection during the hold, encrypted bank
  storage, destination changes and returned transfers. These were **not run**:
  `vendor/` is absent and dependency installation requires prior review/approval.

## Official references

- [Revolut bank transfers](https://developer.revolut.com/docs/guides/manage-accounts/transfers/bank-transfers)
- [Counterparty registration](https://developer.revolut.com/docs/guides/manage-accounts/counterparties/create-a-counterparty)
- [Business API authentication](https://developer.revolut.com/docs/guides/manage-accounts/get-started/make-your-first-api-request)
