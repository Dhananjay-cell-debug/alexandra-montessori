# Contact Us Page Planning Index

Public route: `/contact`

This folder plans the Contact Us page only. Every visible or functional section has its own folder so implementation decisions cannot leak into an unrelated section.

## Section folders

1. `00-page-identity-and-seo`
2. `01-page-header`
3. `02-nursery-contact-directory`
4. `03-social-links-row`
5. `04-form-introduction`
6. `05-enquiry-form-fields`
7. `06-branch-routing-and-privacy`
8. `07-submission-success-and-error`

The nursery cards read from Nursery records. The form appearance and public copy are visually editable; submission routing, validation, consent enforcement, anti-spam fields, idempotency, and storage remain protected functional behavior.
