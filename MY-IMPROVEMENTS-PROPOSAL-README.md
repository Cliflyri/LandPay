# My Improvements - Feature Proposal

Status: Proposal only; implementation is not authorized by this document.
Date: October 2, 2026
Priority: Implement before the separate property details, maps, gallery, and video feature.

## Purpose

Give clients a dedicated place to notify admin of planned improvements and optionally document their progress with photos and updates.

As described by the project owner, the agreement requires clients to notify admin of proposed improvements. Admin approval is not required, provided clients comply with applicable county guidelines and requirements. This feature records notification and acknowledgment of receipt; it does not introduce an approval process.

## Client entry point

Add a **My Improvements** page in the client portal, with a prominent button:

**Notify admin of planned improvement**

The page should list the client's improvement records, showing the improvement title, associated plan, notification date, and receipt status. Opening a record displays the original notification, optional photos, and dated progress updates.

## Notification form

- **Property / plan:** If the client has more than one eligible plan, require selecting the relevant plan. If there is only one, select it automatically and clearly display it. Use recognizable plan numbers and titles. A client may only submit for a plan they are authorized to access.
- **Improvement title:** A short title, such as "Install entrance gate."
- **Description:** Required text prompted by "Tell us what you're planning."
- **Photos:** Optional at submission; clients can also add photos later.

Suggested explanatory copy:

> Use this form to notify us of your planned improvement as required by your agreement. You remain responsible for complying with all applicable county guidelines and requirements.

This wording addresses the notification requirement without claiming that the proposed work has been reviewed for compliance or that the client has satisfied every contractual requirement.

Suggested submission button:

**Notify admin**

Suggested confirmation:

> Your notification has been submitted. Admin will acknowledge receipt here.

## Notification and receipt badges

| Event | Client-facing badge | Color | Date shown |
| --- | --- | --- | --- |
| Client successfully submits a notification | Admin notified | Yellow | Submission date |
| Admin explicitly acknowledges receipt | Admin received | Green | Acknowledgment date |

Example before acknowledgment: **Admin notified - Oct 2, 2026** (yellow).

Example after acknowledgment: **Admin received - Oct 3, 2026** (green).

Use a small badge with readable text, so status is understandable without relying on color alone. Display the green badge as the current receipt status after acknowledgment, while retaining the original submission date in the record history.

"Admin notified" means the notification was saved and an in-app admin notice was created. It does not claim that admin has read it or that an email was delivered.

"Admin received" means admin explicitly acknowledged receipt. Merely opening the record must not turn the badge green. Acknowledgment is not approval, and clients do not need to wait for it to add updates or document their progress.

## Admin workflow

1. Client submits a planned improvement notification.
2. An admin notice links directly to the improvement record.
3. Admin reviews the notification and any attached photos.
4. Admin clicks **Acknowledge receipt**.
5. The record displays the green **Admin received** badge and acknowledgment date to the client and admin.

Store the acknowledgment timestamp and the identity of the admin who acknowledged it. Repeated clicks should not create duplicate acknowledgments or change the original receipt date.

Do not add approval, rejection, or permission-to-start controls to this workflow.

## Encourage optional progress photos

Suggested photo section copy:

> **Making your land your own?**
>
> Add photos of your improvement to document your progress - from your first steps to the finished project. Photos are optional, and you can add them anytime.

Suggested record action:

**Add photos or update**

Clients should be able to add photos at notification time or later, including before, progress, and finished-project photos. Optional captions and dated updates help make the record useful to both the client and admin. Uploading pictures must never be required to submit a notification or obtain acknowledgment.

Keep photos associated with the relevant improvement. Do not automatically publish them in a separate property gallery or make them public.

## Record history and follow-up updates

Recommended behavior for implementation:

- Preserve the original submitted notification and its submission date.
- Allow clients to add dated notes and photos afterward, including changes to the planned work.
- Create an admin notice when a client submits a follow-up update.
- Keep acknowledgment tied to the notification or update that admin actually received. An earlier acknowledgment must not imply that a later change has been acknowledged.
- Present follow-up receipt status on the relevant update, preserving the initial notification's acknowledgment history.

This provides a clear record of what was communicated and when without turning receipt acknowledgment into an approval workflow.

## Fit with the existing application

Read-only inspection identified existing client-to-plan memberships, private shared document uploads, secure messages, and admin notices. Existing document uploads include a property_image category.

The proposed feature should have dedicated improvement records for its notification text, plan association, receipt state, and history. Reuse appropriate existing upload, authorization, and admin notice patterns where practical.

Implementation should keep client files private, validate image uploads, and enforce access checks for both improvement records and individual images. Exact upload limits and supported image formats should be settled during implementation, with clear guidance in the form.

## First implementation scope

- My Improvements client page and individual improvement records.
- Plan selection for clients with multiple eligible plans.
- Planned improvement notification form.
- Yellow Admin notified badge with date.
- Admin notice linking to the submitted record.
- Explicit Acknowledge receipt button.
- Green Admin received badge with date.
- Optional photos at submission and in subsequent progress updates.
- Dated history preserving notifications and acknowledgments.

The separate admin-managed property details feature, including map pins, property galleries, and video links or embeds, remains a later proposal. This feature takes implementation priority over it.

## Acceptance criteria

1. A client with one eligible plan sees it selected and identified; a client with multiple eligible plans can choose the relevant plan.
2. A client can submit a title and description without uploading any photos.
3. Successful submission creates a persistent improvement record and an admin notice, and shows a yellow Admin notified badge with the submission date.
4. Opening a notification alone does not acknowledge it.
5. Clicking Acknowledge receipt records who acknowledged it and when, and shows a green Admin received badge with that date.
6. No approval is requested or required by the application workflow.
7. Clients can return later to add optional photos and dated progress updates.
8. Follow-up updates notify admin and do not inherit an acknowledgment that predates them.
9. Clients cannot access another client's private improvement records or images through a page, direct URL, or changed form value.
10. Original notification and acknowledgment history remains available after later updates.

## Decisions to settle during implementation

- Which plan statuses allow new notifications, and what access remains after a plan closes or terminates.
- Whether co-clients on a plan can view each other's improvements or whether records remain private to the submitting client and admin.
- Image count, size, and format limits, including handling of photos uploaded from phones.
- Whether email notifications supplement the in-app notices. In-app admin notices are part of the proposed first version; email is not yet specified.

This README documents the proposal only. It does not authorize application, database, configuration, or deployment changes.
