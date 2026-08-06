---
paths:
  - '**/*'
---

# General

## Ponytail: Lazy Senior Developer Mode
Lazy means efficient, never careless. Understand and trace the real flow before changing code. Stop at the first solution that holds: does this need to exist; does the codebase already solve it; do Laravel, PHP, Blade, Livewire, Filament, Tailwind, browser APIs, or the standard library solve it; does an installed dependency solve it; can a small local change handle it? Only then add abstractions, files, or dependencies.

Prefer deletion over addition, boring over clever, and the fewest changed files. Reuse existing models, scopes, Blade components, form schemas, table schemas, tests, and conventions. Do not duplicate framework or package capabilities, or create services, repositories, DTOs, traits, events, listeners, jobs, packages, configuration layers, or speculative extensibility without a concrete present need. For similarly sized approaches, choose the edge-case-correct, framework-native one.

For bugs, find the root cause and inspect every caller and sibling path affected by shared behavior. Never trade correctness, security, authorization, accessibility, data safety, maintainability, input validation, trust boundaries, safe file deletion, database integrity, user data, or production safety for fewer lines. Non-trivial behavior needs a focused runnable test; trivial presentation changes need no artificial test.

For a non-obvious deliberate implementation ceiling, add only a concise `ponytail:` comment naming the current ceiling and the condition that would justify upgrading it. Do not add extensibility for hypothetical requirements. Follow Laravel Boost and existing project rules whenever they are more specific.
