+++
title = "Editor Support"
weight = 2
description = "Set up Phel in PhpStorm, VS Code, Emacs, and Vim: syntax highlighting, indentation, and inline eval over nREPL"
aliases = ["/documentation/editor-support"]
+++

| Editor | Plugin | Highlighting | Structural editing | Inline eval |
|---|---|---|---|---|
| PhpStorm | [Phel IntelliJ plugin](https://github.com/phel-lang/phel-intellij-plugin) | yes | yes | yes (REPL actions) |
| VS Code | [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension) | yes | no | yes |
| Emacs | [interactive-lang-tools](https://codeberg.org/mmontone/interactive-lang-tools) | yes | with `paredit` or `smartparens` | yes |
| Vim | [`phel.vim`](https://github.com/danirod/phel.vim) | yes, plus indentation | no | with an nREPL client |

PhpStorm and VS Code install from their marketplace (search for "Phel"). The PhpStorm REPL actions need a project with Phel installed. The VS Code extension also debugs `.phel` files: see [Xdebug setup](/documentation/tooling/xdebug-setup/).

The Emacs package is not on MELPA; install it from source. `phel.vim` covers syntax and indentation only; for inline evaluation, point an nREPL client such as `vim-iced` or `conjure` at `phel nrepl`.

## nREPL and editor integration

Inline evaluation sends a form from your editor to a running server and shows the result. Phel ships an [nREPL](https://nrepl.org/) server:

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

Port `7888` and host `127.0.0.1` are the defaults. `--port=0` picks a free port. All evaluations share one process, as in the [REPL](/documentation/tooling/repl/).

The server implements the standard operations (`eval`, `clone`, `close`, `describe`, `load-file`, `interrupt`, `completions`, `lookup`, `info`, `eldoc`), so stock nREPL clients work unchanged. Two Phel operations back the [REPL workflow](/documentation/tooling/repl/#reload-changed-code):

| Operation | Parameters | Bind it to |
|---|---|---|
| `reload` | optional `all` to reload every namespace | "reload changed namespaces" |
| `run-tests` | `ns`, optional `var` | "run the test under the cursor" |

## Language server (LSP)

For editors that use the Language Server Protocol, Phel ships an LSP server (version 3.17, JSON-RPC over stdio):

```bash
vendor/bin/phel lsp
```

It provides hover, go-to-definition, find references, completion, document and workspace symbols, rename, formatting, and diagnostics. Completion and hover also cover PHP classes, methods, and functions; for method completion the server reads the receiver's type from `:tag` metadata or a `(new ...)` expression.

Both servers are also listed in [CLI commands](/documentation/reference/cli-commands/#nrepl).
