+++
title = "Phel Browser REPL"
description = "Run Phel in your browser via PHP-WASM. No install."
template = "repl-page.html"

[extra]
runtime_version = "0.20"
+++

{% callout(kind="note") %}
This REPL runs Phel 0.20, an older build. Syntax added since then fails here: `#(...)` short functions, `~` unquote, dotted namespaces like `app.core`, and `.method` / `new` interop. For the current language, run the [local REPL](/documentation/tooling/repl/) after [installing Phel](/documentation/installation/).
{% end %}

Built on [seanmorris/php-wasm](https://github.com/seanmorris/php-wasm) with a patched `phel.phar`. Original demo by [@kambo-1st](https://github.com/kambo-1st).
