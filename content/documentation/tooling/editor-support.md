+++
title = "Editor Support"
weight = 3
description = "Set up Phel in PhpStorm, VS Code, Emacs, and Vim: syntax highlighting, indentation, and inline eval over nREPL"
aliases = ["/documentation/editor-support"]
+++

Phel editing is provided by community plugins. Pick the one for your editor, then connect it to a running nREPL server for inline evaluation. Every plugin gives you at least syntax highlighting and filetype detection; richer features (structural editing, inline eval) depend on the plugin.

## PhpStorm

[Phel IntelliJ plugin](https://github.com/phel-lang/phel-intellij-plugin)

What you get:

- Syntax highlighting and filetype detection for `.phel`
- Structural (paren-aware) editing
- REPL actions to evaluate code from the editor

Install:

1. Open *Settings -> Plugins -> Marketplace*.
2. Search for "Phel".
3. Install, then restart the IDE.

Config note: the REPL actions evaluate against a Phel process, so run them from inside a project that has Phel installed (`composer require phel-lang/phel-lang`).

## VS Code

[Phel VS Code extension](https://github.com/phel-lang/phel-vs-code-extension)

What you get:

- Syntax highlighting and filetype detection for `.phel`
- Code snippets
- Inline evaluation of expressions

Install:

1. Open the Extensions view (`Ctrl/Cmd+Shift+X`).
2. Search for "Phel".
3. Install, then reload the window.

Config note: this extension also powers step-through debugging. See [XDebug setup](/documentation/tooling/xdebug-setup/) for `launch.json` and breakpoints in `.phel` files.

## Emacs

[interactive-lang-tools](https://codeberg.org/mmontone/interactive-lang-tools)

What you get:

- Phel editing support with REPL integration for interactive evaluation
- Standard Lisp editing helpers from Emacs

Install: follow the setup instructions in the repository. The package is not on MELPA, so install it from source (for example with `package-vc-install` or `straight.el`).

Config note: for paren editing, pair it with a structural-editing mode you already use, such as `paredit` or `smartparens`.

## Vim

[`phel.vim`](https://github.com/danirod/phel.vim)

What you get:

- Syntax highlighting
- Filetype detection for `.phel`
- Indentation

Install with your plugin manager, for example vim-plug:

```vim
Plug 'danirod/phel.vim'
```

Then run `:PlugInstall`, restart Vim, and open a `.phel` file. Config note: this is a syntax and indentation plugin. For inline evaluation, connect a generic nREPL client (such as `vim-iced` or `conjure`, configured for a custom nREPL) to a running `phel nrepl` server.

## nREPL and editor integration

Inline evaluation (send an expression from your editor and see the result without leaving the file) works by talking to a running server. Phel ships one:

```bash
vendor/bin/phel nrepl --port=7888 --host=127.0.0.1
```

This starts an [nREPL](https://nrepl.org/) server (Bencode over TCP) that nREPL-aware editors connect to. Once connected, evaluating a form in the editor runs it in the same process, so state and loaded namespaces persist between evaluations, exactly like the [REPL](/documentation/tooling/repl/).

Defaults are port `7888` and host `127.0.0.1`. Override either with the flags above. The server is also listed under [CLI commands](/documentation/tooling/cli-commands/#nrepl) alongside the other tooling entry points.

## Next steps

- [REPL](/documentation/tooling/repl/) - the interactive loop your editor connects to
- [CLI commands](/documentation/tooling/cli-commands/#nrepl) - start `phel nrepl` and `phel lsp`
- [XDebug setup](/documentation/tooling/xdebug-setup/) - step-through debugging in VS Code and PhpStorm
