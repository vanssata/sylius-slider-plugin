# Coding policy

<!-- /ai-init fills the project-specific half of this file from the repository.
     The rules above the line are general and apply everywhere. -->

## General

- Follow the conventions already in the file you are editing, even when they are
  not the conventions you would choose. Consistency inside a file beats
  correctness in the abstract.
- One logical change per step. If you find yourself writing "and also", stop.
- No new abstraction until there are three call sites that need it.
- Extend through the framework's own extension points before writing a new
  mechanism: decorators, event subscribers, form extensions, template overrides,
  compiler passes, configuration.
- Never modify `vendor/` or `node_modules/`. A needed change to a third-party
  package becomes a decorator, an event listener, or a patch that is applied on
  install and reviewable in git.
- Dead code is deleted only in a task that is about deleting it, with evidence
  that it is dead.

## Comments

Write the comment that explains *why*, and only when the reason is not visible in
the code. Do not narrate what the next line does. Do not leave commented-out
code; git remembers it.

## Project specifics

<!-- KNOWN FACT entries only: the linters that actually run, the static-analysis
     level actually configured, the naming and layering the code actually uses.
     Cite the config file for each. -->
