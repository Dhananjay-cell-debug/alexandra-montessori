# Alexandra Montessori - Administrator Access Tutorial

## The access model

Every dashboard account has two controls:

1. **Role** decides what the person may do.
2. **Sections** decide where the person may do it.

The username does not grant access. There are only three selectable roles:

| Role | Access |
| --- | --- |
| Viewer | Can read only the selected sections. Cannot create, edit, publish, delete, upload, reply, export, or change settings. |
| Editor | Can create and change content only inside the selected sections. |
| Administrator | Has complete WordPress access and can give, change, or revoke any user's Role and Sections at any time. |

Do not create a separate role for each section. To make a Blog-only account,
choose **Editor** as the Role and select **Blog** under Sections.

## Available sections

The allocation list matches the Alexandra WordPress dashboard:

1. Overview
2. Nurseries
3. Events
4. Blog
5. Testimonials
6. Jobs
7. About us
8. Media Library
9. Site Settings
10. Submissions

Any combination is allowed. Selecting no sections revokes all dashboard-section
access without deleting the account.

## Add a user

1. Sign in with your Administrator account.
2. Open **Users -> Add User**.
3. Enter a unique username.
4. Email is optional.
5. Generate a strong password.
6. Choose **Viewer**, **Editor**, or **Administrator** under Role.
7. For Viewer or Editor, select the exact Sections the person needs.
8. Click **Add User**.
9. When an email is present, **Send the new user an email about their account**
   is selected automatically.

The Website field has been removed because it does not control dashboard
access. The invitation email contains the username, assigned Role, assigned
Sections, the first page inside that allocation, and a private one-time password
setup link. A plain-text password is never emailed. The Administrator receives
a matching audit email with the allocation and a link to manage that user.

These messages are part of the successful **Users -> Add User** action only.
Changing a user's Role or Sections later does not automatically email anybody.
The account password must be at least 20 characters and contain uppercase,
lowercase, a number, and a symbol.

When email is blank, WordPress email notification is disabled. The Administrator
must share the username and generated password through a private, secure channel
and reset it if the person loses it.

On the Local development site, the dashboard link works only on the development
computer. After production deployment, the same email mechanism uses the public
dashboard URL.

The Sections selector is hidden for Administrator because an Administrator
always has full access.

## Common allocations

| Requirement | Role | Sections |
| --- | --- | --- |
| Check Blog content without changing it | Viewer | Blog |
| Manage Blog articles and images | Editor | Blog, Media Library |
| Manage nursery pages and their images | Editor | Nurseries, Media Library |
| Review private website enquiries only | Viewer | Submissions |
| Work with enquiries and send replies | Editor | Submissions |
| Manage the whole website but not users/plugins/system administration | Editor | Select all |

Only give Administrator when the person must manage users, roles, plugins,
themes, or system configuration.

## Change or revoke access at any time

1. Open **Users -> All Users** as Administrator.
2. Open the person's account.
3. Change their Role and/or Sections.
4. Save the user.

Examples:

- Editor to Viewer immediately removes write access.
- Removing Blog immediately blocks the Blog menu and direct Blog URLs.
- Clear removes every section while keeping the login account.
- Viewer or Editor to Administrator gives complete access.
- Administrator to Viewer or Editor applies the newly selected Sections.

Ask the person to refresh the dashboard or sign out and back in after a change.

## Manual acceptance test

Use a private/incognito browser so your Administrator session stays open.

### Test an Editor

1. Create an Editor with only **Events** and **Media Library**.
2. Sign in as that user.
3. Confirm only the allocated website sections appear.
4. Confirm Events can be created and edited.
5. Confirm Media Library can be opened and files can be uploaded.
6. Paste a direct Blog or Site Settings admin URL and confirm access is denied.
7. Delete any temporary draft or media used for the test.

### Test a Viewer

1. Change the same account to Viewer and select **Events** and **Submissions**.
2. Confirm those sections can be opened and read.
3. Confirm create, edit, publish, delete, upload, reply, export, and settings
   controls are unavailable.
4. Confirm a direct URL to an unselected section is denied.
5. Confirm opening a Submission does not alter its status, owner, or first-opened
   audit data.

### Test revocation

1. As Administrator, open the account again.
2. Click **Clear** under Sections and save.
3. Confirm the account can sign in but cannot open any dashboard content section.
4. Restore the intended Role and Sections, or delete the temporary account.

## Security notes

- Give each person a separate account.
- Keep Administrator accounts to the smallest possible number.
- Prefer Viewer when someone only needs to check content.
- Never reuse a password shown in a screenshot.
- Do not send passwords in public messages or ordinary group chats.
- Review **Users -> All Users** periodically and remove access that is no longer
  needed.
