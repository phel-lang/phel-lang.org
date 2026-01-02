+++
title = "Debug"
weight = 18
sort_by = "weight"
template = "section-page.html"

[extra]
insert_after = "Testing"
+++

Debugging is an essential skill for any developer. Whether you're building a new feature, tracking down a bug, or understanding how your Phel code compiles to PHP, having the right debugging tools and techniques makes development faster and more enjoyable.

Phel offers multiple approaches to debugging, from simple print statements to sophisticated step-through debugging with breakpoints.

## Why Debugging Matters

- **Understand code flow**: See exactly how your functions execute and interact
- **Inspect values**: Examine variables and data structures at any point in execution
- **Find bugs faster**: Identify issues quickly instead of guessing
- **Learn the compiler**: For Phel core development, see how Phel code transforms to PHP

## Debugging Approaches

### Quick Inspection Tools

For quick debugging during development, Phel provides built-in helper functions that let you inspect values without setting up external tools. These are perfect for:
- Checking intermediate values in pipelines
- Tracing function calls
- Quick one-off inspections

[Learn about Phel's debug helpers →](/documentation/debug/phel-helpers/)

### Step-Through Debugging with XDebug

For deeper investigation, XDebug provides professional debugging capabilities:
- Set breakpoints and pause execution
- Step through code line by line
- Inspect call stacks and variable states
- Debug both your Phel code and the compiled PHP

[Set up XDebug for your editor →](/documentation/debug/xdebug-setup/)

### PHP Native Tools

Since Phel compiles to PHP, you can use familiar PHP debugging functions:
- `var_dump()` for quick output
- Symfony's `dump()` and `dd()` for beautiful output
- Inspect the generated PHP code

[Explore PHP debugging tools →](/documentation/debug/php-tools/)
