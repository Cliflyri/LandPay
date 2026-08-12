# Dedicated Administrator Notices Page

## Status

Proposal only. No application code has been implemented.

## Goal

Move the full open-notices panel off the administrator dashboard and onto a dedicated Notices page. This keeps the dashboard focused on payment plans while preserving a prominent, live notice indicator in the administrator sidebar.

## Lean implementation

- Add an authenticated GET /admin/notices page.
- Change the sidebar Notices link to the new page and retain its live X open badge.
- Remove the full notices panel from above the dashboard payment-plan list.
- Reuse the existing notice-row Blade partial, notice relationships, action links, and dismiss endpoint.
- Show open notices only, newest first, paginated at 25 per page.
- Show a simple No open notices empty state.
- Continue dismissing notices through the existing action.
- Update polling so sidebar counts refresh on every admin page, while detailed notice HTML refreshes only on the Notices page.
- Preserve typed form data by updating only the notice list; never reload the page.

## Initial page contents

- Page heading and open count.
- Existing notice title, message, and contextual action such as Review, Receive payment, Open message, or Open client.
- Existing Dismiss button.
- Pagination.

## Explicitly deferred

- Dismissed-notice history or restoration.
- Search, notice-type filters, and date filters.
- Bulk dismissal.
- Database or notice-schema changes.
- A duplicate notices panel or summary on the dashboard.

## Important details

- Existing links to /admin#admin-notices should be changed to the dedicated Notices route.
- The sidebar badge should remain visible and live so moving details off the dashboard does not hide new activity.
- Polling must remain read-only and must not mark notices or secure messages as viewed.
- Existing authorization and CSRF protection remain in force.

## Verification

- Confirm open notices appear newest first and paginate correctly.
- Confirm each contextual action and Dismiss still works.
- Confirm the sidebar badge updates while other admin pages remain open.
- Confirm only the notice list refreshes on the Notices page.
- Confirm the dashboard begins with the payment-plan content and has no full notices panel.
- Confirm the off-canvas mobile sidebar still exposes the Notices link and badge.
