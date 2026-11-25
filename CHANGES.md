# Changelog

## 1.0.2 (2026112500)

- Copyright holder: TAG Digital Solutions Ltd.

## 1.0.1 (2026111200)

- Security: the "before footer" hook and the notice dismissal web service now check that the quiz
  activity is available to the user and that they can view it (`mod/quiz:view`).
- Security: configured URLs (setup, room scan, policy, Q&A) are escaped before they are rendered.
- Privacy: the user list for a context only covers course contexts.
- Fix: notice list layout (icon positioned, text flows inline).
- Added SECURITY.md with private vulnerability reporting.
- Added PHPUnit security tests and GitHub Actions CI (Moodle 4.5, PHP 8.2, MariaDB).

## 1.0.0 (2026040400)

- Initial release: preparation checklist modal shown to students before a scheduled quiz.
