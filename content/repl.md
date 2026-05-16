+++
title = "Phel Browser REPL"
description = "Run Phel code directly in your browser via PHP-WASM."
template = "simple-page.html"
+++

Experimental, ~80 MB on first load (cached after). Desktop recommended. Built on
[seanmorris/php-wasm](https://github.com/seanmorris/php-wasm) with a patched
`phel.phar`. Original demo by [@kambo-1st](https://github.com/kambo-1st).

Tip: press `Cmd/Ctrl + Enter` in the editor to run.

<iframe
  src="/wasm-repl/index.html"
  title="Phel WASM REPL"
  loading="lazy"
  class="wasm-repl-frame"
  allow="cross-origin-isolated">
</iframe>
