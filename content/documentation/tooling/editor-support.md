+++
title = "Editor Support"
weight = 2
description = "Set up Phel in PhpStorm, VS Code, Emacs, and Vim: syntax highlighting, indentation, and inline eval over nREPL"
aliases = ["/documentation/editor-support"]
+++

After this page your editor highlights Phel and evaluates code inline. Install the plugin for your editor, then connect it to Phel's nREPL server or language server.

| Editor | Plugin | Highlighting | Structural editing | Inline eval |
|---|---|---|---|---|
| PhpStorm | [Phel IntelliJ plugin](https://github.com/phel-lang/phel-intellij-plugin) | yes | yes | yes (REPL actions) |
| VS Code | [Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension) | yes | no | yes |
| Emacs | [interactive-lang-tools](https://codeberg.org/mmontone/interactive-lang-tools) | yes | with `paredit` or `smartparens` | yes |
| Vim | [`phel.vim`](https://github.com/danirod/phel.vim) | yes, plus indentation | no | with an nREPL client |

## PhpStorm

Open *Settings -> Plugins -> Marketplace*, search for "Phel", install, and restart the IDE. The REPL actions evaluate code in a Phel process, so use them inside a project that has Phel installed (`composer require phel-lang/phel-lang`).

## VS Code

Open the Extensions view (`Ctrl/Cmd+Shift+X`), search for "Phel", install, and reload the window. The extension also adds code snippets and step-through debugging: see [Xdebug setup](/documentation/tooling/xdebug-setup/) for `launch.json` and breakpoints in `.phel` files.

## Emacs

The package is not on MELPA. Install it from source (for example with `package-vc-install` or `straight.el`) and follow the setup in its repository. For paren-aware editing, pair it with the structural mode you already use.

## Vim

Install with your plugin manager, for example vim-plug:

```vim
Plug 'danirod/phel.vim'
```

Run `:PlugInstall`, restart Vim, and open a `.phel` file. The plugin covers syntax and indentation only. For inline evaluation, point a generic nREPL client (such as `vim-iced` or `conjure`) at a running `phel nrepl` server.

## nREPL and editor integration

Inline evaluation sends a form from your editor to a running server and shows the result. Phel ships an [nREPL](https://nrepl.org/) server:

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

Port `7888` and host `127.0.0.1` are the defaults. `--port=0` picks a free port. Every evaluation runs in the same process, so state and loaded namespaces persist between evaluations, as in the [REPL](/documentation/tooling/repl/).

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

It provides hover, go-to-definition, find references, completion, document and workspace symbols, rename, formatting, and diagnostics. Completion also knows PHP interop:

- instance methods and properties after `(.` and `(.-`
- static methods and constants after `Class/`
- class names in `(new ...)` and `\Fully\Qualified` positions
- PHP functions after `php/`

Hover shows the signature of PHP methods, functions, and classes, and signature help works inside `(new ...)` and method calls. The server infers the receiver's type from `:tag` metadata or a `(new ...)` expression or binding. When it cannot infer the type, it offers no completion instead of guessing.

Both servers are also listed in [CLI commands](/documentation/reference/cli-commands/#nrepl).
